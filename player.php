<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');

$player = find_player($_SESSION['player_id']);
if (!$player) {
    header('Location: login.php');
    exit;
}

$timer = timer_status();
$messages = array_filter(load_json('terminal-messages.json'), function ($row) {
    return $row['target'] === 'public_terminal';
});
usort($messages, fn($a, $b) => strtotime($b['timestamp']) <=> strtotime($a['timestamp']));
$messages = array_slice($messages, 0, 5);
include __DIR__ . '/partials/header.php';
?>
<section class="grid cols-2">
    <div class="panel">
        <div class="glitch-overlay"></div>
        <h1>Панель гравця</h1>
        <p class="muted">Ваш канал активний. Код доступу: <?php echo htmlspecialchars($_SESSION['access_code'], ENT_QUOTES); ?>.</p>
        <div class="timeline-item">
            <strong><?php echo htmlspecialchars($player['name'], ENT_QUOTES); ?></strong> — <?php echo htmlspecialchars($player['role'], ENT_QUOTES); ?><br>
            <span class="badge level">Рівень доступу: <?php echo (int)$player['access_level']; ?></span>
            <div class="muted">Фракція: <?php echo htmlspecialchars(strtoupper($player['faction']), ENT_QUOTES); ?></div>
        </div>
        <div style="margin-top:12px;" class="quick-actions">
            <a class="button" href="protocols-player.php">Мої протоколи</a>
            <a class="button secondary" href="expeditions.php">Експедиції</a>
            <a class="button secondary" href="terminal.php">Термінал</a>
        </div>
        <div class="overlay-text">personal console online</div>
    </div>
    <div class="panel">
        <h2>Глобальний таймер</h2>
        <div class="timeline-item">
            <div>Статус: <span class="badge level"><?php echo strtoupper($timer['state']); ?></span></div>
            <div>Минуло: <?php echo human_time((int) $timer['elapsed']); ?></div>
            <div>Залишилось: <?php echo human_time((int) $timer['remaining']); ?></div>
        </div>
        <div class="muted">Тривалість: 12 годин (43200 секунд)</div>
        <div class="glitch-hint">Система знімає показники щосекунди: час — спільний ресурс.</div>
    </div>
</section>

<section class="panel">
    <h2>Останні зміни на станції</h2>
    <div class="terminal">
        <?php foreach ($messages as $msg): ?>
            <div class="terminal-line line-<?php echo htmlspecialchars($msg['type'], ENT_QUOTES); ?>">
                <span class="muted"><?php echo date('H:i', strtotime($msg['timestamp'])); ?></span>
                <span class="badge"><?php echo strtoupper($msg['type']); ?></span>
                <span><?php echo htmlspecialchars($msg['message'], ENT_QUOTES); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="glitch-hint">Панель фіксує тільки останні тривоги — більше в живому терміналі.</div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
