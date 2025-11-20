<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$id = trim($_POST['id'] ?? '');
$label = trim($_POST['label'] ?? '');
$description = trim($_POST['description'] ?? '');
$order = isset($_POST['order']) && $_POST['order'] !== '' ? (int) $_POST['order'] : null;
$duration = isset($_POST['duration_sec']) && $_POST['duration_sec'] !== '' ? (int) $_POST['duration_sec'] : null;
$uiIntensity = trim($_POST['ui_intensity'] ?? '');
$rawSubphases = trim($_POST['subphases'] ?? '');

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

if ($duration !== null && $duration > 0) {
    $newPhase['duration_sec'] = $duration;
}

if ($uiIntensity !== '') {
    $newPhase['ui_intensity'] = $uiIntensity;
}

if ($rawSubphases !== '') {
    $lines = preg_split('/\r?\n/', $rawSubphases);
    $newPhase['subphases'] = [];
    foreach ($lines as $line) {
        if (trim($line) === '') { continue; }
        [$idPart, $rest] = array_pad(explode(':', $line, 2), 2, '');
        [$labelPart, $startPart] = array_pad(explode(';', $rest, 2), 2, '');
        $newPhase['subphases'][] = [
            'id' => trim($idPart),
            'label' => trim($labelPart),
            'start_condition' => trim($startPart),
        ];
    }
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
