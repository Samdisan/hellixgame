<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$phaseData = current_phase();
$current = $phaseData['current'];
$phases = $phaseData['phases'];
$ids = array_column($phases, 'id');
$currentIndex = array_search($current, $ids, true);
include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Керування фазами</h1>
    <p class="muted">Чотири блоки станції: INTRO, OUTBREAK, QUARANTINE, FINAL. Тисніть тумблери — запускаєте катастрофи.</p>
    <table class="table">
        <thead><tr><th>ID</th><th>Назва</th><th>Опис</th><th>Статус</th><th></th></tr></thead>
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
<?php include __DIR__ . '/../partials/footer.php'; ?>
