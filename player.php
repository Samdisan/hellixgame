<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');

$player = find_player($_SESSION['player_id']);
if (!$player) {
    header('Location: login.php');
    exit;
}

$accessApprovers = ['PL_STATION_ROSS', 'PL_STATION_CROW_PSY', 'PL_STATION_SATO'];
$canManageAccess = in_array($player['id'], $accessApprovers, true);

$timer = timer_status();
$phaseData = current_phase();
$lifeFail = $phaseData['life_support'] ?? null;
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
        <h1>Особиста справа</h1>
        <p class="muted">Ваш канал активний. Код доступу: <?php echo htmlspecialchars($_SESSION['access_code'], ENT_QUOTES); ?>.</p>
        <div class="timeline-item">
            <strong><?php echo htmlspecialchars($player['name'], ENT_QUOTES); ?></strong> — <?php echo htmlspecialchars($player['role'], ENT_QUOTES); ?><br>
            <span class="badge level">Рівень доступу: <?php echo (int)$player['access_level']; ?></span>
            <div class="muted">Фракція: <?php echo htmlspecialchars(strtoupper($player['faction']), ENT_QUOTES); ?></div>
        </div>
        <div style="margin-top:12px;" class="quick-actions">
            <a class="button" href="protocols-player.php">Мої протоколи</a>
            <a class="button" href="goals.php">Цілі</a>
            <?php if ($canManageAccess): ?>
                <a class="button" href="access-levels.php">Рівні доступу</a>
            <?php endif; ?>
            <a class="button" href="life-support.php">Показники</a>
            <a class="button" href="personal-files.php">Особові справи</a>
            <a class="button secondary" href="expeditions.php">Експедиції</a>
            <a class="button secondary" href="terminal.php">Термінал</a>
        </div>
        <div class="overlay-text">personal console online</div>
    </div>
    <div class="panel">
        <h2>Вибуття експедицій через:</h2>
        <div class="timeline-item digital-readout">
            <div class="pill-line"><span class="pill-label">Статус</span> <span class="badge level"><?php echo strtoupper($timer['state']); ?></span></div>
            <div class="pill-line">Минуло: <span><?php echo human_time((int) $timer['elapsed']); ?></span></div>
            <div class="pill-line">Залишилось: <span><?php echo human_time((int) $timer['remaining']); ?></span></div>
        </div>
        <div class="glitch-hint">Система знімає показники раз на 3 секунди: час — спільний ресурс.</div>
    </div>
    <div class="panel" data-live-timer data-lifefail-block <?php echo !empty($lifeFail['active']) ? '' : 'hidden'; ?>>
        <h2>До припинення підтримки життєдіяльності</h2>
        <div class="timeline-item digital-readout">
            <div class="pill-line"><span class="pill-label">Статус</span> <span class="badge level" data-lifefail-status><?php echo !empty($lifeFail['active']) ? 'АКТИВНО' : '—'; ?></span></div>
            <div class="pill-line">Минуло: <span data-lifefail-elapsed><?php echo !empty($lifeFail['active']) && isset($lifeFail['elapsed_sec']) ? human_time((int) $lifeFail['elapsed_sec']) : '—'; ?></span></div>
            <div class="pill-line">Залишилось: <span data-lifefail-remaining><?php echo (!empty($lifeFail['active']) && isset($lifeFail['remaining_sec'])) ? human_time((int) $lifeFail['remaining_sec']) : '—'; ?></span></div>
        </div>
        <div class="glitch-hint">Критичний режим життєзабезпечення — станція працює на межі.</div>
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
