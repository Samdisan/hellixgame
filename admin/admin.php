<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$phase = current_phase();
$timer = timer_status();
$messages = array_slice(array_reverse(load_json('terminal-messages.json')), 0, 8);
$protocols = load_json('protocols.json');
$players = load_json('players.json');

$activeProtocols = count(array_filter($protocols, fn($p) => !empty($p['active'])));
$statusCounts = [];
foreach ($players as $player) {
    $statusCounts[$player['status']] = ($statusCounts[$player['status']] ?? 0) + 1;
}
include __DIR__ . '/../partials/header.php';
?>
<section class="panel" data-live-timer>
    <div class="glitch-overlay"></div>
    <h1>Головний командний центр</h1>
    <p class="muted">Ви на містку корабля HELIX. Тут сходяться фаза, час, статуси, протоколи — все, що рухає гру.</p>
    <div class="admin-hero">
        <div class="protocol-card">
            <div class="badge level">Фаза</div>
            <div class="phase-badge"><?php echo htmlspecialchars($phase['current'], ENT_QUOTES); ?></div>
            <div class="glitch-hint">Перемикання фаз запускає каскади подій.</div>
        </div>
        <div class="protocol-card">
            <div class="badge level">Глобальний час</div>
            <div>Статус: <span data-timer-status><?php echo strtoupper($timer['state']); ?></span></div>
            <div>Минуло: <span data-timer-elapsed><?php echo human_time((int)$timer['elapsed']); ?></span></div>
            <div>Залишилось: <span data-timer-remaining><?php echo human_time((int)$timer['remaining']); ?></span></div>
        </div>
        <div class="protocol-card">
            <div class="badge level">Активні протоколи</div>
            <div style="font-size:24px; font-weight:700;"><?php echo $activeProtocols; ?></div>
            <div class="glitch-hint">Керуйте стіною оголошень станції.</div>
        </div>
    </div>
    <div class="grid cols-3" style="margin-top:16px;">
        <?php foreach ($statusCounts as $status => $count): ?>
            <div class="protocol-card">
                <div class="badge"><?php echo strtoupper($status); ?></div>
                <div><?php echo $count; ?> персонажів</div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="overlay-text">bridge online</div>
</section>

<section class="panel">
    <h2>Останні події</h2>
    <div class="terminal">
        <?php foreach ($messages as $msg): ?>
            <div class="terminal-line line-<?php echo htmlspecialchars($msg['type'], ENT_QUOTES); ?>">
                <span class="muted"><?php echo date('H:i:s', strtotime($msg['timestamp'])); ?></span>
                <span class="badge"><?php echo strtoupper($msg['target']); ?></span>
                <span><?php echo htmlspecialchars($msg['message'], ENT_QUOTES); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
