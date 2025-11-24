<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$metrics = load_json('life-support.json');
if (!is_array($metrics)) {
    $metrics = [];
}
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

$count = count($metrics);
?>
<section class="panel panel--life-admin">
    <div class="panel__header panel__header--stacked">
        <div>
            <p class="eyebrow">Показники життєзабезпечення</p>
            <h1>Редактор сенсорів</h1>
            <p class="muted">Керуйте потоками кисню, напруги та тиску, що відображаються на публічній сторінці систем.</p>
        </div>
        <div class="panel__header-actions">
            <?php if ($notice): ?>
                <span class="badge level">✔ <?php echo htmlspecialchars($notice, ENT_QUOTES); ?></span>
            <?php endif; ?>
            <span class="pill pill--ghost">Сенсорів: <?php echo $count; ?></span>
        </div>
    </div>

    <div class="life-admin__grid">
        <div class="life-admin__card">
            <div class="life-admin__card-title">Статуси каналів</div>
            <p class="muted">Сенсори публікуються в реальному часі. При активній фазі збою (PH_LIFEFAIL) значення падають до меж збою.</p>
            <div class="life-admin__chips">
                <?php foreach ($metrics as $metric): ?>
                    <div class="chip">
                        <span class="chip__label"><?php echo htmlspecialchars($metric['label'] ?? $metric['id'], ENT_QUOTES); ?></span>
                        <span class="micro muted"><?php echo htmlspecialchars(($metric['fail'] ?? 0) . ' ' . ($metric['unit'] ?? ''), ENT_QUOTES); ?> → збій</span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($metrics)): ?>
                    <div class="muted micro">Додайте перший сенсор, щоб показати його гравцям.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="life-admin__card life-admin__card--accent">
            <div class="life-admin__card-title">Поради з оновлення</div>
            <ul class="muted list">
                <li>Тримайте <strong>Норму</strong> і <strong>Збій</strong> у тих самих одиницях, що й гравці бачать на /life-support.php.</li>
                <li>Додавайте короткий <strong>опис</strong>, щоб майстрам було видно сенс каналу.</li>
                <li>Оновлення логуються в адмін-термінал із міткою <strong>[LS]</strong>.</li>
            </ul>
        </div>
    </div>

    <form method="post" class="form-grid life-admin__form">
        <div class="table table--compact table--frosted">
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
