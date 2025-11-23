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
    $duration = (int) ($timer['duration_seconds'] ?? 0);
    $elapsedBase = (int) ($timer['elapsed_seconds'] ?? 0);
    $state = $timer['state'] ?? 'not_started';
    $lastTick = isset($timer['last_updated_epoch']) ? (int) $timer['last_updated_epoch'] : null;
    $now = time();

    if ($state === 'running' && $lastTick) {
        $elapsedBase += max(0, $now - $lastTick);
    }

    $elapsed = min($elapsedBase, $duration);
    $remaining = max(0, $duration - $elapsed);
    if ($elapsed >= $duration && $duration > 0) {
        $state = 'finished';
    }

    $timer['elapsed_seconds'] = $elapsed;
    $timer['last_updated_epoch'] = $now;
    $timer['state'] = $state;

    if ($processTriggers) {
        $timer = process_time_triggers($timer, $elapsed, $remaining);
    }

    return [
        'state' => $state,
        'elapsed' => $elapsed,
        'remaining' => $remaining,
        'duration' => $duration,
        'last_updated_epoch' => $timer['last_updated_epoch'] ?? null,
        'time_triggers' => $timer['time_triggers'] ?? [],
        'raw' => $timer,
    ];
}

function trigger_quest(string $questId, int $elapsed, int $remaining): void
{
    $quest = find_quest($questId);
    if ($quest && quest_time_allowed($quest, $elapsed, $remaining)) {
        run_quest_actions($quest);
    }
}

function set_timer_state(string $action): void
{
    $timer = load_json('timer.json');
    $duration = $timer['duration_seconds'] ?? 0;
    $now = time();

    $computeElapsed = function () use ($timer, $now) {
        $elapsed = $timer['elapsed_seconds'] ?? 0;
        if (($timer['state'] ?? 'not_started') === 'running' && !empty($timer['last_updated_epoch'])) {
            $elapsed += max(0, $now - (int) $timer['last_updated_epoch']);
        }
        return $elapsed;
    };

    switch ($action) {
        case 'start':
            $timer['state'] = 'running';
            $timer['last_updated_epoch'] = $now;
            $timer['elapsed_seconds'] = 0;
            break;
        case 'pause':
            $timer['elapsed_seconds'] = $computeElapsed();
            $timer['state'] = 'paused';
            $timer['last_updated_epoch'] = $now;
            break;
        case 'resume':
            if (($timer['state'] ?? '') === 'paused') {
                $timer['state'] = 'running';
                $timer['last_updated_epoch'] = $now;
            }
            break;
        case 'reset':
            $timer['state'] = 'not_started';
            $timer['last_updated_epoch'] = null;
            $timer['elapsed_seconds'] = 0;
            break;
    }

    if (($timer['elapsed_seconds'] ?? 0) >= $duration && $duration > 0) {
        $timer['state'] = 'finished';
    }

    $timer['last_updated'] = gmdate('c', $now);
    save_json('timer.json', $timer);
}

function reset_timer_and_phases(): array
{
    $now = time();
    $timer = load_json('timer.json');
    $timer['state'] = 'not_started';
    $timer['elapsed_seconds'] = 0;
    $timer['last_updated_epoch'] = null;
    $timer['last_updated'] = gmdate('c', $now);

    if (isset($timer['time_triggers']) && is_array($timer['time_triggers'])) {
        foreach ($timer['time_triggers'] as &$trigger) {
            $trigger['fired'] = false;
        }
        unset($trigger);
    }

    save_json('timer.json', $timer);

    $phases = load_json('phases.json');
    $phases['current_phase_started_elapsed'] = 0;
    $phases['active_phases'] = [];
    $phases['executed_quests'] = [];

    if (isset($phases['phases']) && is_array($phases['phases'])) {
        foreach ($phases['phases'] as &$phase) {
            if (isset($phase['outcome'])) {
                $phase['outcome'] = null;
            }
        }
        unset($phase);

        $firstPhaseId = $phases['phases'][0]['id'] ?? null;
        $phases['current_phase'] = $firstPhaseId;
        if ($firstPhaseId) {
            $phases['active_phases'][] = [
                'id' => $firstPhaseId,
                'started_elapsed' => 0,
            ];
        }
    }

    save_json('phases.json', $phases);

    return timer_status(false);
}

