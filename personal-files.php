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
        <?php $detailLink = ($person['id'] ?? '') === 'PL_STATION_GREN' ? 'personal-file.php?id=PL_STATION_GREN' : null; ?>
        <article class="personal-card">
            <div class="portrait portrait-<?php echo htmlspecialchars($person['faction'], ENT_QUOTES); ?>">
                <span><?php echo mb_substr($person['name'], 0, 1, 'UTF-8'); ?></span>
            </div>
            <div class="personal-meta">
                <h3>
                    <?php if ($detailLink): ?>
                        <a href="<?php echo htmlspecialchars($detailLink, ENT_QUOTES); ?>">
                            <?php echo htmlspecialchars($person['name'], ENT_QUOTES); ?>
                        </a>
                    <?php else: ?>
                        <?php echo htmlspecialchars($person['name'], ENT_QUOTES); ?>
                    <?php endif; ?>
                </h3>
                <div class="muted">Роль: <?php echo htmlspecialchars($person['role'], ENT_QUOTES); ?></div>
                <div class="badge status-<?php echo htmlspecialchars($person['status'], ENT_QUOTES); ?>">Статус: <?php echo htmlspecialchars($person['status'], ENT_QUOTES); ?></div>
                <div class="chip">ID: <?php echo htmlspecialchars($person['id'], ENT_QUOTES); ?></div>
                <div class="chip">Доступ: <?php echo (int) $person['access_level']; ?></div>
                <div class="chip">Фракція: <?php echo htmlspecialchars($person['faction'], ENT_QUOTES); ?></div>
            </div>
            <p class="personal-summary"><?php echo htmlspecialchars($file['summary'] ?? 'Досьє створено автоматично з даних реєстру.', ENT_QUOTES); ?></p>
            <?php if ($detailLink): ?>
                <div class="stack">
                    <a class="button button-ghost" href="<?php echo htmlspecialchars($detailLink, ENT_QUOTES); ?>">Відкрити досьє</a>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
