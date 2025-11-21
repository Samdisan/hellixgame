<?php
require_once __DIR__ . '/includes/helpers.php';
$protocols = array_filter(load_json('protocols.json'), fn($p) => !empty($p['public']));
usort($protocols, fn($a, $b) => strtotime($b['publish_time']) <=> strtotime($a['publish_time']));
include __DIR__ . '/partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Публічні протоколи</h1>
    <p class="muted">Офіційна стіна оголошень станції HELIX. Деякі записи можуть тимчасово зникати: Data integrity тримається на 89%.</p>
    <div class="banner"><span class="scan-pulse"></span>Система звіряє цифрові підписи. Несанкціоновані зміни будуть зафіксовані.</div>
</section>
<section class="card-stack">
    <?php foreach ($protocols as $protocol): ?>
        <div class="protocol-card">
            <h3><?php echo htmlspecialchars($protocol['label'], ENT_QUOTES); ?></h3>
            <div class="muted">Рівень: <?php echo (int)$protocol['level']; ?> · Фаза: <?php echo htmlspecialchars($protocol['phase'], ENT_QUOTES); ?></div>
            <p><?php echo htmlspecialchars($protocol['description'], ENT_QUOTES); ?></p>
            <button type="button" class="button secondary protocol-open"
                data-protocol-id="<?php echo htmlspecialchars($protocol['id'], ENT_QUOTES); ?>"
                data-protocol-label="<?php echo htmlspecialchars($protocol['label'], ENT_QUOTES); ?>"
                data-protocol-level="<?php echo (int)$protocol['level']; ?>"
                data-protocol-phase="<?php echo htmlspecialchars($protocol['phase'], ENT_QUOTES); ?>"
                data-protocol-description="<?php echo htmlspecialchars($protocol['description'], ENT_QUOTES); ?>"
                data-protocol-content="<?php echo htmlspecialchars($protocol['content'], ENT_QUOTES); ?>">
                Читати / відкрити
            </button>
        </div>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
