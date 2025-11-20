<?php
require_once __DIR__ . '/includes/helpers.php';
$phase = current_phase();
$phaseId = strtolower($phase['current'] ?? '');
$timer = timer_status();
include __DIR__ . '/partials/header.php';
?>
<section class="panel phase-<?php echo htmlspecialchars(trim($phaseId, '_'), ENT_QUOTES); ?>" data-live-timer>
    <div class="glitch-overlay"></div>
    <h1>Термінал станції</h1>
    <div class="terminal-meta">
        <div class="meta-block">
            <div class="badge">Глобальний таймер</div>
            <div class="meta-line">Минуло: <span data-timer-elapsed><?php echo human_time((int) ($timer['elapsed'] ?? 0)); ?></span></div>
            <div class="meta-line">Залишилось: <span data-timer-remaining><?php echo human_time((int) ($timer['remaining'] ?? 0)); ?></span></div>
        </div>
        <?php if (!empty($phase['current_meta']['remaining_sec'])): ?>
            <div class="meta-block">
                <div class="badge">Фаза</div>
                <div class="meta-line"><?php echo htmlspecialchars($phase['current'] ?? '—', ENT_QUOTES); ?></div>
                <div class="meta-line">Зворотній відлік фази: <span data-phase-remaining><?php echo human_time((int) $phase['current_meta']['remaining_sec']); ?></span></div>
            </div>
        <?php endif; ?>
    </div>
    <div class="terminal-hero" style="margin:12px 0;">
        <div class="terminal-line line-info"><span class="muted">[STREAM]</span><span class="badge">LIVE</span><span>Повідомлення системи, аварійні сигнали, витоки даних, галюцинації станції.</span></div>
        <div class="terminal-line line-warning"><span class="muted">[PHASE]</span><span class="badge">REACTIVE</span><span>Стиль відображення залежить від фази: спокій, спалах, карантин, фінал.</span></div>
    </div>
    <div class="terminal terminal-feed"></div>
    <div class="glitch-hint">Дані можуть зникати або перемішуватися — термінал живе власним життям.</div>
    <div class="overlay-text">live core</div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
