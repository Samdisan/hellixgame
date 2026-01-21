<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$response = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        $response = 'Введіть текст повідомлення';
    } else {
        append_terminal_message('both', 'info', $message);
        $response = 'Надіслано у всі термінали';
    }
}

$messages = array_slice(array_reverse(load_terminal_messages_with_ids()), 0, 20);
include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Адмінський термінал</h1>
    <p class="muted">Проста консоль для швидких оголошень. Введіть текст — він одразу піде у всі термінали; нижче видно останні записи.</p>
    <form method="post">
        <input class="form-control" name="message" placeholder="Текст повідомлення" required>
        <button class="button" type="submit" style="margin-top:8px;">Надіслати</button>
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
