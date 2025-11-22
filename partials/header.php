<?php
require_once __DIR__ . '/../includes/helpers.php';

$phaseData = $phaseData ?? current_phase();
$currentPhaseId = $phaseData['current'] ?? null;
$currentPhaseConfig = null;
$currentPhaseOutcome = null;
foreach ($phaseData['phases'] ?? [] as $phaseCfg) {
    if (($phaseCfg['id'] ?? null) === $currentPhaseId) {
        $currentPhaseConfig = $phaseCfg;
        $currentPhaseOutcome = $phaseCfg['outcome'] ?? null;
        break;
    }
}

$intensity = $currentPhaseConfig['ui_intensity'] ?? null;
$bodyClasses = ['helix-shell'];
$isRepairedFailure = $currentPhaseId === 'PH_LIFEFAIL' && $currentPhaseOutcome === 'repaired';

if ($currentPhaseId && !$isRepairedFailure) {
    $bodyClasses[] = 'phase-' . strtolower($currentPhaseId);
}
if ($intensity && !$isRepairedFailure) {
    $bodyClasses[] = 'intensity-' . strtolower($intensity);
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HELIX ECHELON — Command Interface</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <script defer src="/assets/js/main.js"></script>
</head>
<body class="<?php echo htmlspecialchars(implode(' ', $bodyClasses), ENT_QUOTES); ?>">
<header class="top-bar">
    <div class="logo">HELIX ECHELON</div>
    <nav>
        <a href="/index.php">Головна</a>
        <a href="/expeditions.php">Експедиції</a>
        <a href="/protocols.php">Протоколи</a>
        <a href="/terminal.php">Термінал</a>
        <?php if (!empty($_SESSION['access_type']) && $_SESSION['access_type'] === 'player'): ?>
            <a class="cabinet-link" href="/player.php">До кабінету</a>
        <?php endif; ?>
    </nav>
</header>
<?php if (!empty($_SESSION['access_type']) && $_SESSION['access_type'] === 'admin'): ?>
    <nav class="admin-nav">
        <a href="/admin/admin.php">Адмін-хаб</a>
        <a href="/admin/admin-phases.php">Фази & квести</a>
        <a href="/admin/admin-goals.php">Цілі</a>
        <a href="/admin/admin-alerts.php">Оповіщення</a>
        <a href="/admin/admin-terminal.php">Адмін-термінал</a>
        <a href="/admin/admin-protocols.php">Протоколи</a>
        <a href="/admin/admin-players.php">Гравці</a>
        <a href="/admin/admin-diagnostics.php">Діагностика</a>
    </nav>
    <?php
        $navTimer = $timer ?? timer_status();
        $navPhase = $phase ?? ($phaseData ?? current_phase());
        $navPhaseMeta = $navPhase['current_meta'] ?? [];
        $navNext = $navPhase['next_phase']['id'] ?? null;
        $activePhases = $navPhase['active'] ?? [];
    ?>
    <div class="admin-timebar" data-live-timer>
        <div class="time-pill">
            <div class="micro muted">Глобальний час</div>
            <div class="pill-line">
                <span class="pill-label">Статус</span>
                <span data-timer-status><?php echo strtoupper($navTimer['state'] ?? '—'); ?></span>
            </div>
            <div class="pill-line">Минуло: <span data-timer-elapsed><?php echo human_time((int) ($navTimer['elapsed'] ?? 0)); ?></span></div>
            <div class="pill-line">Залишилось: <span data-timer-remaining><?php echo human_time((int) ($navTimer['remaining'] ?? 0)); ?></span></div>
        </div>
        <div class="time-pill">
            <div class="micro muted">Поточна фаза</div>
            <div class="pill-line">
                <span class="pill-label"><?php echo htmlspecialchars($navPhase['current'] ?? '—', ENT_QUOTES); ?></span>
                <span class="muted">→ <?php echo htmlspecialchars($navNext ?? '—', ENT_QUOTES); ?></span>
            </div>
            <div class="pill-line">У фазі: <span data-phase-elapsed><?php echo human_time((int) ($navPhaseMeta['elapsed_sec'] ?? 0)); ?></span></div>
            <div class="pill-line">До кінця фази: <span data-phase-remaining><?php echo isset($navPhaseMeta['remaining_sec']) ? human_time((int)$navPhaseMeta['remaining_sec']) : '—'; ?></span></div>
            <div class="pill-line">До наступної: <span data-phase-next><?php echo isset($navPhaseMeta['to_next_sec']) ? human_time((int)$navPhaseMeta['to_next_sec']) : '—'; ?></span></div>
        </div>
        <div class="time-pill time-pill--stacked">
            <div class="micro muted">Одночасні фази</div>
            <div class="active-phase-list" data-active-phases>
                <?php if (!empty($activePhases)): ?>
                    <?php foreach ($activePhases as $ap): ?>
                        <div class="phase-chip">
                            <span><?php echo htmlspecialchars($ap['id'], ENT_QUOTES); ?></span>
                            <span class="micro muted"><?php echo human_time((int) ($ap['remaining_sec'] ?? 0)); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="micro muted">—</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php echo render_glitch_hint(); ?>
<main class="page">
