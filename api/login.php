<?php
require_once __DIR__ . '/../includes/helpers.php';

$playerId = trim($_POST['player_id'] ?? '');
$adminRequested = !empty($_POST['admin']);

if ($adminRequested) {
    $_SESSION['access_code'] = null;
    $_SESSION['access_type'] = 'admin';
    $_SESSION['player_id'] = null;
    header('Location: /admin/admin-phases.php');
    exit;
}

if ($playerId !== '') {
    $player = find_player($playerId);
    if (!$player) {
        header('Location: /?error=1#access');
        exit;
    }

    $_SESSION['access_code'] = null;
    $_SESSION['access_type'] = 'player';
    $_SESSION['player_id'] = $playerId;

    header('Location: /player.php');
    exit;
}

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
