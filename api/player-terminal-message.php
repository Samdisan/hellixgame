<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('player');

$message = trim($_POST['message'] ?? '');
if ($message === '') {
    respond_json(['error' => 'empty_message'], 400);
    exit;
}

$playerId = $_SESSION['player_id'] ?? 'UNKNOWN';
$prefix = $playerId ? '[' . $playerId . '] ' : '';
append_terminal_message('admin_terminal', 'player', $prefix . $message);

respond_json(['status' => 'queued']);
