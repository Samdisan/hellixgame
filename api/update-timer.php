<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$action = $_POST['action'] ?? '';
if (!in_array($action, ['start', 'pause', 'resume', 'reset'], true)) {
    respond_json(['error' => 'invalid_action'], 400);
    exit;
}

if ($action === 'reset') {
    $code = $_POST['reset_code'] ?? '';
    if ($code !== '30071992') {
        if (!empty($_POST['redirect'])) {
            $redirect = $_POST['redirect'];
            $sep = strpos($redirect, '?') === false ? '?' : '&';
            header('Location: ' . $redirect . $sep . 'error=reset_code');
            exit;
        }
        respond_json(['error' => 'reset_code_required'], 403);
        exit;
    }

    $status = reset_timer_and_phases();
    append_terminal_message('both', 'warning', 'Таймер скинуто до початку (RESET)');
} else {
    set_timer_state($action);
    append_terminal_message('admin_terminal', 'info', 'Timer action: ' . $action);
    $status = timer_status();
}

if (!empty($_POST['redirect'])) {
    header('Location: ' . $_POST['redirect']);
    exit;
}

respond_json(['status' => 'ok', 'timer' => $status]);
