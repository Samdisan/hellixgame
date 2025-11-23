<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$id = $_POST['id'] ?? '';
if ($id === '') {
    respond_json(['error' => 'missing_id'], 400);
    exit;
}

$messages = load_terminal_messages_with_ids();
$filtered = array_values(array_filter($messages, fn($m) => ($m['id'] ?? '') !== $id));

if (count($filtered) === count($messages)) {
    respond_json(['error' => 'not_found'], 404);
    exit;
}

save_json('terminal-messages.json', $filtered);
respond_json(['status' => 'deleted', 'id' => $id]);
