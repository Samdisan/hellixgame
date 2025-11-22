<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$players = load_json('players.json');
$protocols = load_json('protocols.json');
$quests = load_json('quests.json');
$phases = current_phase();
$timer = load_json('timer.json');

$errors = [];
$warnings = [];

$playerIds = array_column($players, 'id');
$protocolIds = array_column($protocols, 'id');
$phaseIds = array_map(fn($p) => $p['id'], $phases['phases'] ?? []);

foreach ($quests as $quest) {
    if (!in_array($quest['phase'], $phaseIds, true)) {
        $warnings[] = 'Quest ' . $quest['id'] . ' посилається на відсутню фазу ' . $quest['phase'];
    }
    foreach ($quest['actions'] ?? [] as $action) {
        if (($action['type'] ?? '') === 'push_protocol' && !in_array($action['id'] ?? '', $protocolIds, true)) {
            $warnings[] = 'Quest ' . $quest['id'] . ' вказує на невідомий протокол ' . ($action['id'] ?? '');
        }
    }
}

foreach ($timer['time_triggers'] ?? [] as $trigger) {
    if (!in_array($trigger['quest_id'], array_column($quests, 'id'), true)) {
        $errors[] = 'Тригер ' . ($trigger['id'] ?? $trigger['quest_id']) . ' посилається на відсутній квест ' . $trigger['quest_id'];
    }
}

foreach ($protocols as $protocol) {
    if (!in_array($protocol['phase'], $phaseIds, true)) {
        $warnings[] = 'Протокол ' . $protocol['id'] . ' без валідної фази';
    }
    if (($protocol['level'] ?? 0) < 1 || ($protocol['level'] ?? 0) > 5) {
        $warnings[] = 'Рівень доступу протоколу ' . $protocol['id'] . ' виходить за межі 1–5';
    }
}

respond_json([
    'errors' => $errors,
    'warnings' => $warnings,
]);
