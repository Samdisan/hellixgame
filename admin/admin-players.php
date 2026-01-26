<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');
$players = load_json('players.json');
$files = load_json('personal-files.json');
$filesById = [];
foreach ($files as $file) {
    $filesById[$file['id']] = $file;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    foreach ($players as &$player) {
        if ($player['id'] === $id) {
            $player['access_level'] = (int) $_POST['access_level'];
            $player['status'] = $_POST['status'];
            $player['infected'] = !empty($_POST['infected']);
            append_terminal_message('admin_terminal', 'info', "[PLAYER] Оновлено {$id}");
        }
    }
    unset($player);

    $photo = trim($_POST['photo'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    if (!isset($filesById[$id])) {
        $filesById[$id] = [
            'id' => $id,
            'summary' => '',
            'photo' => ''
        ];
    }
    if ($photo !== '') {
        $filesById[$id]['photo'] = $photo;
    }
    if ($summary !== '') {
        $filesById[$id]['summary'] = $summary;
    }

    save_json('players.json', $players);
    save_json('personal-files.json', array_values($filesById));
    header('Location: admin-players.php');
    exit;
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Персонал і гравці</h1>
    <p class="muted">Панель впливу на сюжет: хто активний, хто зник, хто в карантині. Піднімайте або знижуйте рівні доступу, змінюйте статуси й вказуйте де лежать досьє.</p>
    <table class="table">
        <thead><tr><th>Ім'я</th><th>Фракція</th><th>Доступ</th><th>Статус</th><th>Заражений</th><th>Фото досьє</th><th>Опис</th><th>Дії</th></tr></thead>
        <tbody>
            <?php foreach ($players as $player): ?>
                <?php $file = $filesById[$player['id']] ?? ['photo' => '', 'summary' => '']; ?>
                <?php $formId = 'edit-' . htmlspecialchars($player['id'], ENT_QUOTES); ?>
                <form id="<?php echo $formId; ?>" method="post"></form>
                <tr>
                    <td><?php echo htmlspecialchars($player['name'], ENT_QUOTES); ?><input form="<?php echo $formId; ?>" type="hidden" name="id" value="<?php echo htmlspecialchars($player['id'], ENT_QUOTES); ?>"></td>
                    <td><?php echo strtoupper($player['faction']); ?></td>
                    <td><input form="<?php echo $formId; ?>" class="form-control compact" type="number" name="access_level" value="<?php echo (int)$player['access_level']; ?>" min="1" max="5"></td>
                    <td>
                        <select form="<?php echo $formId; ?>" class="form-control compact" name="status">
                            <?php foreach (['active','search','quarantine','unknown'] as $status): ?>
                                <option value="<?php echo $status; ?>" <?php if ($status === $player['status']) echo 'selected'; ?>><?php echo $status; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <label class="micro">
                            <input form="<?php echo $formId; ?>" type="checkbox" name="infected" value="1" <?php if (!empty($player['infected'])) echo 'checked'; ?>>
                            інфікований
                        </label>
                    </td>
                    <td>
                        <input form="<?php echo $formId; ?>" class="form-control" type="text" name="photo" value="<?php echo htmlspecialchars($file['photo'], ENT_QUOTES); ?>" placeholder="/assets/img/dossiers/ID.jpg">
                        <div class="micro muted">Шлях на сервері до портрету.</div>
                    </td>
                    <td>
                        <textarea form="<?php echo $formId; ?>" class="form-control" name="summary" rows="2" placeholder="Коротка нотатка по персонажу"><?php echo htmlspecialchars($file['summary'], ENT_QUOTES); ?></textarea>
                    </td>
                    <td><button form="<?php echo $formId; ?>" class="button secondary" type="submit">Зберегти</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
