<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$target = $_POST['target'] ?? 'public_terminal';
$type = $_POST['type'] ?? 'info';
$message = trim($_POST['message'] ?? '');
if ($message === '') {
    respond_json(['error' => 'empty_message'], 400);
    exit;
}
append_terminal_message($target, $type, $message);
respond_json(['status' => 'queued']);
