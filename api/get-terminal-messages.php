<?php
require_once __DIR__ . '/../includes/helpers.php';

$target = $_GET['target'] ?? 'public_terminal';
$messages = load_terminal_messages_with_ids();
if ($target !== 'all') {
    $messages = array_values(array_filter($messages, fn($m) => ($m['target'] ?? '') === $target || ($m['target'] ?? '') === 'both'));
}

usort($messages, fn($a, $b) => strtotime($b['timestamp']) <=> strtotime($a['timestamp']));
respond_json(['messages' => $messages]);
