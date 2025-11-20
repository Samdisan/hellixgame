<?php
require_once __DIR__ . '/../includes/helpers.php';

$target = $_GET['target'] ?? 'public_terminal';
$messages = load_json('terminal-messages.json');
if ($target !== 'all') {
    $messages = array_values(array_filter($messages, fn($m) => ($m['target'] ?? '') === $target));
}

usort($messages, fn($a, $b) => strtotime($b['timestamp']) <=> strtotime($a['timestamp']));
respond_json(['messages' => $messages]);
