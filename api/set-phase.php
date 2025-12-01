<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$phaseId = $_POST['phase'] ?? '';
if ($phaseId === '') {
    respond_json(['error' => 'missing_phase'], 400);
    exit;
}

set_current_phase($phaseId);
append_terminal_message('admin_terminal', 'info', 'Phase set to ' . $phaseId);

if (!empty($_POST['redirect'])) {
    header('Location: ' . $_POST['redirect']);
    exit;
}

respond_json(['status' => 'ok', 'phase' => $phaseId]);
