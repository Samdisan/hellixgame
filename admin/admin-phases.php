<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$phaseData = current_phase();
$current = $phaseData['current'];
$phaseStarted = $phaseData['started_elapsed'] ?? 0;
$phases = $phaseData['phases'];
$ids = array_column($phases, 'id');
$currentIndex = array_search($current, $ids, true);
$timer = timer_status();
$phaseRuntime = max(0, ($timer['elapsed'] ?? 0) - $phaseStarted);
$activeProtocols = array_filter(load_json('protocols.json'), function ($protocol) {
    return $protocol['active'] ?? false;
});
include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Керування фазами</h1>
    <p class="muted">Чотири блоки станції: INTRO, OUTBREAK, QUARANTINE, FINAL. Тисніть тумблери — запускаєте катастрофи.</p>
    <table class="table">
        <thead><tr><th>ID</th><th>Назва</th><th>Опис</th><th>Статус</th><th>Час у фазі</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($phases as $idx => $phase): ?>
                <?php
                    if ($idx < $currentIndex) {
                        $status = 'past';
                    } elseif ($idx === $currentIndex) {
                        $status = 'current';
                    } elseif ($idx === $currentIndex + 1) {
                        $status = 'next';
                    } else {
                        $status = 'future';
                    }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($phase['id'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($phase['label'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($phase['description'], ENT_QUOTES); ?></td>
                    <td><span class="badge level"><?php echo strtoupper($status); ?></span></td>
                    <td>
                        <?php if ($phase['id'] === $current): ?>
                            <?php echo human_time((int) $phaseRuntime); ?>
                        <?php elseif (!empty($phase['duration_sec'])): ?>
                            заплановано: <?php echo human_time((int) $phase['duration_sec']); ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($phase['id'] !== $current): ?>
                            <form method="post" action="/api/set-phase.php">
                                <input type="hidden" name="phase" value="<?php echo htmlspecialchars($phase['id'], ENT_QUOTES); ?>">
                                <input type="hidden" name="redirect" value="/admin/admin-phases.php">
                                <button class="button secondary" type="submit">Зробити поточною</button>
                            </form>
                        <?php else: ?>
                            Поточна
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <h2>Активні протоколи</h2>
    <?php if (empty($activeProtocols)): ?>
        <p class="muted">Наразі немає активних протоколів.</p>
    <?php else: ?>
        <div class="chips">
            <?php foreach ($activeProtocols as $protocol): ?>
                <span class="chip">
                    <?php echo htmlspecialchars($protocol['id'] . ' — ' . ($protocol['label'] ?? 'Без назви'), ENT_QUOTES); ?>
                    <small class="muted">рівень <?php echo (int) ($protocol['level'] ?? 0); ?></small>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Додати фазу</h2>
    <form class="stack" method="post" action="/api/add-phase.php">
        <div class="grid two">
            <label>Ідентифікатор
                <input required name="id" placeholder="PH_NEW" />
            </label>
            <label>Назва
                <input required name="label" placeholder="Нова фаза" />
            </label>
        </div>
        <label>Опис
            <textarea required name="description" rows="3" placeholder="Коротке пояснення фази"></textarea>
        </label>
        <div class="grid three">
            <label>Порядок
                <input name="order" type="number" min="1" step="1" placeholder="<?php echo count($phases) + 1; ?>" />
            </label>
            <label>Тривалість (сек)
                <input name="duration_sec" type="number" min="0" step="60" placeholder="900" />
            </label>
            <label>Інтенсивність UI
                <input name="ui_intensity" placeholder="low/medium/high/critical" />
            </label>
        </div>
        <input type="hidden" name="redirect" value="/admin/admin-phases.php" />
        <button class="button" type="submit">Зберегти фазу</button>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
