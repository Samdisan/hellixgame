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

function timer_status(bool $processTriggers = true): array
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

    if ($processTriggers) {
        $timer = process_time_triggers($timer, $elapsed);
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
    $timer = timer_status(false);

    $currentId = $phases['current_phase'] ?? null;
    $startedElapsed = (int) ($phases['current_phase_started_elapsed'] ?? 0);
    $currentConfig = null;

    foreach ($phases['phases'] ?? [] as $phase) {
        if (($phase['id'] ?? null) === $currentId) {
            $currentConfig = $phase;
            break;
        }
    }

    $phaseElapsed = max(0, ($timer['elapsed'] ?? 0) - $startedElapsed);
    $phaseDuration = $currentConfig['duration_sec'] ?? null;
    $phaseRemaining = $phaseDuration !== null ? max(0, $phaseDuration - $phaseElapsed) : null;

    $nextPhase = null;
    if ($phases['phases'] ?? false) {
        $ids = array_column($phases['phases'], 'id');
        $idx = array_search($currentId, $ids, true);
        if ($idx !== false && isset($phases['phases'][$idx + 1])) {
            $nextPhase = $phases['phases'][$idx + 1];
        }
    }

    return [
        'current' => $currentId,
        'started_elapsed' => $startedElapsed,
        'phases' => $phases['phases'] ?? [],
        'current_meta' => [
            'duration_sec' => $phaseDuration,
            'elapsed_sec' => $phaseElapsed,
            'remaining_sec' => $phaseRemaining,
            'to_next_sec' => $phaseRemaining,
        ],
        'next_phase' => $nextPhase,
    ];
}

function set_current_phase(string $phaseId): void
{
    $phases = load_json('phases.json');
    $timer = timer_status(false);
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

function run_quest_actions(array $quest): void
{
    $actions = $quest['actions'] ?? [];
    $protocols = load_json('protocols.json');
    $players = load_json('players.json');

    foreach ($actions as $action) {
        if (!is_array($action) || empty($action['type'])) {
            continue;
        }

        switch ($action['type']) {
            case 'set_phase':
                if (!empty($action['to'])) {
                    set_current_phase($action['to']);
                    append_terminal_message('admin_terminal', 'info', '[PHASE] Перемкнено на ' . $action['to']);
                }
                break;
            case 'activate_protocol':
            case 'deactivate_protocol':
            case 'unlock_protocol':
                foreach ($protocols as &$protocol) {
                    if ($protocol['id'] === ($action['protocol_id'] ?? '')) {
                        $protocol['active'] = $action['type'] !== 'deactivate_protocol';
                        if ($action['type'] === 'unlock_protocol') {
                            $protocol['public'] = true;
                        }
                        append_terminal_message('admin_terminal', 'protocol', '[PROTOCOL] ' . $protocol['id'] . ' → ' . ($protocol['active'] ? 'active' : 'inactive'));
                    }
                }
                unset($protocol);
                break;
            case 'set_access':
            case 'set_status':
                foreach ($players as &$player) {
                    if ($player['id'] === ($action['player_id'] ?? '')) {
                        if ($action['type'] === 'set_access' && isset($action['level'])) {
                            $player['access_level'] = (int) $action['level'];
                        }
                        if ($action['type'] === 'set_status' && isset($action['status'])) {
                            $player['status'] = $action['status'];
                        }
                        append_terminal_message('admin_terminal', 'info', '[PLAYER] ' . $player['id'] . ' оновлено');
                    }
                }
                unset($player);
                break;
            case 'push_terminal':
                $target = $action['target'] ?? 'public_terminal';
                $type = $action['level'] ?? 'info';
                $text = $action['message'] ?? ($action['message_id'] ?? '');
                if ($text !== '') {
                    append_terminal_message($target, $type, $text);
                }
                break;
        }
    }

    save_json('protocols.json', $protocols);
    save_json('players.json', $players);
}

function process_time_triggers(array $timer, int $elapsed): array
{
    if (($timer['state'] ?? 'not_started') === 'not_started' && $elapsed === 0) {
        return $timer;
    }

    $changed = false;
    foreach ($timer['time_triggers'] ?? [] as &$trigger) {
        $at = (int) ($trigger['at_seconds'] ?? 0);
        if (!empty($trigger['fired'])) {
            continue;
        }
        if ($elapsed >= $at && !empty($trigger['quest_id'])) {
            $quest = find_quest($trigger['quest_id']);
            if ($quest) {
                run_quest_actions($quest);
                append_terminal_message('admin_terminal', 'info', '[TRIGGER] Спрацював тригер ' . $trigger['id']);
            }
            $trigger['fired'] = true;
            $changed = true;
        }
    }
    unset($trigger);

    if ($changed) {
        $timer['last_updated'] = gmdate('c');
        save_json('timer.json', $timer);
    }

    return $timer;
}

function find_quest(string $questId): ?array
{
    $quests = load_json('quests.json');
    foreach ($quests as $quest) {
        if ($quest['id'] === $questId) {
            return $quest;
        }
    }
    return null;
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
