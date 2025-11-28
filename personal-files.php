<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');

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
    <h1>Особові справи</h1>
</section>
<section class="grid personal-grid">
    <?php foreach ($players as $person): ?>
        <?php $file = $filesById[$person['id']] ?? null; ?>
        <article class="personal-card">
            <div class="portrait portrait-<?php echo htmlspecialchars($person['faction'], ENT_QUOTES); ?>">
                <?php if (!empty($file['photo'])): ?>
                    <a class="portrait-link" href="<?php echo htmlspecialchars($file['photo'], ENT_QUOTES); ?>" target="_blank" rel="noopener">
                        <img src="<?php echo htmlspecialchars($file['photo'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($person['name'], ENT_QUOTES); ?>">
                    </a>
                <?php else: ?>
                    <span><?php echo mb_substr($person['name'], 0, 1, 'UTF-8'); ?></span>
                <?php endif; ?>
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
