<?php
require_once __DIR__ . '/includes/helpers.php';
$phase = current_phase();
$phaseId = strtolower($phase['current'] ?? '');
$phaseTagline = [
    'ph_intro' => 'INTRO → спокій + дрібні глічі',
    'ph_outbreak' => 'OUTBREAK → тривога + попередження',
    'ph_quarantine' => 'QUARANTINE → червоні сигнали + зовнішній тиск',
    'ph_final' => 'FINAL → критичні системні збої',
][$phaseId] ?? 'Режим не визначено';
include __DIR__ . '/partials/header.php';
?>
<section class="panel phase-<?php echo htmlspecialchars(trim($phaseId, '_'), ENT_QUOTES); ?>">
    <div class="glitch-overlay"></div>
    <h1>Термінал станції</h1>
    <div class="muted">Поточна фаза: <?php echo htmlspecialchars($phase['current'] ?? '—', ENT_QUOTES); ?> · <?php echo htmlspecialchars($phaseTagline, ENT_QUOTES); ?></div>
    <div class="terminal-hero" style="margin:12px 0;">
        <div class="terminal-line line-info"><span class="muted">[STREAM]</span><span class="badge">LIVE</span><span>Повідомлення системи, аварійні сигнали, витоки даних, галюцинації станції.</span></div>
        <div class="terminal-line line-warning"><span class="muted">[PHASE]</span><span class="badge">REACTIVE</span><span>Стиль відображення залежить від фази: спокій, спалах, карантин, фінал.</span></div>
    </div>
    <div class="terminal terminal-feed"></div>
    <div class="glitch-hint">Дані можуть зникати або перемішуватися — термінал живе власним життям.</div>
    <div class="overlay-text">live core</div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
