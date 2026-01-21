<?php
require_once __DIR__ . '/../includes/helpers.php';

$code = trim($_POST['code'] ?? '');
if ($code === '') {
    respond_json(['error' => 'missing_code'], 400);
    exit;
}

$entry = get_access_code($code);
if (!$entry) {
    header('Location: /?error=1#access');
    exit;
}

$_SESSION['access_code'] = $code;
$_SESSION['access_type'] = $entry['type'];
$_SESSION['player_id'] = $entry['player_id'] ?? null;

$redirect = $entry['type'] === 'admin' ? '/admin/admin-phases.php' : '/player.php';
header('Location: ' . $redirect);
exit;
