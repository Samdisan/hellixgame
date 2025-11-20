<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$phase = current_phase();
$timer = timer_status();
$messages = array_slice(array_reverse(load_json('terminal-messages.json')), 0, 8);
$protocols = load_json('protocols.json');
$activeProtocols = count(array_filter($protocols, fn($p) => !empty($p['active'])));
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
            <div class="meta-line">Плин фази: <span data-phase-elapsed><?php echo human_time((int) ($phase['current_meta']['elapsed_sec'] ?? 0)); ?></span></div>
            <div class="meta-line">До завершення: <span data-phase-remaining><?php echo isset($phase['current_meta']['remaining_sec']) ? human_time((int) $phase['current_meta']['remaining_sec']) : '—'; ?></span></div>
            <div class="glitch-hint">Перемикання фаз запускає каскади подій.</div>
        </div>
        <div class="protocol-card">
            <div class="badge level">Глобальний час</div>
            <div>Статус: <span data-timer-status><?php echo strtoupper($timer['state']); ?></span></div>
            <div>Минуло: <span data-timer-elapsed><?php echo human_time((int)$timer['elapsed']); ?></span></div>
            <div>Залишилось: <span data-timer-remaining><?php echo human_time((int)$timer['remaining']); ?></span></div>
            <form method="post" action="/api/update-timer.php" class="quick-actions" style="margin-top:12px;">
                <input type="hidden" name="redirect" value="/admin/admin.php">
                <button class="button" name="action" value="start" type="submit">Start</button>
                <button class="button secondary" name="action" value="pause" type="submit">Pause</button>
                <button class="button secondary" name="action" value="resume" type="submit">Resume</button>
                <button class="button secondary" name="action" value="reset" type="submit">Reset</button>
            </form>
        </div>
        <a class="protocol-card link-card" href="/admin/admin-protocols.php">
            <div class="badge level">Активні протоколи</div>
            <div style="font-size:24px; font-weight:700; color: var(--accent); text-decoration: underline;">Переглянути (<?php echo $activeProtocols; ?>)</div>
            <div class="glitch-hint">Відкрити повний перелік документів.</div>
        </a>
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
