<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');
$player = find_player($_SESSION['player_id']);
$protocols = load_json('protocols.json');
$openedId = $_GET['open'] ?? null;
$showAll = isset($_GET['all']);
$currentProtocol = null;

$accessible = array_filter($protocols, function ($protocol) use ($player) {
    return protocol_accessible($protocol, $player);
});

$newCount = 0;
foreach ($accessible as $candidate) {
    if (!has_opened_protocol($player['id'], $candidate['id'])) {
        $newCount++;
    }
}

$listing = $showAll ? $protocols : $accessible;

if ($openedId) {
    $candidate = fetch_protocol($openedId);
    if ($candidate && ($showAll || protocol_accessible($candidate, $player))) {
        $currentProtocol = $candidate;
        if (protocol_accessible($candidate, $player)) {
            update_player_progress($player['id'], $candidate['id']);
        }
    }
}

usort($listing, fn($a, $b) => ($b['level'] <=> $a['level']) ?: strcmp($a['id'], $b['id']));
include __DIR__ . '/partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Персональні протоколи</h1>
    <p class="muted">Це канал секретних даних. Непрочитані протоколи пульсують — станція вимагає реакції.</p>
    <div class="flex between" style="gap:12px; align-items:center; flex-wrap:wrap;">
        <?php if ($newCount > 0 && !$showAll): ?>
            <div class="banner"><span class="scan-pulse"></span>Новий протокол доставлено · <?php echo $newCount; ?> ще не відкрито.</div>
        <?php endif; ?>
        <div class="actions" style="margin-left:auto;">
            <?php if ($showAll): ?>
                <a class="button secondary" href="/protocols-player.php">Показати лише доступні</a>
            <?php else: ?>
                <a class="button secondary" href="/protocols-player.php?all=1">Показати всі протоколи</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="overlay-text">secured feed</div>
</section>
<section class="card-stack">
    <?php foreach ($listing as $protocol): ?>
        <?php
            $accessible = protocol_accessible($protocol, $player);
            $isNew = $accessible && !has_opened_protocol($player['id'], $protocol['id']);
            $classes = [];
            if ($isNew) { $classes[] = 'new'; }
            if (!$accessible && $showAll) { $classes[] = 'locked'; }
            $qs = http_build_query(['open' => $protocol['id']] + ($showAll ? ['all' => 1] : []));
        ?>
        <div class="protocol-card <?php echo implode(' ', $classes); ?>">
            <h3><?php echo htmlspecialchars($protocol['label'], ENT_QUOTES); ?></h3>
            <div class="muted">Рівень <?php echo (int)$protocol['level']; ?> · Фаза: <?php echo htmlspecialchars($protocol['phase'], ENT_QUOTES); ?></div>
            <p><?php echo htmlspecialchars($protocol['description'], ENT_QUOTES); ?></p>
            <?php if (!$accessible && $showAll): ?>
                <div class="micro muted">Недостатній доступ, але доступно для ознайомлення.</div>
            <?php endif; ?>
            <a class="button secondary" href="/protocols-player.php?<?php echo $qs; ?>">Читати</a>
        </div>
    <?php endforeach; ?>
</section>

<?php if ($currentProtocol): ?>
<section class="panel">
    <h2><?php echo htmlspecialchars($currentProtocol['label'], ENT_QUOTES); ?></h2>
    <?php $currentAccessible = protocol_accessible($currentProtocol, $player); ?>
    <div class="muted">ID: <?php echo htmlspecialchars($currentProtocol['id'], ENT_QUOTES); ?> · Оприлюднено: <?php echo htmlspecialchars($currentProtocol['publish_time'] ?? '—', ENT_QUOTES); ?> <?php if (!$currentAccessible): ?>· лише для перегляду<?php endif; ?></div>
    <?php if (!$currentAccessible): ?>
        <div class="banner secondary"><span class="scan-pulse"></span>Цей протокол поза вашим доступом, контент відкрито лише для читання.</div>
    <?php endif; ?>
    <p><?php echo nl2br(htmlspecialchars($currentProtocol['content'], ENT_QUOTES)); ?></p>
</section>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
