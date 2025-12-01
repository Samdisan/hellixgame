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
    </div>
    <div class="terminal-hero" style="margin:12px 0;">
        <div class="terminal-line line-info"><span class="muted">[STREAM]</span><span class="badge">LIVE</span><span>Повідомлення системи, аварійні сигнали, витоки даних, галюцинації станції.</span></div>
    </div>
    <div class="terminal terminal-feed"></div>
    <?php if (($_SESSION['access_type'] ?? '') === 'player'): ?>
    <form class="terminal-input request-card" data-player-terminal-form>
        <div class="request-head">
            <div class="request-icon">SYS</div>
            <div>
                <div class="muted micro">Заявка в систему</div>
                <div class="request-title">Короткий сигнал для майстрів</div>
            </div>
            <span class="chip chip-live">канал зв'язку</span>
        </div>
        <label for="player-terminal-message" class="sr-only">Текст заявки</label>
        <div class="input-shell">
            <input id="player-terminal-message" name="message" maxlength="240" placeholder="Введіть зміст заявки — до 240 символів" required>
            <button class="button button-glow" type="submit">Надіслати</button>
        </div>
        <div class="muted micro">Повідомлення з'являється у майстрів як службова заявка.</div>
        <div class="status micro" data-terminal-status></div>
    </form>
    <?php endif; ?>
    <div class="glitch-hint">Дані можуть зникати або перемішуватися — термінал живе власним життям.</div>
    <div class="overlay-text">live core</div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
