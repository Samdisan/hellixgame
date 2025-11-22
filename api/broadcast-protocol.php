<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('player');

$protocolId = trim($_POST['protocol'] ?? '');
if ($protocolId === '') {
    respond_json(['error' => 'missing_protocol'], 400);
    exit;
}

$player = find_player($_SESSION['player_id']);
if (!$player) {
    respond_json(['error' => 'player_not_found'], 403);
    exit;
}

$protocols = load_json('protocols.json');
$players = load_json('players.json');

$baseIndex = null;
$redactedIndex = null;
foreach ($protocols as $idx => $protocol) {
    if (($protocol['id'] ?? '') === $protocolId) {
        $baseIndex = $idx;
    }
}

if ($baseIndex === null) {
    respond_json(['error' => 'protocol_not_found'], 404);
    exit;
}

$base = $protocols[$baseIndex];
$flags = $base['flags'] ?? [];

$shareAllowed = $flags['share_allowed'] ?? [];
if (empty($flags['shareable_redacted']) || !in_array($player['id'], $shareAllowed, true)) {
    respond_json(['error' => 'not_permitted'], 403);
    exit;
}

if (!empty($flags['broadcasted'])) {
    respond_json(['status' => 'already_sent']);
    exit;
}

$redactedId = $flags['redacted_variant'] ?? '';
if ($redactedId === '') {
    respond_json(['error' => 'missing_redacted_variant'], 500);
    exit;
}

if ($redactedIndex === null) {
    foreach ($protocols as $idx => $protocol) {
        if (($protocol['id'] ?? '') === $redactedId) {
            $redactedIndex = $idx;
            break;
        }
    }
    if ($redactedIndex === null) {
        respond_json(['error' => 'redacted_not_found'], 404);
        exit;
    }
}

$allPlayers = array_column($players, 'id');

$protocols[$redactedIndex]['active'] = true;
$protocols[$redactedIndex]['allowed_players'] = $allPlayers;
$protocols[$redactedIndex]['announce_in_terminal'] = true;
$protocols[$redactedIndex]['publish_time'] = 'broadcast';

$protocols[$baseIndex]['flags']['broadcasted'] = true;
$protocols[$baseIndex]['flags']['broadcasted_at'] = gmdate('c');

save_json('protocols.json', $protocols);
append_terminal_message('both', 'protocol', '[ILARIA] ILR-BIOSEC-PHASE3 розіслано: доступна ушкоджена копія для всієї станції.');

respond_json([
    'status' => 'sent',
    'redacted_id' => $redactedId,
]);
