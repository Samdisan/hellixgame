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

include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <h1>Адмінські оповіщення</h1>
    <p class="muted">Хронологія системних подій: хто що відкрив, які протоколи та фази спрацювали.</p>
    <div class="alert-summary">
        <?php foreach ($scopes as $key => $label): ?>
            <a class="pill<?php echo $currentScope === $key ? ' pill--active' : ''; ?>" href="?scope=<?php echo urlencode($key); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES); ?></a>
        <?php endforeach; ?>
    </div>
    <div class="alert-feed">
        <?php if (empty($filtered)): ?>
            <div class="muted">Подій за фільтром не знайдено.</div>
        <?php else: ?>
            <?php foreach ($filtered as $entry): ?>
                <?php
                    $bucket = $entry['bucket'] ?? 'system';
                    $ts = $entry['timestamp'] ?? '';
                    $target = $entry['target'] ?? '';
                    $type = $entry['type'] ?? '';
                ?>
                <div class="alert-row alert-row--<?php echo htmlspecialchars($bucket, ENT_QUOTES); ?>">
                    <div class="alert-meta">
                        <span class="badge level"><?php echo strtoupper($bucket); ?></span>
                        <span class="micro muted"><?php echo htmlspecialchars($ts, ENT_QUOTES); ?></span>
                        <span class="micro">Ціль: <?php echo htmlspecialchars($target ?: '—', ENT_QUOTES); ?></span>
                        <span class="micro">Тип: <?php echo htmlspecialchars($type ?: '—', ENT_QUOTES); ?></span>
                    </div>
                    <div class="alert-body"><?php echo htmlspecialchars($entry['message'] ?? '', ENT_QUOTES); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
