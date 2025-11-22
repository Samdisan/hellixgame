<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$phaseId = $_POST['phase_id'] ?? '';
$outcome = $_POST['outcome'] ?? '';
$redirect = $_POST['redirect'] ?? '/admin/admin.php';

$validOutcomes = ['repaired', 'not_repaired'];

if ($phaseId === '' || !in_array($outcome, $validOutcomes, true)) {
    $_SESSION['error'] = 'Неправильні дані для результату фази.';
    header('Location: ' . $redirect);
    exit;
}


$phases = load_json('phases.json');
$found = false;
if (!empty($phases['phases']) && is_array($phases['phases'])) {
    foreach ($phases['phases'] as &$phase) {
        if (($phase['id'] ?? '') === $phaseId) {
            if (!empty($phase['outcome'])) {
                $_SESSION['error'] = 'Результат цієї фази вже зафіксовано.';
                header('Location: ' . $redirect);
                exit;
            }
            $phase['outcome'] = $outcome;
            $found = true;
            break;
        }
    }
    unset($phase);
}

if (!$found) {
    $_SESSION['error'] = 'Фазу не знайдено.';
    header('Location: ' . $redirect);
    exit;
}

// If the failure phase was repaired, drop it from the active list and clear it as current
// so the UI returns to the normal design instead of the red alert skin.
if ($outcome === 'repaired') {
    $phases['active_phases'] = array_values(array_filter($phases['active_phases'] ?? [], function ($record) use ($phaseId) {
        return ($record['id'] ?? '') !== $phaseId;
    }));

    if (($phases['current_phase'] ?? '') === $phaseId) {
        $phases['current_phase'] = null;
        $phases['current_phase_started_elapsed'] = null;

        if (!empty($phases['active_phases'])) {
            $phases['current_phase'] = $phases['active_phases'][0]['id'] ?? null;
            $phases['current_phase_started_elapsed'] = $phases['active_phases'][0]['started_elapsed'] ?? null;
        }
    }

    // When life support is repaired, continue directly with the Origin recovery effort.
    set_current_phase('PH_ORIGIN_RECOVERY');
}

save_json('phases.json', $phases);
append_terminal_message('both', 'info', '[PHASE] ' . $phaseId . ' → ' . ($outcome === 'repaired' ? 'система відремонтована' : 'система не відремонтована'));

$_SESSION['success'] = 'Результат фази оновлено.';
header('Location: ' . $redirect);
exit;
