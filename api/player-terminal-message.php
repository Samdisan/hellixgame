<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('player');

$message = trim($_POST['message'] ?? '');
if ($message === '') {
    respond_json(['error' => 'empty_message'], 400);
    exit;
}

$playerId = $_SESSION['player_id'] ?? 'UNKNOWN';
$players = load_json('players.json');
$player = null;
foreach ($players as $candidate) {
    if (($candidate['id'] ?? '') === $playerId) {
        $player = $candidate;
        break;
    }
}

$prefix = $playerId ? '[' . $playerId . '] ' : '';
append_terminal_message('admin_terminal', 'player', $prefix . $message);

// Special cooperative diagnostic command between станція та ILARIA security.
$normalized = trim($message);
if (preg_match('/^\/run\s+diagnostic\s+(.+)$/i', $normalized, $m)) {
    $code = strtoupper(trim($m[1]));
    if ($code === 'ALPHA-7-ZULU') {
        append_terminal_message(
            'player:' . $playerId,
            'system',
            'ЗВ\'ЯЗОК З ПОВЕРХНЕЮ: НЕСТАБІЛЬНИЙ. ПОМИЛКА 404. СЕРВЕР НЕ ВІДПОВІДАЄ.'
        );
        append_terminal_message(
            'admin_terminal',
            'info',
            "[DIAG] {$playerId} виконав діагностику з кодом ALPHA-7-ZULU"
        );
    }
}

// WHO medical screening: /run check_bio <id>.
if (preg_match('/^\/run\s+check_bio\s+(\S+)$/i', $normalized, $match)) {
    $targetId = strtoupper(trim($match[1]));
    $isWho = ($player['faction'] ?? '') === 'who';

    if (!$isWho) {
        append_terminal_message('player:' . $playerId, 'info', 'Команда доступна лише представникам ВООЗ.');
    } else {
        $alertIds = ['7733'];
        $isAlert = in_array($targetId, $alertIds, true);
        $response = $isAlert
            ? "Суб'єкт {$targetId}: УВАГА! ПІДВИЩЕНИЙ РІВЕНЬ КОРТИЗОЛУ. СЛІДИ НЕВІДОМОГО БІЛКА."
            : "Суб'єкт {$targetId}: ПОКАЗНИКИ В НОРМІ.";

        append_terminal_message('player:' . $playerId, 'system', $response);
        append_terminal_message(
            'admin_terminal',
            $isAlert ? 'warning' : 'info',
            "[BIO] {$playerId} перевірив {$targetId}: " . ($isAlert ? 'анормальні показники' : 'норма')
        );
    }
}

respond_json(['status' => 'queued']);
