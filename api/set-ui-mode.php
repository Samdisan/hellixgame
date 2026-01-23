<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$mode = trim((string) ($_POST['ui_mode'] ?? ''));
$redirect = $_POST['redirect'] ?? '/admin/admin-phases.php';

$allowed = ['normal', 'warning', 'critical'];

$phases = load_json('phases.json');
if ($mode === '' || $mode === 'auto') {
    unset($phases['ui_mode_override']);
} elseif (in_array($mode, $allowed, true)) {
    $phases['ui_mode_override'] = $mode;
}

save_json('phases.json', $phases);

header('Location: ' . $redirect);
exit;
