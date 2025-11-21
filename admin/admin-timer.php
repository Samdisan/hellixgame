<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$timer = timer_status();
include __DIR__ . '/../partials/header.php';
?>
<section class="panel" data-live-timer>
    <div class="glitch-overlay"></div>
    <h1>Глобальний таймер</h1>
    <p class="muted">Панель керування реактором часу. Кнопки Start/Pause/Resume/Reset змінюють долю станції в реальному часі.</p>
    <div class="grid cols-3">
        <div class="timeline-item">
            <div class="muted">Статус</div>
            <div class="phase-badge" data-timer-status><?php echo strtoupper($timer['state']); ?></div>
        </div>
        <div class="timeline-item">
            <div>Минуло: <span data-timer-elapsed><?php echo human_time((int)$timer['elapsed']); ?></span></div>
            <div>Залишилось: <span data-timer-remaining><?php echo human_time((int)$timer['remaining']); ?></span></div>
        </div>
        <div class="timeline-item">
            <div>Останнє оновлення</div>
            <div class="muted"><?php echo htmlspecialchars($timer['raw']['last_updated'] ?? '—', ENT_QUOTES); ?></div>
        </div>
    </div>
    <form method="post" action="/api/update-timer.php" class="quick-actions" style="margin-top:12px;">
        <input type="hidden" name="redirect" value="/admin/admin-timer.php">
        <button class="button" name="action" value="start" type="submit">Start</button>
        <button class="button secondary" name="action" value="pause" type="submit">Pause</button>
        <button class="button secondary" name="action" value="resume" type="submit">Resume</button>
        <button class="button secondary" name="action" value="reset" type="submit">Reset</button>
    </form>
    <div class="overlay-text">time reactor</div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
