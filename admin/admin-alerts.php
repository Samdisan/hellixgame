<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

// Helpers -------------------------------------------------------------
function game_time_seconds(?string $time): int
{
    if (!$time) {
        return 0;
    }
    $parts = explode(':', $time);
    if (count($parts) === 3) {
        return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)$parts[2];
    }
    if (count($parts) === 2) {
        return ((int)$parts[0] * 60) + (int)$parts[1];
    }
    return (int) $time;
}

function classify_bucket(array $entry): string
{
    $type = strtolower((string)($entry['type'] ?? ''));
    $message = mb_strtolower((string)($entry['message'] ?? ($entry['text'] ?? '')));

    if ($type === 'protocol' || str_contains($message, '[protocol]') || str_contains($message, 'протокол')) {
        return 'protocol';
    }
    if (str_contains($message, '[phase]') || str_contains($message, '[trigger]') || str_contains($message, 'фаза')) {
        return 'phase';
    }
    if ($type === 'access' || str_contains($message, 'доступ')) {
        return 'access';
    }
    if (str_contains($message, 'player') || str_contains($message, 'гравець') || str_contains($message, '[player]')) {
        return 'player';
    }
    return 'system';
}

// Normalise messages --------------------------------------------------
$rawMessages = load_json('terminal-messages.json');
if (!is_array($rawMessages)) {
    $rawMessages = [];
}

$events = [];
$index = 0;
foreach ($rawMessages as $row) {
    $index++;
    $message = trim((string)($row['message'] ?? ($row['text'] ?? '')));
    if ($message === '') {
        continue;
    }

    $timestampRaw = (string)($row['timestamp'] ?? '');
    $timeGame = (string)($row['time_game'] ?? '');
    $sortKey = 0;
    $displayTime = '—';

    if ($timestampRaw !== '') {
        $sortKey = strtotime($timestampRaw) ?: 0;
        $displayTime = date('Y-m-d H:i:s', $sortKey ?: time());
    } elseif ($timeGame !== '') {
        $sortKey = game_time_seconds($timeGame);
        $displayTime = 'T+' . $timeGame;
    } else {
        $sortKey = -$index; // fallback preserves insertion order
    }

    $events[] = [
        'bucket' => classify_bucket($row),
        'target' => $row['target'] ?? '—',
        'type' => $row['type'] ?? '—',
        'message' => $message,
        'timestamp_label' => $displayTime,
        'sort_key' => $sortKey,
    ];
}

usort($events, function ($a, $b) {
    return ($b['sort_key'] ?? 0) <=> ($a['sort_key'] ?? 0);
});

$buckets = [
    'all' => 'Усі події',
    'protocol' => 'Протоколи',
    'phase' => 'Фази/тригери',
    'access' => 'Доступи',
    'player' => 'Дії гравців',
    'system' => 'Система',
];

$targets = array_values(array_unique(array_map(fn($e) => $e['target'] ?? '—', $events)));
sort($targets);

$scope = $_GET['scope'] ?? 'all';
if (!isset($buckets[$scope])) {
    $scope = 'all';
}

$targetFilter = $_GET['target'] ?? 'all';

$filtered = array_filter($events, function ($event) use ($scope, $targetFilter) {
    if ($scope !== 'all' && ($event['bucket'] ?? '') !== $scope) {
        return false;
    }
    if ($targetFilter !== 'all' && ($event['target'] ?? '') !== $targetFilter) {
        return false;
    }
    return true;
});

$totals = array_fill_keys(array_keys($buckets), 0);
foreach ($events as $event) {
    $bucket = $event['bucket'] ?? 'system';
    if (!isset($totals[$bucket])) {
        $bucket = 'system';
    }
    $totals[$bucket]++;
    $totals['all']++;
}

include __DIR__ . '/../partials/header.php';
?>

<section class="panel panel--alerts">
    <div class="panel__header panel__header--stacked">
        <div>
            <p class="eyebrow">Моніторинг подій</p>
            <h1>Адмінські оповіщення</h1>
            <p class="muted">Єдиний центр логів: протоколи, фазові тригери, доступи та активність гравців.</p>
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
        <?php foreach ($buckets as $key => $label): ?>
            <a class="pill<?php echo $scope === $key ? ' pill--active' : ''; ?>" href="?scope=<?php echo urlencode($key); ?>">
                <?php echo htmlspecialchars($label, ENT_QUOTES); ?>
                <span class="pill__count"><?php echo $totals[$key]; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="alert-stats-grid">
        <div class="alert-card">
            <div class="alert-card__label">Усього записів</div>
            <div class="alert-card__value"><?php echo $totals['all']; ?></div>
            <div class="alert-card__hint">Фільтр: <?php echo htmlspecialchars($buckets[$scope], ENT_QUOTES); ?></div>
        </div>
        <div class="alert-card alert-card--phase">
            <div class="alert-card__label">Фазові події</div>
            <div class="alert-card__value"><?php echo $totals['phase']; ?></div>
            <div class="alert-card__hint">Перемикання, тригери</div>
        </div>
        <div class="alert-card alert-card--protocol">
            <div class="alert-card__label">Протоколи</div>
            <div class="alert-card__value"><?php echo $totals['protocol']; ?></div>
            <div class="alert-card__hint">Оголошення, активації</div>
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
    </div>

    <div class="alert-filters">
        <label>Цільові стрічки
            <select onchange="location.href='?scope=<?php echo urlencode($scope); ?>&target=' + encodeURIComponent(this.value);">
                <option value="all" <?php echo $targetFilter === 'all' ? 'selected' : ''; ?>>Усі</option>
                <?php foreach ($targets as $t): ?>
                    <option value="<?php echo htmlspecialchars($t, ENT_QUOTES); ?>" <?php echo $targetFilter === $t ? 'selected' : ''; ?>><?php echo htmlspecialchars($t, ENT_QUOTES); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="alert-feed">
        <?php if (empty($filtered)): ?>
            <div class="empty-state">
                <div class="empty-state__title">Немає подій</div>
                <div class="muted">Спробуйте іншу категорію або зачекайте нових логів.</div>
            </div>
        <?php else: ?>
            <div class="alert-timeline">
                <?php foreach ($filtered as $entry): ?>
                    <?php $bucket = $entry['bucket'] ?? 'system'; ?>
                    <div class="timeline-item timeline-item--<?php echo htmlspecialchars($bucket, ENT_QUOTES); ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-card">
                            <div class="timeline-meta">
                                <span class="badge level"><?php echo strtoupper($bucket); ?></span>
                                <span class="micro muted"><?php echo htmlspecialchars($entry['timestamp_label'], ENT_QUOTES); ?></span>
                                <?php if (!empty($entry['target'])): ?>
                                    <span class="micro">Ціль: <?php echo htmlspecialchars($entry['target'], ENT_QUOTES); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($entry['type']) && $entry['type'] !== '—'): ?>
                                    <span class="micro">Тип: <?php echo htmlspecialchars($entry['type'], ENT_QUOTES); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-body"><?php echo nl2br(htmlspecialchars($entry['message'], ENT_QUOTES)); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
