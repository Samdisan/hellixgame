<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('player');

$message = trim($_POST['message'] ?? '');
if ($message === '') {
    respond_json(['error' => 'empty_message'], 400);
    exit;
}

$playerId = $_SESSION['player_id'] ?? 'UNKNOWN';
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

respond_json(['status' => 'queued']);