function current_phase(): array
{
    $phases = load_json('phases.json');
    $timer = timer_status(false);

    $currentId = $phases['current_phase'] ?? null;
    $startedElapsed = (int) ($phases['current_phase_started_elapsed'] ?? 0);
    $activeStarts = [];
    foreach ($phases['active_phases'] ?? [] as $record) {
        if (!empty($record['id'])) {
            $activeStarts[$record['id']] = (int) ($record['started_elapsed'] ?? $startedElapsed);
        }
    }

    // Ensure at least the current phase exists in the active list for backward compatibility.
    if ($currentId && !isset($activeStarts[$currentId])) {
        $activeStarts[$currentId] = $startedElapsed;
    }

    $activePhases = [];
    $currentConfig = null;

    foreach ($phases['phases'] ?? [] as $phase) {
        $id = $phase['id'] ?? null;
        if (!$id) {
            continue;
        }

        $window = $phase['time_window'] ?? [];
        $outcome = $phase['outcome'] ?? null;
        $plannedStart = isset($window['planned_start_elapsed_sec']) ? (int) $window['planned_start_elapsed_sec'] : null;
        $plannedEnd = isset($window['planned_end_elapsed_sec']) ? (int) $window['planned_end_elapsed_sec'] : null;

        $startElapsed = $activeStarts[$id] ?? null;
        if ($startElapsed === null && $plannedStart !== null && ($timer['elapsed'] ?? 0) >= $plannedStart) {
            $startElapsed = $plannedStart;
        }

        $isActive = $startElapsed !== null;
        $isLifeSupportFailure = $id === 'PH_LIFEFAIL';
        $lifeSupportRepaired = $isLifeSupportFailure && $outcome === 'repaired';

        // The life-support failure should stay active (red mode) until an explicit repair,
        // even if its planned window has passed or other phases are running concurrently.
        if ($isActive && $lifeSupportRepaired) {
            $isActive = false;
        }
        if ($isActive && !$isLifeSupportFailure && $plannedEnd !== null && ($timer['elapsed'] ?? 0) > $plannedEnd) {
            $isActive = false;
        }

        if ($isActive) {
            $elapsedInPhase = max(0, ($timer['elapsed'] ?? 0) - $startElapsed);
            $remainingInPhase = $plannedEnd !== null ? max(0, $plannedEnd - ($timer['elapsed'] ?? 0)) : null;
            $activePhases[] = [
                'id' => $id,
                'title' => $phase['title'] ?? $id,
                'started_elapsed' => $startElapsed,
                'elapsed_sec' => $elapsedInPhase,
                'remaining_sec' => $remainingInPhase,
                'planned_start_elapsed_sec' => $plannedStart,
                'planned_end_elapsed_sec' => $plannedEnd,
                'ui_intensity' => $phase['ui_intensity'] ?? null,
            ];
        }

        if ($currentId === $id) {
            $currentConfig = $phase;
            $startedElapsed = $startElapsed ?? $startedElapsed;
        }
    }

    $phaseElapsed = max(0, ($timer['elapsed'] ?? 0) - $startedElapsed);
    $window = $currentConfig['time_window'] ?? [];
    $plannedStart = isset($window['planned_start_elapsed_sec']) ? (int) $window['planned_start_elapsed_sec'] : null;
    $plannedEnd = isset($window['planned_end_elapsed_sec']) ? (int) $window['planned_end_elapsed_sec'] : null;
    $phaseDuration = ($plannedStart !== null && $plannedEnd !== null) ? max(0, $plannedEnd - $plannedStart) : null;
    $phaseRemaining = $plannedEnd !== null ? max(0, $plannedEnd - ($timer['elapsed'] ?? 0)) : null;

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
        'active' => $activePhases,
        'current_meta' => [
            'duration_sec' => $phaseDuration,
            'elapsed_sec' => $phaseElapsed,
            'remaining_sec' => $phaseRemaining,
            'to_next_sec' => $phaseRemaining,
            'planned_start_elapsed_sec' => $plannedStart,
            'planned_end_elapsed_sec' => $plannedEnd,
        ],
        'next_phase' => $nextPhase,
    ];
}

