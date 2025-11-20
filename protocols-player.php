<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');
$player = find_player($_SESSION['player_id']);
$protocols = load_json('protocols.json');
$openedId = $_GET['open'] ?? null;
$currentProtocol = null;

$filtered = array_filter($protocols, function ($protocol) use ($player) {
    return protocol_accessible($protocol, $player);
});

$newCount = 0;
foreach ($filtered as $candidate) {
    if (!has_opened_protocol($player['id'], $candidate['id'])) {
        $newCount++;
    }
}

if ($openedId) {
    $candidate = fetch_protocol($openedId);
    if ($candidate && protocol_accessible($candidate, $player)) {
        $currentProtocol = $candidate;
    }
}

usort($filtered, fn($a, $b) => ($b['level'] <=> $a['level']) ?: strcmp($a['id'], $b['id']));
include __DIR__ . '/partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Персональні протоколи</h1>
    <p class="muted">Це канал секретних даних. Непрочитані протоколи пульсують — станція вимагає реакції.</p>
    <?php if ($newCount > 0): ?>
        <div class="banner"><span class="scan-pulse"></span>Новий протокол доставлено · <?php echo $newCount; ?> ще не відкрито.</div>
    <?php endif; ?>
    <div class="overlay-text">secured feed</div>
</section>
<section class="card-stack">
    <?php foreach ($filtered as $protocol): ?>
        <?php $isNew = !has_opened_protocol($player['id'], $protocol['id']); ?>
        <div class="protocol-card <?php echo $isNew ? 'new' : ''; ?>">
            <h3><?php echo htmlspecialchars($protocol['label'], ENT_QUOTES); ?></h3>
            <div class="muted">Рівень <?php echo (int)$protocol['level']; ?> · Фаза: <?php echo htmlspecialchars($protocol['phase'], ENT_QUOTES); ?></div>
            <p><?php echo htmlspecialchars($protocol['description'], ENT_QUOTES); ?></p>
            <a class="button secondary" href="/api/mark-protocol-opened.php?protocol=<?php echo urlencode($protocol['id']); ?>&redirect=/protocols-player.php">Відкрити</a>
        </div>
    <?php endforeach; ?>
</section>

<?php if ($currentProtocol): ?>
<section class="panel">
    <h2><?php echo htmlspecialchars($currentProtocol['label'], ENT_QUOTES); ?></h2>
    <div class="muted">ID: <?php echo htmlspecialchars($currentProtocol['id'], ENT_QUOTES); ?> · Оприлюднено: <?php echo htmlspecialchars($currentProtocol['publish_time'] ?? '—', ENT_QUOTES); ?></div>
    <p><?php echo nl2br(htmlspecialchars($currentProtocol['content'], ENT_QUOTES)); ?></p>
</section>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
