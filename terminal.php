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
            <div class="badge">Зворотний відлік</div>
            <div class="meta-line meta-line--timer" data-timer-remaining>
                <?php echo human_time((int) ($timer['remaining'] ?? 0)); ?>
            </div>
        </div>
        <div class="meta-block">
            <div class="badge">Активні фази</div>
            <div class="active-phase-list" data-active-phases>
                <?php foreach (($phase['active'] ?? []) as $ap): ?>
                    <div class="phase-chip"><?php echo htmlspecialchars($ap['id'], ENT_QUOTES); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="terminal-hero" style="margin:12px 0;">
        <div class="terminal-line line-info"><span class="muted">[STREAM]</span><span class="badge">LIVE</span><span>Повідомлення системи, аварійні сигнали, витоки даних, галюцинації станції.</span></div>
        <div class="terminal-line line-warning"><span class="muted">[PHASE]</span><span class="badge">REACTIVE</span><span>Стиль відображення залежить від фази: спокій, спалах, карантин, фінал.</span></div>
    </div>
    <div class="terminal terminal-feed"></div>
    <?php if (($_SESSION['access_type'] ?? '') === 'player'): ?>
    <form class="terminal-input" data-player-terminal-form>
        <label for="player-terminal-message" class="muted micro">Надіслати сигнал у термінал (бачать майстри)</label>
        <div class="input-row">
            <input id="player-terminal-message" name="message" maxlength="240" placeholder="Ваше повідомлення..." required>
            <button class="button" type="submit">Надіслати</button>
        </div>
        <div class="muted micro" data-terminal-status></div>
    </form>
    <?php endif; ?>
    <div class="glitch-hint">Дані можуть зникати або перемішуватися — термінал живе власним життям.</div>
    <div class="overlay-text">live core</div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
