<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');
$quests = load_json('quests.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    foreach ($quests as &$quest) {
        if ($quest['id'] === $id) {
            if (isset($_POST['favorite'])) {
                $quest['favorite'] = !$quest['favorite'];
            }
            if (isset($_POST['run'])) {
                append_terminal_message('admin_terminal', 'protocol', "Квест {$id} запущено вручну.");
            }
        }
    }
    unset($quest);
    save_json('quests.json', $quests);
    header('Location: admin-quests.php');
    exit;
}
include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Квестова карта</h1>
    <p class="muted">Сценарний реактор: кожен квест — модуль, що запускає події. Запускайте вручну, позначайте favorites для швидкого доступу.</p>
    <table class="table">
        <thead><tr><th>ID</th><th>Label</th><th>Фаза</th><th>Actions</th><th>Favorite</th><th>Дії</th></tr></thead>
        <tbody>
            <?php foreach ($quests as $quest): ?>
                <tr>
                    <td><?php echo htmlspecialchars($quest['id'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($quest['label'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($quest['phase'], ENT_QUOTES); ?></td>
                    <td><?php echo implode(', ', $quest['actions']); ?></td>
                    <td><?php echo !empty($quest['favorite']) ? 'yes' : 'no'; ?></td>
                    <td>
                        <form class="inline" method="post">
                            <button class="button secondary" name="run" value="1">Run</button>
                            <button class="button secondary" name="favorite" value="1">Favorite</button>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($quest['id'], ENT_QUOTES); ?>">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
