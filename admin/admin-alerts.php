<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

// Load and normalise messages so the feed tolerates legacy fields.
$rawMessages = load_json('terminal-messages.json');
if (!is_array($rawMessages)) {
    $rawMessages = [];
}

// Normalise and sort newest first
$normalized = [];
foreach ($rawMessages as $entry) {
    $tsRaw = (string) ($entry['timestamp'] ?? ($entry['time_game'] ?? ''));
    $ts = $tsRaw ? date('Y-m-d H:i:s', strtotime($tsRaw)) : '—';
    $text = trim((string) ($entry['message'] ?? ($entry['text'] ?? '')));
    if ($text === '') {
        continue;
    }
    $type = strtolower((string) ($entry['type'] ?? ''));
    $target = (string) ($entry['target'] ?? '');

    // Bucketing rules keep only one scope per entry for filtering
    $bucket = 'system';
    $needle = mb_strtolower($text);

    if ($type === 'protocol' || str_contains($needle, '[protocol]') || str_contains($needle, 'протокол')) {
        $bucket = 'protocol';
    } elseif (str_contains($needle, '[phase]') || str_contains($needle, '[trigger]') || str_contains($needle, 'фаза')) {
        $bucket = 'phase';
    } elseif ($type === 'access' || str_contains($needle, '[access]') || str_contains($needle, 'доступ')) {
        $bucket = 'access';
    } elseif (str_contains($needle, '[player]') || str_contains($needle, 'гравець') || str_contains($needle, 'player ')) {
        $bucket = 'player';
    }

    $normalized[] = [
        'timestamp_raw' => $tsRaw,
        'timestamp' => $ts,
        'target' => $target,
        'type' => $type ?: '—',
        'message' => $text,
        'bucket' => $bucket,
    ];
}

usort($normalized, function ($a, $b) {
    $ta = strtotime($a['timestamp_raw'] ?? '') ?: 0;
    $tb = strtotime($b['timestamp_raw'] ?? '') ?: 0;
    return $tb <=> $ta; // newest first
});

$scopes = [
    'all' => 'Усі події',
    'protocol' => 'Протоколи',
    'phase' => 'Фази / тригери',
    'access' => 'Доступи',
    'player' => 'Дії гравців',
    'system' => 'Системні',
];
$currentScope = $_GET['scope'] ?? 'all';
if (!isset($scopes[$currentScope])) {
    $currentScope = 'all';
}

$filtered = array_filter($normalized, function ($msg) use ($currentScope) {
    if ($currentScope === 'all') {
        return true;
    }
    return ($msg['bucket'] ?? '') === $currentScope;
});

$totals = array_fill_keys(array_keys($scopes), 0);
foreach ($normalized as $msg) {
    $bucket = $msg['bucket'] ?? 'system';
    if (isset($totals[$bucket])) {
        $totals[$bucket] += 1;
    }
    $totals['all'] += 1;
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel panel--alerts">
    <div class="panel__header panel__header--stacked">
        <div>
            <p class="eyebrow">Моніторинг подій</p>
            <h1>Адмінські оповіщення</h1>
            <p class="muted">Єдине місце, де видно все: протоколи, фазові тригери, доступи та активність гравців.</p>
        </div>
        <div class="alert-legend">
            <span class="dot dot--protocol"></span>Протоколи
            <span class="dot dot--phase"></span>Фази / тригери
            <span class="dot dot--access"></span>Доступи
            <span class="dot dot--player"></span>Гравці
            <span class="dot dot--system"></span>Система
        </div>
    </div>

    <div class="alert-pills">
        <?php foreach ($scopes as $key => $label): ?>
            <a class="pill<?php echo $currentScope === $key ? ' pill--active' : ''; ?>" href="?scope=<?php echo urlencode($key); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="alert-stats-grid">
        <div class="alert-card">
            <div class="alert-card__label">Усього записів</div>
            <div class="alert-card__value"><?php echo $totals['all']; ?></div>
            <div class="alert-card__hint">Оновлено: <?php echo date('H:i:s'); ?></div>
        </div>
        <div class="alert-card alert-card--protocol">
            <div class="alert-card__label">Протоколи</div>
            <div class="alert-card__value"><?php echo $totals['protocol']; ?></div>
            <div class="alert-card__hint">Оголошення, активації</div>
        </div>
        <div class="alert-card alert-card--phase">
            <div class="alert-card__label">Фази / тригери</div>
            <div class="alert-card__value"><?php echo $totals['phase']; ?></div>
            <div class="alert-card__hint">Перемикання, розсилки</div>
        </div>
        <div class="alert-card alert-card--access">
            <div class="alert-card__label">Доступи</div>
            <div class="alert-card__value"><?php echo $totals['access']; ?></div>
            <div class="alert-card__hint">Підвищення / блокування</div>
        </div>
        <div class="alert-card alert-card--player">
            <div class="alert-card__label">Дії гравців</div>
            <div class="alert-card__value"><?php echo $totals['player']; ?></div>
            <div class="alert-card__hint">Повідомлення, відкриття</div>
        </div>
        <div class="alert-card alert-card--system">
            <div class="alert-card__label">Система</div>
            <div class="alert-card__value"><?php echo $totals['system']; ?></div>
            <div class="alert-card__hint">Технічні події</div>
        </div>
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
                        $ts = $entry['timestamp'] ?? '—';
                        $target = $entry['target'] ?? '';
                        $type = $entry['type'] ?? '—';
                        $msg = $entry['message'] ?? '';
                    ?>
                    <div class="timeline-item timeline-item--<?php echo htmlspecialchars($bucket, ENT_QUOTES); ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-card">
                            <div class="timeline-meta">
                                <span class="badge level"><?php echo strtoupper($bucket); ?></span>
                                <span class="micro muted"><?php echo htmlspecialchars($ts, ENT_QUOTES); ?></span>
                                <?php if ($target): ?><span class="micro">Ціль: <?php echo htmlspecialchars($target, ENT_QUOTES); ?></span><?php endif; ?>
                                <?php if ($type && $type !== '—'): ?><span class="micro">Тип: <?php echo htmlspecialchars($type, ENT_QUOTES); ?></span><?php endif; ?>
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
