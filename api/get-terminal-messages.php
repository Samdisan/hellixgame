<?php
require_once __DIR__ . '/../includes/helpers.php';

$target = $_GET['target'] ?? 'public_terminal';
$playerId = trim($_GET['player_id'] ?? '');
$messages = load_terminal_messages_with_ids();
if ($target !== 'all') {
    $personalKey = $playerId !== '' && ($playerId === ($_SESSION['player_id'] ?? ''))
        ? 'player:' . $playerId
        : '';
    $messages = array_values(array_filter($messages, function ($m) use ($target, $personalKey) {
        $t = $m['target'] ?? '';
        return $t === $target || $t === 'both' || ($personalKey !== '' && $t === $personalKey);
    }));
}

usort($messages, fn($a, $b) => strtotime($b['timestamp']) <=> strtotime($a['timestamp']));
respond_json(['messages' => $messages]);
