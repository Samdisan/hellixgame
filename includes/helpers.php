<?php
session_start();

function load_json(string $file): array
{
    $path = __DIR__ . '/../data/' . $file;
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return [];
    }
    return $decoded;
}

function save_json(string $file, array $data): bool
{
    $path = __DIR__ . '/../data/' . $file;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return (bool) file_put_contents($path, $json, LOCK_EX);
}

function require_role(string $role): void
{
    if (!isset($_SESSION['access_type']) || $_SESSION['access_type'] !== $role) {
        header('Location: /login.php');
        exit;
    }
}

function require_role_api(string $role): void
{
    if (!isset($_SESSION['access_type']) || $_SESSION['access_type'] !== $role) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
}

function find_player(string $playerId): ?array
{
    $players = load_json('players.json');
    foreach ($players as $player) {
        if ($player['id'] === $playerId) {
            return $player;
        }
    }
    return null;
}

function get_access_code(string $code): ?array
{
    $codes = load_json('access-codes.json');
    foreach ($codes as $entry) {
        if (strcasecmp($entry['code'], $code) === 0) {
            return $entry;
        }
    }
    return null;
}

function human_time(int $seconds): string
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $parts = [];
    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }
    $parts[] = str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . 'm';
    $parts[] = str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT) . 's';
    return implode(' ', $parts);
}

function timer_status(): array
{
    $timer = load_json('timer.json');
    $duration = $timer['duration_seconds'] ?? 0;
    $elapsedBase = $timer['elapsed_seconds'] ?? 0;
    $state = $timer['state'] ?? 'not_started';
    $start = isset($timer['start_time']) ? strtotime($timer['start_time']) : null;

    if ($state === 'running' && $start) {
        $elapsedBase += max(0, time() - $start);
    }

    $elapsed = min($elapsedBase, $duration);
    $remaining = max(0, $duration - $elapsed);
    if ($elapsed >= $duration && $duration > 0) {
        $state = 'finished';
    }

    return [
        'state' => $state,
        'elapsed' => $elapsed,
        'remaining' => $remaining,
        'duration' => $duration,
        'start_time' => $timer['start_time'] ?? null,
        'time_triggers' => $timer['time_triggers'] ?? [],
        'raw' => $timer,
    ];
}

function set_timer_state(string $action): void
{
    $timer = load_json('timer.json');
    $duration = $timer['duration_seconds'] ?? 0;
    $nowIso = gmdate('c');

    $computeElapsed = function () use ($timer) {
        $elapsed = $timer['elapsed_seconds'] ?? 0;
        if (($timer['state'] ?? 'not_started') === 'running' && !empty($timer['start_time'])) {
            $elapsed += max(0, time() - strtotime($timer['start_time']));
        }
        return $elapsed;
    };

    switch ($action) {
        case 'start':
            $timer['state'] = 'running';
            $timer['start_time'] = $nowIso;
            $timer['elapsed_seconds'] = 0;
            break;
        case 'pause':
            $timer['elapsed_seconds'] = $computeElapsed();
            $timer['state'] = 'paused';
            $timer['start_time'] = null;
            break;
        case 'resume':
            if (($timer['state'] ?? '') === 'paused') {
                $timer['state'] = 'running';
                $timer['start_time'] = $nowIso;
            }
            break;
        case 'reset':
            $timer['state'] = 'not_started';
            $timer['start_time'] = null;
            $timer['elapsed_seconds'] = 0;
            break;
    }

    if (($timer['elapsed_seconds'] ?? 0) >= $duration && $duration > 0) {
        $timer['state'] = 'finished';
    }

    $timer['last_updated'] = $nowIso;
    save_json('timer.json', $timer);
}

function current_phase(): array
{
    $phases = load_json('phases.json');
    return [
        'current' => $phases['current_phase'] ?? null,
        'started_elapsed' => $phases['current_phase_started_elapsed'] ?? 0,
        'phases' => $phases['phases'] ?? [],
    ];
}

function set_current_phase(string $phaseId): void
{
    $phases = load_json('phases.json');
    $timer = timer_status();
    $phases['current_phase_started_elapsed'] = $timer['elapsed'] ?? 0;
    $phases['current_phase'] = $phaseId;
    save_json('phases.json', $phases);
}

function append_terminal_message(string $target, string $type, string $message): void
{
    $messages = load_json('terminal-messages.json');
    $messages[] = [
        'timestamp' => gmdate('c'),
        'target' => $target,
        'type' => $type,
        'message' => $message,
    ];
    save_json('terminal-messages.json', $messages);
}

function respond_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function fetch_protocol(string $id): ?array
{
    $protocols = load_json('protocols.json');
    foreach ($protocols as $protocol) {
        if ($protocol['id'] === $id) {
            return $protocol;
        }
    }
    return null;
}

function update_player_progress(string $playerId, string $protocolId): void
{
    $progress = load_json('player-progress.json');
    $progress['players'] = $progress['players'] ?? [];
    $progress['players'][$playerId] = $progress['players'][$playerId] ?? ['opened_protocols' => []];
    if (!in_array($protocolId, $progress['players'][$playerId]['opened_protocols'], true)) {
        $progress['players'][$playerId]['opened_protocols'][] = $protocolId;
        save_json('player-progress.json', $progress);
    }
}

function has_opened_protocol(string $playerId, string $protocolId): bool
{
    $progress = load_json('player-progress.json');
    return in_array($protocolId, $progress['players'][$playerId]['opened_protocols'] ?? [], true);
}

function protocol_accessible(array $protocol, array $player): bool
{
    if (!($protocol['active'] ?? false)) {
        return false;
    }
    if (($protocol['level'] ?? 0) > ($player['access_level'] ?? 0)) {
        return false;
    }
    if (!empty($protocol['allowed_players']) && !in_array($player['id'], $protocol['allowed_players'], true)) {
        return false;
    }
    return true;
}

function render_glitch_hint(): string
{
    $hints = load_json('hints.json');
    if (empty($hints)) {
        return '';
    }

    $texts = array_map(function ($hint) {
        if (is_array($hint) && isset($hint['text'])) {
            return (string) $hint['text'];
        }
        return (string) $hint;
    }, $hints);

    $payload = htmlspecialchars(json_encode(array_values(array_filter($texts))), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return '<div class="glitch-hint-window" role="status" aria-live="polite" data-hints="' . $payload . '"><div class="glitch-hint"></div></div>';
}
