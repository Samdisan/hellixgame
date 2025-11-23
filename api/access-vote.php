<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('player');

$approvers = ['PL_STATION_ROSS', 'PL_STATION_CROW_PSY', 'PL_STATION_SATO'];
$actor = $_SESSION['player_id'] ?? '';
$targetId = $_POST['target'] ?? '';

if (!in_array($actor, $approvers, true)) {
    respond_json(['error' => 'not_authorized'], 403);
    exit;
}

$players = load_json('players.json');
$votes = load_json('access-votes.json');

$now = time();
$meta = $votes['_meta'] ?? ['promotions' => []];
$recentPromotions = array_values(array_filter($meta['promotions'], function ($ts) use ($now) {
    return is_int($ts) && $ts >= ($now - 3600);
}));

$target = null;
foreach ($players as &$p) {
    if (($p['id'] ?? '') === $targetId) {
        $target = &$p;
        break;
    }
}
unset($p);

if (!$target) {
    respond_json(['error' => 'not_found'], 404);
    exit;
}

$currentLevel = (int) ($target['access_level'] ?? 1);
if ($currentLevel >= 3) {
    respond_json(['error' => 'max_level', 'level' => $currentLevel]);
    exit;
}

$votes[$targetId]['approvals'] = array_values(array_unique(array_merge($votes[$targetId]['approvals'] ?? [], [$actor])));
$approvalCount = count($votes[$targetId]['approvals']);
$leveledUp = false;

if ($approvalCount >= 2 && count($recentPromotions) >= 3) {
    respond_json([
        'error' => 'rate_limited',
        'message' => 'Ліміт підвищень вичерпано. Спробуйте за годину.',
    ], 429);
    exit;
}

if ($approvalCount >= 2) {
    $target['access_level'] = min(3, $currentLevel + 1);
    $leveledUp = $target['access_level'] !== $currentLevel;
    $votes[$targetId]['approvals'] = [];
    if ($leveledUp) {
        $recentPromotions[] = $now;
        append_terminal_message('both', 'info', '[ACCESS] ' . $target['id'] . ' піднято до ' . $target['access_level']);
    }
}

$meta['promotions'] = $recentPromotions;
$votes['_meta'] = $meta;

save_json('access-votes.json', $votes);
save_json('players.json', $players);

respond_json([
    'ok' => true,
    'approvals' => $approvalCount,
    'leveled_up' => $leveledUp,
    'new_level' => $target['access_level'],
]);