function set_current_phase(string $phaseId): void
{
    $phases = load_json('phases.json');
    $timer = timer_status(false);
    $elapsed = $timer['elapsed'] ?? 0;

    $phases['active_phases'] = $phases['active_phases'] ?? [];
    $alreadyActive = false;
    foreach ($phases['active_phases'] as $record) {
        if (($record['id'] ?? '') === $phaseId) {
            $alreadyActive = true;
            break;
        }
    }

    if (!$alreadyActive) {
        $phases['active_phases'][] = [
            'id' => $phaseId,
            'started_elapsed' => $elapsed,
        ];
        run_phase_hooks($phaseId, 'on_start_quests');
    }

    $phases['current_phase_started_elapsed'] = $elapsed;
    $phases['current_phase'] = $phaseId;
    save_json('phases.json', $phases);
}

function append_terminal_message(string $target, string $type, string $message): void
{
    $messages = load_terminal_messages_with_ids();
    $messages[] = [
        'id' => uniqid('msg_', true),
        'timestamp' => gmdate('c'),
        'target' => $target,
        'type' => $type,
        'message' => $message,
    ];
    save_json('terminal-messages.json', $messages);
}

function load_message_triggers(): array
{
    $triggers = load_json('message-triggers.json');
    return is_array($triggers) ? $triggers : [];
}

function save_message_triggers(array $triggers): void
{
    save_json('message-triggers.json', array_values($triggers));
}

function apply_player_message_triggers(string $message, string $playerId = ''): void
{
    $triggers = load_message_triggers();
    if (empty($triggers)) {
        return;
    }

    foreach ($triggers as $trigger) {
        $pattern = $trigger['pattern'] ?? '';
        $response = $trigger['response'] ?? '';
        if ($pattern === '' || $response === '') {
            continue;
        }

        if (stripos($message, $pattern) === false) {
            continue;
        }

        $target = $trigger['target'] ?? 'both';
        $decorated = str_replace('{player}', $playerId !== '' ? $playerId : 'unknown', $response);
        append_terminal_message($target, 'info', '[TRIGGER] ' . $decorated);
    }
}

function load_terminal_messages_with_ids(): array
{
    $messages = load_json('terminal-messages.json');
    $changed = false;

    foreach ($messages as &$msg) {
        if (empty($msg['id'])) {
            $msg['id'] = uniqid('msg_', true);
            $changed = true;
        }
    }
    unset($msg);

    if ($changed) {
        save_json('terminal-messages.json', $messages);
    }

    return $messages;
}

