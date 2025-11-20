<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('player');

$protocolId = $_GET['protocol'] ?? $_POST['protocol'] ?? '';
$redirect = $_GET['redirect'] ?? '/protocols-player.php';
$glue = str_contains($redirect, '?') ? '&' : '?';
if ($protocolId === '') {
    header('Location: ' . $redirect . $glue . 'error=missing');
    exit;
}

$player = find_player($_SESSION['player_id']);
$protocol = fetch_protocol($protocolId);
if (!$player || !$protocol || !protocol_accessible($protocol, $player)) {
    header('Location: ' . $redirect . $glue . 'error=forbidden');
    exit;
}

update_player_progress($player['id'], $protocolId);
append_terminal_message('admin_terminal', 'info', 'Гравець відкрив протокол ' . $protocolId);
header('Location: ' . $redirect . $glue . 'open=' . urlencode($protocolId));
exit;
