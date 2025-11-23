<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('player');

$protocolId = $_GET['protocol'] ?? $_POST['protocol'] ?? '';
$redirect = $_GET['redirect'] ?? '/protocols-player.php';
$glue = (strpos($redirect, '?') !== false) ? '&' : '?';
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$wantsJson = ($_POST['mode'] ?? '') === 'json' || (strpos($acceptHeader, 'application/json') !== false);
if ($protocolId === '') {
    if ($wantsJson) {
        respond_json(['error' => 'missing'], 400);
    }
    header('Location: ' . $redirect . $glue . 'error=missing');
    exit;
}

$player = find_player($_SESSION['player_id']);
$protocol = fetch_protocol($protocolId);
if (!$player || !$protocol || !protocol_accessible($protocol, $player)) {
    if ($wantsJson) {
        respond_json(['error' => 'forbidden'], 403);
    }
    header('Location: ' . $redirect . $glue . 'error=forbidden');
    exit;
}

update_player_progress($player['id'], $protocolId);
append_terminal_message('admin_terminal', 'info', 'Гравець відкрив протокол ' . $protocolId);
if ($wantsJson) {
    respond_json(['status' => 'ok']);
}
header('Location: ' . $redirect . $glue . 'open=' . urlencode($protocolId));
exit;
