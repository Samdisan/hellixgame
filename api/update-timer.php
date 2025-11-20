<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$action = $_POST['action'] ?? '';
if (!in_array($action, ['start', 'pause', 'resume', 'reset'], true)) {
    respond_json(['error' => 'invalid_action'], 400);
    exit;
}

set_timer_state($action);
append_terminal_message('admin_terminal', 'info', 'Timer action: ' . $action);

if (!empty($_POST['redirect'])) {
    header('Location: ' . $_POST['redirect']);
    exit;
}

respond_json(['status' => 'ok', 'timer' => timer_status()]);
