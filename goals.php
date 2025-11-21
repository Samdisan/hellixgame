<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');

$player = find_player($_SESSION['player_id']);
if (!$player) {
    header('Location: login.php');
    exit;
}

$goals = load_json('goals.json');
$scopeEntry = null;

foreach ($goals as $entry) {
    if (($entry['scope'] ?? '') === 'player:' . $player['id']) {
        $scopeEntry = $entry;
        break;
    }
}

if (!$scopeEntry) {
    foreach ($goals as $entry) {
        if (($entry['scope'] ?? '') === 'faction:' . ($player['faction'] ?? '')) {
            $scopeEntry = $entry;
            break;
        }
    }
}

if (!$scopeEntry) {
    foreach ($goals as $entry) {
        if (($entry['scope'] ?? '') === 'default') {
            $scopeEntry = $entry;
            break;
        }
    }
}

include __DIR__ . '/partials/header.php';
?>
<section class="panel">
    <div class="panel__header">
        <div>
            <p class="micro muted">Персональна секція</p>
            <h1>Цілі</h1>
        </div>
        <div class="badge level">Рівень доступу: <?php echo (int) $player['access_level']; ?></div>
    </div>
    <p class="muted">Ваші задачі відображаються з урахуванням ролі та фракції. Оновлення застосовуються миттєво після синхронізації сценарію.</p>
    <div class="goals-grid">
        <div class="goal-card">
            <div class="glitch-overlay"></div>
            <div class="goal-card__meta">Фракція: <?php echo strtoupper(htmlspecialchars($player['faction'], ENT_QUOTES)); ?></div>
            <h2><?php echo htmlspecialchars($scopeEntry['title'] ?? 'Цілі станції', ENT_QUOTES); ?></h2>
            <ol>
                <?php foreach (($scopeEntry['goals'] ?? []) as $goal): ?>
                    <li><?php echo htmlspecialchars($goal, ENT_QUOTES); ?></li>
                <?php endforeach; ?>
            </ol>
            <?php if (!empty($scopeEntry['notes'])): ?>
                <div class="muted micro">Примітка: <?php echo htmlspecialchars($scopeEntry['notes'], ENT_QUOTES); ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
