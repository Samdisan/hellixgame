<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');
$player = find_player($_SESSION['player_id']);
$protocols = load_json('protocols.json');

$accessible = array_filter($protocols, function ($protocol) use ($player) {
    return protocol_accessible($protocol, $player);
});

$newCount = 0;
foreach ($accessible as $candidate) {
    if (!has_opened_protocol($player['id'], $candidate['id'])) {
        $newCount++;
    }
}

usort($accessible, fn($a, $b) => ($b['level'] <=> $a['level']) ?: strcmp($a['id'], $b['id']));
include __DIR__ . '/partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Персональні протоколи</h1>
    <p class="muted">Це канал секретних даних. Непрочитані протоколи пульсують — станція вимагає реакції.</p>
    <div class="flex between" style="gap:12px; align-items:center; flex-wrap:wrap;">
        <?php if ($newCount > 0): ?>
            <div class="banner"><span class="scan-pulse"></span>Новий протокол доставлено · <?php echo $newCount; ?> ще не відкрито.</div>
        <?php endif; ?>
    </div>
    <div class="overlay-text">secured feed</div>
</section>
<section class="card-stack">
    <?php foreach ($accessible as $protocol): ?>
        <?php
            $isAccessible = protocol_accessible($protocol, $player);
            $isNew = $isAccessible && !has_opened_protocol($player['id'], $protocol['id']);
            $isRedacted = !empty($protocol['flags']['redacted']);
            $classes = [];
            if ($isNew) { $classes[] = 'new'; }
            if ($isRedacted) { $classes[] = 'redacted'; }
        ?>
        <div class="protocol-card <?php echo implode(' ', $classes); ?>">
            <h3><?php echo htmlspecialchars($protocol['label'], ENT_QUOTES); ?></h3>
            <div class="muted">Рівень <?php echo (int)$protocol['level']; ?> · Фаза: <?php echo htmlspecialchars($protocol['phase'], ENT_QUOTES); ?></div>
            <p><?php echo htmlspecialchars($protocol['description'], ENT_QUOTES); ?></p>
            <?php if ($isRedacted): ?><div class="micro muted">Ця копія містить приховані блоки та глічі.</div><?php endif; ?>
            <button type="button" class="button secondary protocol-open" data-protocol-id="<?php echo htmlspecialchars($protocol['id'], ENT_QUOTES); ?>"
                data-protocol-label="<?php echo htmlspecialchars($protocol['label'], ENT_QUOTES); ?>"
                data-protocol-level="<?php echo (int)$protocol['level']; ?>"
                data-protocol-phase="<?php echo htmlspecialchars($protocol['phase'], ENT_QUOTES); ?>"
                data-protocol-description="<?php echo htmlspecialchars($protocol['description'], ENT_QUOTES); ?>"
                data-protocol-content="<?php echo htmlspecialchars($protocol['content'], ENT_QUOTES); ?>"
                data-protocol-redacted="<?php echo $isRedacted ? '1' : '0'; ?>"
                data-protocol-player="<?php echo htmlspecialchars($player['id'], ENT_QUOTES); ?>"
                data-mark-url="/api/mark-protocol-opened.php"
                <?php if (!$isAccessible): ?>data-locked="1"<?php endif; ?>
            >Читати</button>
        </div>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
