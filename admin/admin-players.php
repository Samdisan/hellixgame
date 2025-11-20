<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');
$players = load_json('players.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    foreach ($players as &$player) {
        if ($player['id'] === $id) {
            $player['access_level'] = (int) $_POST['access_level'];
            $player['status'] = $_POST['status'];
            append_terminal_message('admin_terminal', 'info', "[PLAYER] Оновлено {$id}");
        }
    }
    unset($player);
    save_json('players.json', $players);
    header('Location: admin-players.php');
    exit;
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Персонал і гравці</h1>
    <p class="muted">Панель впливу на сюжет: хто активний, хто зник, хто в карантині. Піднімайте або знижуйте рівні доступу, змінюйте статуси.</p>
    <table class="table">
        <thead><tr><th>Ім'я</th><th>Фракція</th><th>Доступ</th><th>Статус</th><th>Дії</th></tr></thead>
        <tbody>
            <?php foreach ($players as $player): ?>
                <tr>
                    <td><?php echo htmlspecialchars($player['name'], ENT_QUOTES); ?></td>
                    <td><?php echo strtoupper($player['faction']); ?></td>
                    <td><?php echo (int)$player['access_level']; ?></td>
                    <td><?php echo htmlspecialchars($player['status'], ENT_QUOTES); ?></td>
                    <td>
                        <form class="inline" method="post">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($player['id'], ENT_QUOTES); ?>">
                            <input class="form-control" style="width:80px; display:inline-block;" type="number" name="access_level" value="<?php echo (int)$player['access_level']; ?>" min="1" max="5">
                            <select class="form-control" style="width:130px; display:inline-block;" name="status">
                                <?php foreach (['active','search','quarantine','unknown'] as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php if ($status === $player['status']) echo 'selected'; ?>><?php echo $status; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button secondary" type="submit">Зберегти</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
