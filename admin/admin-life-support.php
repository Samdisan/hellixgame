<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$metrics = load_json('life-support.json');
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = $_POST['id'] ?? [];
    $labels = $_POST['label'] ?? [];
    $units = $_POST['unit'] ?? [];
    $normals = $_POST['normal'] ?? [];
    $fails = $_POST['fail'] ?? [];
    $maxes = $_POST['max'] ?? [];
    $descriptions = $_POST['description'] ?? [];

    $updated = [];
    foreach ($ids as $idx => $id) {
        $id = trim((string) $id);
        if ($id === '') {
            continue;
        }
        $updated[] = [
            'id' => $id,
            'label' => trim((string) ($labels[$idx] ?? '')),
            'unit' => trim((string) ($units[$idx] ?? '')),
            'normal' => (float) ($normals[$idx] ?? 0),
            'fail' => (float) ($fails[$idx] ?? 0),
            'max' => (float) ($maxes[$idx] ?? 0),
            'description' => trim((string) ($descriptions[$idx] ?? '')),
        ];
    }

    $newId = trim($_POST['new_id'] ?? '');
    if ($newId !== '') {
        $updated[] = [
            'id' => $newId,
            'label' => trim((string) ($_POST['new_label'] ?? '')),
            'unit' => trim((string) ($_POST['new_unit'] ?? '')),
            'normal' => (float) ($_POST['new_normal'] ?? 0),
            'fail' => (float) ($_POST['new_fail'] ?? 0),
            'max' => (float) ($_POST['new_max'] ?? 0),
            'description' => trim((string) ($_POST['new_description'] ?? '')),
        ];
    }

    if (!empty($updated)) {
        save_json('life-support.json', array_values($updated));
        append_terminal_message('admin_terminal', 'system', '[LS] Оновлено показники (' . count($updated) . ')');
        $notice = 'Зміни збережено';
        $metrics = $updated;
    }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="panel__header panel__header--stacked">
        <div>
            <p class="eyebrow">Показники життєзабезпечення</p>
            <h1>Редактор сенсорів</h1>
            <p class="muted">Керуйте значеннями для кисню, напруги, тиску та інших каналів, які бачать гравці на сторінці систем.</p>
        </div>
        <?php if ($notice): ?>
            <div class="badge level">✔ <?php echo htmlspecialchars($notice, ENT_QUOTES); ?></div>
        <?php endif; ?>
    </div>

    <form method="post" class="form-grid">
        <div class="table table--compact">
            <div class="table__header">
                <div>ID</div>
                <div>Назва</div>
                <div>Одиниці</div>
                <div>Норма</div>
                <div>Стан збою</div>
                <div>Макс</div>
                <div>Опис</div>
            </div>
            <?php foreach ($metrics as $idx => $metric): ?>
                <div class="table__row">
                    <div><input name="id[]" value="<?php echo htmlspecialchars($metric['id'], ENT_QUOTES); ?>" required></div>
                    <div><input name="label[]" value="<?php echo htmlspecialchars($metric['label'], ENT_QUOTES); ?>" required></div>
                    <div><input name="unit[]" value="<?php echo htmlspecialchars($metric['unit'], ENT_QUOTES); ?>" required></div>
                    <div><input type="number" step="0.1" name="normal[]" value="<?php echo htmlspecialchars($metric['normal'], ENT_QUOTES); ?>" required></div>
                    <div><input type="number" step="0.1" name="fail[]" value="<?php echo htmlspecialchars($metric['fail'], ENT_QUOTES); ?>" required></div>
                    <div><input type="number" step="0.1" name="max[]" value="<?php echo htmlspecialchars($metric['max'], ENT_QUOTES); ?>" required></div>
                    <div><input name="description[]" value="<?php echo htmlspecialchars($metric['description'], ENT_QUOTES); ?>"></div>
                </div>
            <?php endforeach; ?>
            <div class="table__row table__row--muted">
                <div><input name="new_id" placeholder="новий-id"></div>
                <div><input name="new_label" placeholder="Нова назва"></div>
                <div><input name="new_unit" placeholder="Одиниці"></div>
                <div><input type="number" step="0.1" name="new_normal" placeholder="Норма"></div>
                <div><input type="number" step="0.1" name="new_fail" placeholder="Збій"></div>
                <div><input type="number" step="0.1" name="new_max" placeholder="Макс"></div>
                <div><input name="new_description" placeholder="Опис"></div>
            </div>
        </div>

        <div class="form-actions">
            <button class="button" type="submit">Зберегти показники</button>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
