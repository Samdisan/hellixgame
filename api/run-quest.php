<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$questId = $_POST['quest_id'] ?? $_GET['quest_id'] ?? '';
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? null;

if ($questId === '') {
    respond_json(['error' => 'missing_quest'], 400);
    exit;
}

$quest = find_quest($questId);
if (!$quest) {
    respond_json(['error' => 'quest_not_found'], 404);
    exit;
}

run_quest_actions($quest);
append_terminal_message('both', 'info', 'Quest executed: ' . $questId);

if ($redirect) {
    header('Location: ' . $redirect);
    exit;
}

respond_json(['status' => 'completed', 'quest' => $quest]);
