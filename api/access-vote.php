<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('player');

$approvers = ['PL_STATION_ROSS', 'PL_STATION_CROW_PSY', 'PL_STATION_SATO', 'PL_WHO_PROGRAMMER'];
$actor = $_SESSION['player_id'] ?? '';
$targetId = $_POST['target'] ?? '';

if (!in_array($actor, $approvers, true)) {
    respond_json(['error' => 'not_authorized'], 403);
}

$players = load_json('players.json');
$votes = load_json('access-votes.json');

$now = time();
$meta = $votes['_meta'] ?? ['promotions' => [], 'clicks' => []];
$promotionLedger = $meta['promotions'] ?? [];
$clickLedger = $meta['clicks'] ?? [];
$settings = $meta['settings'] ?? [];

// Backward compatibility: previously an array of timestamps; now per-approver buckets.
if (isset($promotionLedger[0]) && is_int($promotionLedger[0])) {
    $promotionLedger = [$actor => $promotionLedger];
}

if (isset($clickLedger[0]) && is_int($clickLedger[0])) {
    $clickLedger = [$actor => $clickLedger];
}

$actorPromotions = $promotionLedger[$actor] ?? [];
if (!is_array($actorPromotions)) {
    $actorPromotions = [];
}

$actorClicks = $clickLedger[$actor] ?? [];
if (!is_array($actorClicks)) {
    $actorClicks = [];
}

$rossOverrideEnabled = !empty($settings['ross_single_promotion_enabled']);
$rossOverrideCooldown = (int) ($settings['ross_single_promotion_cooldown_sec'] ?? 7200);
$rossOverrideLastUsed = (int) ($settings['ross_single_promotion_last_used'] ?? 0);
$rossOverrideRemaining = max(0, ($rossOverrideLastUsed + $rossOverrideCooldown) - $now);
$whoOverrideEnabled = !empty($settings['who_programmer_single_promotion_enabled']);
$whoOverrideCooldown = (int) ($settings['who_programmer_single_promotion_cooldown_sec'] ?? 7200);
$whoOverrideLastUsed = (int) ($settings['who_programmer_single_promotion_last_used'] ?? 0);
$whoOverrideRemaining = max(0, ($whoOverrideLastUsed + $whoOverrideCooldown) - $now);

$recentPromotions = array_values(array_filter($actorPromotions, function ($ts) use ($now) {
    return is_int($ts) && $ts >= ($now - 3600);
}));

$recentClicks = array_values(array_filter($actorClicks, function ($ts) use ($now) {
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
}

$currentLevel = (int) ($target['access_level'] ?? 1);
if ($currentLevel >= 3) {
    respond_json(['error' => 'max_level', 'level' => $currentLevel]);
}

$votes[$targetId]['approvals'] = array_values(array_unique(array_merge($votes[$targetId]['approvals'] ?? [], [$actor])));
$approvalCount = count($votes[$targetId]['approvals']);
$leveledUp = false;
$rossOverrideUsed = false;
$whoOverrideUsed = false;

if ($actor === 'PL_STATION_ROSS' && $rossOverrideEnabled && $rossOverrideRemaining === 0) {
    $target['access_level'] = min(3, $currentLevel + 1);
    $leveledUp = $target['access_level'] !== $currentLevel;
    $votes[$targetId]['approvals'] = [];
    $rossOverrideUsed = $leveledUp;
    $settings['ross_single_promotion_last_used'] = $now;
    $meta['settings'] = $settings;

    if ($leveledUp) {
        append_terminal_message('both', 'info', '[ACCESS] ' . $target['id'] . ' піднято до ' . $target['access_level'] . ' (Глен Росс)');
    }

    $votes['_meta'] = $meta;
    save_json('access-votes.json', $votes);
    save_json('players.json', $players);

    respond_json([
        'ok' => true,
        'approvals' => 0,
        'leveled_up' => $leveledUp,
        'new_level' => $target['access_level'],
        'remaining' => max(0, 3 - count($recentClicks)),
        'ross_override_used' => $rossOverrideUsed,
        'ross_retry_in' => $rossOverrideCooldown,
        'who_override_used' => $whoOverrideUsed,
        'who_retry_in' => $whoOverrideRemaining,
    ]);
}

if ($actor === 'PL_WHO_PROGRAMMER' && $whoOverrideEnabled && $whoOverrideRemaining === 0) {
    $target['access_level'] = min(3, $currentLevel + 1);
    $leveledUp = $target['access_level'] !== $currentLevel;
    $votes[$targetId]['approvals'] = [];
    $whoOverrideUsed = $leveledUp;
    $settings['who_programmer_single_promotion_last_used'] = $now;
    $meta['settings'] = $settings;

    if ($leveledUp) {
        append_terminal_message('both', 'info', '[ACCESS] ' . $target['id'] . ' піднято до ' . $target['access_level'] . ' (програміст ВООЗ)');
    }

    $votes['_meta'] = $meta;
    save_json('access-votes.json', $votes);
    save_json('players.json', $players);

    respond_json([
        'ok' => true,
        'approvals' => 0,
        'leveled_up' => $leveledUp,
        'new_level' => $target['access_level'],
        'remaining' => max(0, 3 - count($recentClicks)),
        'ross_override_used' => $rossOverrideUsed,
        'ross_retry_in' => $rossOverrideRemaining,
        'who_override_used' => $whoOverrideUsed,
        'who_retry_in' => $whoOverrideCooldown,
    ]);
}

if (count($recentClicks) >= 3) {
    $retryIn = max(0, ($recentClicks[0] + 3600) - $now);
    respond_json([
        'error' => 'rate_limited',
        'message' => 'Ліміт: не більше 3 спроб підвищити доступ за годину.',
        'retry_in' => $retryIn,
        'remaining' => 0,
    ], 429);
}

$recentClicks[] = $now;
$clickLedger[$actor] = $recentClicks;

if ($approvalCount >= 2) {
    $target['access_level'] = min(3, $currentLevel + 1);
    $leveledUp = $target['access_level'] !== $currentLevel;
    $votes[$targetId]['approvals'] = [];
    if ($leveledUp) {
        $recentPromotions[] = $now;
        $promotionLedger[$actor] = $recentPromotions;
        append_terminal_message('both', 'info', '[ACCESS] ' . $target['id'] . ' піднято до ' . $target['access_level']);
    }
}

$meta['promotions'] = $promotionLedger;
$meta['clicks'] = $clickLedger;
$meta['settings'] = $settings;
$votes['_meta'] = $meta;

save_json('access-votes.json', $votes);
save_json('players.json', $players);

respond_json([
    'ok' => true,
    'approvals' => $approvalCount,
    'leveled_up' => $leveledUp,
    'new_level' => $target['access_level'],
    'remaining' => max(0, 3 - count($recentClicks)),
    'ross_override_used' => $rossOverrideUsed,
    'ross_retry_in' => $rossOverrideRemaining,
    'who_override_used' => $whoOverrideUsed,
    'who_retry_in' => $whoOverrideRemaining,
]);
