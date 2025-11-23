<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$playerId = $_POST['id'] ?? '';
$level = isset($_POST['access_level']) ? (int)$_POST['access_level'] : null;
$status = $_POST['status'] ?? null;

$players = load_json('players.json');
$updated = false;
foreach ($players as &$player) {
    if ($player['id'] === $playerId) {
        if ($level !== null) {
            $player['access_level'] = $level;
        }
        if ($status !== null) {
            $player['status'] = $status;
        }
        $updated = true;
        break;
    }
}

if (!$updated) {
    respond_json(['error' => 'player_not_found'], 404);
    exit;
}

save_json('players.json', $players);
append_terminal_message('admin_terminal', 'info', 'Player updated: ' . $playerId);
respond_json(['status' => 'saved']);
