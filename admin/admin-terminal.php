<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$response = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $command = trim($_POST['command']);
    if (stripos($command, '/msg') === 0) {
        if (preg_match('/^\/msg\s+\"?(.*?)\"?$/', $command, $m)) {
            append_terminal_message('both', 'info', $m[1]);
            $response = 'Надіслано у всі термінали';
        } else {
            $response = 'Формат: /msg "текст"';
        }
    } elseif (stripos($command, '/run') === 0) {
        $id = trim(substr($command, 4));
        $quest = find_quest($id);
        if ($quest) {
            run_quest_actions($quest);
            append_terminal_message('both', 'protocol', 'Запущено квест ' . $id);
            $response = 'Квест виконано: ' . $id;
        } else {
            $response = 'Квест не знайдено';
        }
    } elseif (stripos($command, '/setaccess') === 0) {
        $parts = preg_split('/\s+/', $command);
        if (count($parts) === 3) {
            [$cmd, $playerId, $level] = $parts;
            $players = load_json('players.json');
            foreach ($players as &$player) {
                if ($player['id'] === $playerId) {
                    $player['access_level'] = (int) $level;
                    append_terminal_message('both', 'info', "[ACCESS] {$playerId} -> {$level}");
                }
            }
            unset($player);
            save_json('players.json', $players);
            $response = "Рівень доступу {$playerId} встановлено на {$level}";
        } else {
            $response = 'Формат: /setaccess PLAYER LEVEL';
        }
    } elseif (stripos($command, '/phase') === 0) {
        $id = trim(substr($command, 6));
        set_current_phase($id);
        append_terminal_message('both', 'info', 'Фазу змінено на ' . $id);
        $response = 'Фаза оновлена';
    } else {
        $response = 'Невідома команда';
    }
}

$messages = array_slice(array_reverse(load_terminal_messages_with_ids()), 0, 20);
include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Адмінський термінал</h1>
    <p class="muted">Чиста консоль для архітектора гри. Команди /msg, /run, /setaccess, /phase керують станцією; усі повідомлення гравців одразу з’являються тут як чат.</p>
    <form method="post">
        <input class="form-control" name="command" placeholder="/run Q_OUTBREAK_01" required>
        <button class="button" type="submit" style="margin-top:8px;">Виконати</button>
    </form>
    <?php if ($response): ?><div class="protocol-card" style="margin-top:8px;"><?php echo htmlspecialchars($response, ENT_QUOTES); ?></div><?php endif; ?>
    <div class="overlay-text">full control</div>
</section>
<section class="panel">
    <h2>Живий log</h2>
    <div class="terminal terminal-feed" data-target="all" data-can-delete="1" data-poll-ms="3000">
        <?php foreach ($messages as $msg): ?>
            <div class="terminal-line line-<?php echo htmlspecialchars($msg['type'], ENT_QUOTES); ?>" data-message-id="<?php echo htmlspecialchars($msg['id'], ENT_QUOTES); ?>">
                <span class="muted"><?php echo date('H:i:s', strtotime($msg['timestamp'])); ?></span>
                <span class="badge"><?php echo strtoupper($msg['target']); ?></span>
                <span class="terminal-line__body">
                    <?php echo htmlspecialchars($msg['message'], ENT_QUOTES); ?>
                    <button class="terminal-delete" type="button" data-delete-id="<?php echo htmlspecialchars($msg['id'], ENT_QUOTES); ?>" aria-label="Видалити повідомлення">✕</button>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
