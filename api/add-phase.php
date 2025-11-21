<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$id = trim($_POST['id'] ?? '');
$label = trim($_POST['label'] ?? '');
$description = trim($_POST['description'] ?? '');
$order = isset($_POST['order']) && $_POST['order'] !== '' ? (int) $_POST['order'] : null;
$plannedStart = isset($_POST['planned_start_elapsed_sec']) && $_POST['planned_start_elapsed_sec'] !== '' ? (int) $_POST['planned_start_elapsed_sec'] : null;
$plannedEnd = isset($_POST['planned_end_elapsed_sec']) && $_POST['planned_end_elapsed_sec'] !== '' ? (int) $_POST['planned_end_elapsed_sec'] : null;
$uiIntensity = trim($_POST['ui_intensity'] ?? '');
$startQuests = array_filter(array_map('trim', explode(',', $_POST['on_start_quests'] ?? '')));
$endQuests = array_filter(array_map('trim', explode(',', $_POST['on_end_quests'] ?? '')));

if ($id === '' || $label === '' || $description === '') {
    respond_json(['error' => 'missing_fields'], 400);
    exit;
}

$phasesData = load_json('phases.json');
$phasesData['phases'] = $phasesData['phases'] ?? [];

foreach ($phasesData['phases'] as $phase) {
    if (strcasecmp($phase['id'], $id) === 0) {
        respond_json(['error' => 'duplicate_id'], 400);
        exit;
    }
}

if ($order === null || $order <= 0) {
    $order = count($phasesData['phases']) + 1;
}

$newPhase = [
    'id' => $id,
    'label' => $label,
    'description' => $description,
    'order' => $order,
];

if ($plannedStart !== null || $plannedEnd !== null) {
    $newPhase['time_window'] = [
        'planned_start_elapsed_sec' => $plannedStart,
        'planned_end_elapsed_sec' => $plannedEnd,
    ];
}

if ($uiIntensity !== '') {
    $newPhase['ui_intensity'] = $uiIntensity;
}

if (!empty($startQuests)) {
    $newPhase['on_start_quests'] = array_values($startQuests);
}

if (!empty($endQuests)) {
    $newPhase['on_end_quests'] = array_values($endQuests);
}

$phasesData['phases'][] = $newPhase;

usort($phasesData['phases'], function ($a, $b) {
    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
});

save_json('phases.json', $phasesData);

append_terminal_message('admin_terminal', 'info', 'Додано фазу ' . $id);

if (!empty($_POST['redirect'])) {
    header('Location: ' . $_POST['redirect']);
    exit;
}

respond_json(['status' => 'ok', 'phase' => $newPhase]);
