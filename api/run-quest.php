<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$questId = $_POST['quest_id'] ?? $_GET['quest_id'] ?? '';
if ($questId === '') {
    respond_json(['error' => 'missing_quest'], 400);
    exit;
}

$quests = load_json('quests.json');
$quest = null;
foreach ($quests as $q) {
    if ($q['id'] === $questId) {
        $quest = $q;
        break;
    }
}

if (!$quest) {
    respond_json(['error' => 'quest_not_found'], 404);
    exit;
}

append_terminal_message('admin_terminal', 'info', 'Quest run: ' . $questId);
respond_json(['status' => 'queued', 'quest' => $quest]);
