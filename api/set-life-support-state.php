<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$state = $_POST['state'] ?? 'failure';
$redirect = !empty($_POST['redirect']) ? $_POST['redirect'] : '/admin/admin-phases.php';

$timer = timer_status(false);
set_life_support_state($state, (int) ($timer['elapsed'] ?? 0));
append_terminal_message('admin_terminal', 'warning', '[LIFE] Режим життєзабезпечення встановлено: ' . $state);

header('Location: ' . $redirect);
exit;
