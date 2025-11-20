<?php
require_once __DIR__ . '/includes/helpers.php';
$protocols = array_filter(load_json('protocols.json'), fn($p) => !empty($p['public']));
usort($protocols, fn($a, $b) => strtotime($b['publish_time']) <=> strtotime($a['publish_time']));
$players = load_json('players.json');
$files = load_json('personal-files.json');
$filesById = [];
foreach ($files as $file) {
    $filesById[$file['id']] = $file;
}
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
            <details>
                <summary class="button secondary" style="display:inline-block;">Читати / відкрити</summary>
                <p><?php echo htmlspecialchars($protocol['content'], ENT_QUOTES); ?></p>
            </details>
        </div>
    <?php endforeach; ?>
</section>

<section class="panel" id="personal-files">
    <h2>Особисті справи</h2>
    <p class="muted">Повна картотека персоналу HELIX: біо, фото-заглушки та статуси з /data/players.json.</p>
</section>

<section class="grid cols-3 personal-grid">
    <?php foreach ($players as $person): ?>
        <?php $file = $filesById[$person['id']] ?? null; ?>
        <article class="personal-card">
            <div class="portrait portrait-<?php echo htmlspecialchars($person['faction'], ENT_QUOTES); ?>">
                <span><?php echo mb_substr($person['name'], 0, 1, 'UTF-8'); ?></span>
            </div>
            <div class="personal-meta">
                <h3><?php echo htmlspecialchars($person['name'], ENT_QUOTES); ?></h3>
                <div class="muted">Роль: <?php echo htmlspecialchars($person['role'], ENT_QUOTES); ?></div>
                <div class="badge status-<?php echo htmlspecialchars($person['status'], ENT_QUOTES); ?>">Статус: <?php echo htmlspecialchars($person['status'], ENT_QUOTES); ?></div>
                <div class="chip">Доступ: <?php echo (int) $person['access_level']; ?></div>
                <div class="chip">Фракція: <?php echo htmlspecialchars($person['faction'], ENT_QUOTES); ?></div>
            </div>
            <p class="personal-summary"><?php echo htmlspecialchars($file['summary'] ?? 'Досьє створено автоматично з даних реєстру.', ENT_QUOTES); ?></p>
        </article>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
