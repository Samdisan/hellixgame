<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$timer = timer_status();
$phaseData = current_phase();
$phases = $phaseData['phases'] ?? [];
$active = $phaseData['active'] ?? [];
$activeIndex = [];
foreach ($active as $record) {
    if (!empty($record['id'])) {
        $activeIndex[$record['id']] = $record;
    }
}

$quests = load_json('quests.json');
$questLabels = [];
foreach ($quests as $quest) {
    $questLabels[$quest['id']] = $quest['label'] ?? $quest['id'];
}

$elapsed = $timer['elapsed'] ?? 0;
$remaining = $timer['remaining'] ?? 0;

$phaseSchedule = [];
foreach ($phases as $phase) {
    $window = $phase['time_window'] ?? [];
    $start = isset($window['planned_start_elapsed_sec']) ? (int) $window['planned_start_elapsed_sec'] : null;
    $end = isset($window['planned_end_elapsed_sec']) ? (int) $window['planned_end_elapsed_sec'] : null;
    $status = 'pending';
    if (!empty($activeIndex[$phase['id']])) {
        $status = 'active';
    } elseif ($end !== null && $elapsed > $end) {
        $status = 'past';
    } elseif ($start !== null && $elapsed < $start) {
        $status = 'upcoming';
    }
    $phaseSchedule[] = [
        'id' => $phase['id'],
        'title' => $phase['title'] ?? ($phase['label'] ?? $phase['id']),
        'start' => $start,
        'end' => $end,
        'status' => $status,
        'notes' => $phase['notes'] ?? ($phase['description'] ?? ''),
    ];
}

$timeTriggers = $timer['time_triggers'] ?? [];

include __DIR__ . '/../partials/header.php';
?>
<section class="panel" data-live-timer>
    <div class="glitch-overlay"></div>
    <div class="flex between align-center" style="gap:12px; flex-wrap:wrap;">
        <div>
            <h1>Розклад фаз і квестів</h1>
            <p class="muted">Живий перегляд запланованих вікон і time-тригерів з урахуванням глобального таймера.</p>
        </div>
        <div class="timeline-item">
            <div class="muted">Глобальний час</div>
            <div class="phase-badge" data-timer-status><?php echo strtoupper($timer['state'] ?? '—'); ?></div>
            <div class="micro">Минуло: <span data-timer-elapsed><?php echo human_time((int)$elapsed); ?></span></div>
            <div class="micro">Залишилось: <span data-timer-remaining><?php echo human_time((int)$remaining); ?></span></div>
        </div>
    </div>
</section>

<section class="panel">
    <h2>Фази за таймером</h2>
    <?php if (empty($phaseSchedule)): ?>
        <p class="muted">Фази ще не додано.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>ID</th><th>Назва</th><th>Заплановано</th><th>Статус</th><th>Опис</th></tr></thead>
            <tbody>
                <?php foreach ($phaseSchedule as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?></td>
                        <td>
                            <?php if ($row['start'] !== null): ?>
                                старт: <?php echo human_time((int)$row['start']); ?>
                            <?php endif; ?>
                            <?php if ($row['end'] !== null): ?>
                                <div class="micro muted">кінець: <?php echo human_time((int)$row['end']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge level"><?php echo strtoupper($row['status']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['notes'], ENT_QUOTES); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Квести з часовими тригерами</h2>
    <?php if (empty($timeTriggers)): ?>
        <p class="muted">Немає time-тригерів.</p>
    <?php else: ?>
        <table class="table dense">
            <thead><tr><th>Умова</th><th>Квест</th><th>Статус</th><th>Остання дія</th></tr></thead>
            <tbody>
                <?php foreach ($timeTriggers as $trigger): ?>
                    <?php
                        $conds = [];
                        if ($trigger['elapsed_ge_sec'] !== null && $trigger['elapsed_ge_sec'] !== '') {
                            $conds[] = 'elapsed ≥ ' . human_time((int)$trigger['elapsed_ge_sec']);
                        }
                        if ($trigger['remaining_le_sec'] !== null && $trigger['remaining_le_sec'] !== '') {
                            $conds[] = 'remaining ≤ ' . human_time((int)$trigger['remaining_le_sec']);
                        }
                        $questLabel = $questLabels[$trigger['quest_id']] ?? ($trigger['quest_id'] ?? '');
                        $fired = !empty($trigger['fired']);
                        $firedAt = $trigger['fired_at'] ?? null;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars(implode(' & ', $conds), ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($questLabel, ENT_QUOTES); ?></td>
                        <td><span class="badge level"><?php echo $fired ? 'FIRED' : 'PENDING'; ?></span></td>
                        <td class="micro muted"><?php echo $firedAt ? htmlspecialchars($firedAt, ENT_QUOTES) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
