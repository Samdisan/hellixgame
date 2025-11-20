<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$protocols = load_json('protocols.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle'])) {
        $id = $_POST['toggle'];
        foreach ($protocols as &$protocol) {
            if ($protocol['id'] === $id) {
                $field = $_POST['field'];
                $protocol[$field] = !$protocol[$field];
                append_terminal_message('admin_terminal', 'info', "[PROTOCOL] {$field} переключено для {$id}");
            }
        }
        unset($protocol);
    } elseif (isset($_POST['new_protocol'])) {
        $protocols[] = [
            'id' => $_POST['id'],
            'label' => $_POST['label'],
            'description' => $_POST['description'],
            'content' => $_POST['content'],
            'level' => (int) $_POST['level'],
            'public' => !empty($_POST['public']),
            'phase' => $_POST['phase'],
            'active' => !empty($_POST['active']),
            'publish_time' => gmdate('c')
        ];
        append_terminal_message('admin_terminal', 'protocol', 'Додано протокол ' . $_POST['id']);
    }
    save_json('protocols.json', $protocols);
    header('Location: admin-protocols.php');
    exit;
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Протоколи системи</h1>
    <p class="muted">Таблиця секретних документів. Створюйте, вмикайте, оголошуйте через термінал — тут ви формуєте офіційну правду станції.</p>
    <table class="table">
        <thead><tr><th>ID</th><th>Label</th><th>Level</th><th>Public</th><th>Active</th><th>Фаза</th><th>Дії</th></tr></thead>
        <tbody>
            <?php foreach ($protocols as $protocol): ?>
                <tr>
                    <td><?php echo htmlspecialchars($protocol['id'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($protocol['label'], ENT_QUOTES); ?></td>
                    <td><?php echo (int)$protocol['level']; ?></td>
                    <td><?php echo !empty($protocol['public']) ? 'yes' : 'no'; ?></td>
                    <td><?php echo !empty($protocol['active']) ? 'yes' : 'no'; ?></td>
                    <td><?php echo htmlspecialchars($protocol['phase'], ENT_QUOTES); ?></td>
                    <td>
                        <form class="inline" method="post">
                            <input type="hidden" name="field" value="public">
                            <button class="button secondary" name="toggle" value="<?php echo htmlspecialchars($protocol['id'], ENT_QUOTES); ?>" type="submit">Public</button>
                        </form>
                        <form class="inline" method="post">
                            <input type="hidden" name="field" value="active">
                            <button class="button secondary" name="toggle" value="<?php echo htmlspecialchars($protocol['id'], ENT_QUOTES); ?>" type="submit">Active</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<section class="panel">
    <h2>Додати протокол</h2>
    <form method="post">
        <input type="hidden" name="new_protocol" value="1">
        <div class="grid cols-2">
            <div>
                <label>ID<br><input class="form-control" name="id" required></label>
            </div>
            <div>
                <label>Label<br><input class="form-control" name="label" required></label>
            </div>
            <div>
                <label>Level<br><input class="form-control" type="number" name="level" value="1" min="1" max="5"></label>
            </div>
            <div>
                <label>Фаза<br><input class="form-control" name="phase" value="PH_INTRO"></label>
            </div>
        </div>
        <label>Опис<br><textarea class="form-control" name="description" rows="2"></textarea></label>
        <label>Контент<br><textarea class="form-control" name="content" rows="3"></textarea></label>
        <div style="margin-top:8px;">
            <label><input type="checkbox" name="public" value="1"> Public</label>
            <label style="margin-left:12px;"><input type="checkbox" name="active" value="1" checked> Active</label>
        </div>
        <button class="button" type="submit">Створити</button>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
