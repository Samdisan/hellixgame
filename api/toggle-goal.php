<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('player');

$player = find_player($_SESSION['player_id']);
if (!$player) {
    respond_json(['success' => false, 'error' => 'not_authenticated'], 403);
}

$scopeEntry = find_goal_scope_for_player($player);
if (!$scopeEntry) {
    respond_json(['success' => false, 'error' => 'no_goals'], 404);
}

$goalKey = trim($_POST['goal_key'] ?? '');
$completed = isset($_POST['completed']) ? (bool) $_POST['completed'] : true;

if ($goalKey === '') {
    respond_json(['success' => false, 'error' => 'missing_goal'], 400);
}

$validKeys = [];
$scope = $scopeEntry['scope'] ?? 'default';
foreach (($scopeEntry['goals'] ?? []) as $goalText) {
    $validKeys[] = goal_key($scope, (string) $goalText);
}

if (!in_array($goalKey, $validKeys, true)) {
    respond_json(['success' => false, 'error' => 'invalid_goal'], 400);
}

$newCompleted = set_goal_completion($player['id'], $goalKey, $completed);

respond_json([
    'success' => true,
    'completed_keys' => $newCompleted,
]);
