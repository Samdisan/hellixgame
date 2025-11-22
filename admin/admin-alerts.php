<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$allMessages = load_json('terminal-messages.json');

usort($allMessages, function ($a, $b) {
    $ta = strtotime($a['timestamp'] ?? '') ?: 0;
    $tb = strtotime($b['timestamp'] ?? '') ?: 0;
    return $tb <=> $ta; // newest first
});

$scopes = [
    'all' => 'Усі',
    'protocol' => 'Протоколи',
    'access' => 'Доступи',
    'phase' => 'Фази / тригери',
    'player' => 'Дії гравців',
    'system' => 'Системні',
];
$currentScope = $_GET['scope'] ?? 'all';
if (!isset($scopes[$currentScope])) {
    $currentScope = 'all';
}

$categorized = [];
foreach ($allMessages as $msg) {
    $type = $msg['type'] ?? '';
    $text = $msg['message'] ?? '';
    $bucket = 'system';

    if (stripos($text, '[PROTOCOL]') !== false || $type === 'protocol') {
        $bucket = 'protocol';
    } elseif (stripos($text, '[PLAYER]') !== false || stripos($text, 'Гравець відкрив протокол') !== false) {
        $bucket = 'player';
    } elseif (stripos($text, '[PHASE]') !== false || stripos($text, '[TRIGGER]') !== false) {
        $bucket = 'phase';
    } elseif (stripos($text, 'доступ') !== false || stripos($text, '[ACCESS]') !== false) {
        $bucket = 'access';
    }

    $msg['bucket'] = $bucket;
    $categorized[] = $msg;
}

$filtered = array_filter($categorized, function ($msg) use ($currentScope) {
    if ($currentScope === 'all') {
        return true;
    }
    return ($msg['bucket'] ?? '') === $currentScope;
});

$totals = array_fill_keys(array_keys($scopes), 0);
foreach ($categorized as $msg) {
    $bucket = $msg['bucket'] ?? 'system';
    if (isset($totals[$bucket])) {
        $totals[$bucket] += 1;
    }
    $totals['all'] += 1;
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="panel__header">
        <div>
            <p class="eyebrow">Моніторинг подій</p>
            <h1>Адмінські оповіщення</h1>
            <p class="muted">Події з протоколів, фаз, доступів і дій гравців в єдиній стрічці для майстрів.</p>
        </div>
        <div class="alert-legend">
            <span class="dot dot--protocol"></span>Протоколи
            <span class="dot dot--phase"></span>Фази / тригери
            <span class="dot dot--access"></span>Доступи
            <span class="dot dot--player"></span>Гравці
            <span class="dot dot--system"></span>Система
        </div>
    </div>

    <div class="alert-stats">
        <?php foreach ($scopes as $key => $label): ?>
            <a class="stat-card<?php echo $currentScope === $key ? ' stat-card--active' : ''; ?>" href="?scope=<?php echo urlencode($key); ?>">
                <div class="stat-card__label"><?php echo htmlspecialchars($label, ENT_QUOTES); ?></div>
                <div class="stat-card__value"><?php echo $totals[$key] ?? 0; ?></div>
                <div class="stat-card__hint"><?php echo $key === 'all' ? 'Усі події' : 'Фільтр за категорією'; ?></div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="alert-feed">
        <?php if (empty($filtered)): ?>
            <div class="empty-state">
                <div class="empty-state__title">Немає подій</div>
                <div class="muted">За обраним фільтром поки що немає логів. Спробуйте іншу категорію або дочекайтесь нових подій.</div>
            </div>
        <?php else: ?>
            <div class="alert-timeline">
                <?php foreach ($filtered as $entry): ?>
                    <?php
                        $bucket = $entry['bucket'] ?? 'system';
                        $ts = $entry['timestamp'] ?? '';
                        $target = $entry['target'] ?? '';
                        $type = $entry['type'] ?? '';
                        $msg = $entry['message'] ?? '';
                    ?>
                    <div class="timeline-item timeline-item--<?php echo htmlspecialchars($bucket, ENT_QUOTES); ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-card">
                            <div class="timeline-meta">
                                <span class="badge level"><?php echo strtoupper($bucket); ?></span>
                                <span class="micro muted"><?php echo htmlspecialchars($ts, ENT_QUOTES); ?></span>
                                <?php if ($target): ?><span class="micro">Ціль: <?php echo htmlspecialchars($target, ENT_QUOTES); ?></span><?php endif; ?>
                                <?php if ($type): ?><span class="micro">Тип: <?php echo htmlspecialchars($type, ENT_QUOTES); ?></span><?php endif; ?>
                            </div>
                            <div class="timeline-body"><?php echo nl2br(htmlspecialchars($msg, ENT_QUOTES)); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