function run_quest_actions(array $quest): void
{
    $actions = $quest['actions'] ?? [];
    $protocols = load_json('protocols.json');
    $players = load_json('players.json');
    $timer = timer_status(false);
    $phases = load_json('phases.json');

    $executed = $phases['executed_quests'] ?? [];
    if (!empty($quest['prevent_repeat']) && in_array($quest['id'], $executed, true)) {
        return;
    }

    $phasesDirty = false;

    foreach ($actions as $action) {
        if (!is_array($action) || empty($action['type'])) {
            continue;
        }

        switch ($action['type']) {
            case 'set_phase':
                if (!empty($action['to'])) {
                    set_current_phase($action['to']);
                    $phases = load_json('phases.json');
                    $phasesDirty = true;
                    append_terminal_message('admin_terminal', 'info', '[PHASE] Перемкнено на ' . $action['to']);
                }
                break;
            case 'set_timer_remaining':
                if (isset($action['remaining_sec'])) {
                    $newRemaining = max(0, (int) $action['remaining_sec']);
                    $timerData = load_json('timer.json');
                    $elapsedSeconds = $timer['elapsed'] ?? 0;
                    $timerData['duration_seconds'] = $elapsedSeconds + $newRemaining;
                    $timerData['elapsed_seconds'] = $elapsedSeconds;
                    $timerData['state'] = $timerData['state'] ?? 'running';
                    $timerData['last_updated_epoch'] = time();
                    $timerData['last_updated'] = gmdate('c');
                    save_json('timer.json', $timerData);
                    append_terminal_message('admin_terminal', 'warning', '[TIMER] Новий залишок: ' . human_time($newRemaining));
                }
                break;
            case 'activate_protocol':
            case 'deactivate_protocol':
            case 'unlock_protocol':
                foreach ($protocols as &$protocol) {
                    if ($protocol['id'] === ($action['protocol_id'] ?? '')) {
                        $protocol['active'] = $action['type'] !== 'deactivate_protocol';
                        if (!empty($action['allowed_players']) && is_array($action['allowed_players'])) {
                            $protocol['allowed_players'] = array_values(array_unique(array_map('strval', $action['allowed_players'])));
                        }
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
    if (!empty($quest['prevent_repeat'])) {
        $executed[] = $quest['id'];
        $phases['executed_quests'] = array_values(array_unique($executed));
        $phasesDirty = true;
    }
    if ($phasesDirty) {
        save_json('phases.json', $phases);
    }
}

function process_time_triggers(array $timer, int $elapsed, int $remaining): array
{
    $changed = false;
    foreach ($timer['time_triggers'] ?? [] as &$trigger) {
        if (!empty($trigger['fired'])) {
            continue;
        }

        $elapsedOk = !isset($trigger['elapsed_ge_sec']) || $elapsed >= (int) $trigger['elapsed_ge_sec'];
        $remainingOk = !isset($trigger['remaining_le_sec']) || $remaining <= (int) $trigger['remaining_le_sec'];

        if ($elapsedOk && $remainingOk && !empty($trigger['quest_id'])) {
            trigger_quest($trigger['quest_id'], $elapsed, $remaining);
            append_terminal_message('admin_terminal', 'info', '[TRIGGER] Спрацював тригер ' . ($trigger['id'] ?? ''));
            $trigger['fired'] = true;
            $trigger['fired_at'] = gmdate('c');
            $changed = true;
        }
    }
    unset($trigger);

    if ($changed) {
        $timer['last_updated_epoch'] = time();
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

function quest_time_allowed(array $quest, int $elapsed, int $remaining): bool
{
    if (empty($quest['time_constraints'])) {
        return true;
    }

    $constraints = $quest['time_constraints'];
    if (isset($constraints['min_elapsed_sec']) && $elapsed < (int) $constraints['min_elapsed_sec']) {
        return false;
    }
    if (isset($constraints['max_elapsed_sec']) && $elapsed > (int) $constraints['max_elapsed_sec']) {
        return false;
    }
    if (isset($constraints['remaining_le_sec']) && $remaining > (int) $constraints['remaining_le_sec']) {
        return false;
    }

    return true;
}

function run_phase_hooks(string $phaseId, string $key): void
{
    $phases = load_json('phases.json');
    $phaseConfig = null;
    foreach ($phases['phases'] ?? [] as $phase) {
        if (($phase['id'] ?? '') === $phaseId) {
            $phaseConfig = $phase;
            break;
        }
    }

    if (!$phaseConfig || empty($phaseConfig[$key]) || !is_array($phaseConfig[$key])) {
        return;
    }

    foreach ($phaseConfig[$key] as $questId) {
        $quest = find_quest($questId);
        if ($quest) {
            run_quest_actions($quest);
        }
    }
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
    if (!empty($protocol['flags']['general_only'])) {
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
