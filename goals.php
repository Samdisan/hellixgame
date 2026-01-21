<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');

$player = find_player($_SESSION['player_id']);
if (!$player) {
    header('Location: login.php');
    exit;
}

$scopeEntry = find_goal_scope_for_player($player);
$completed = completed_goal_keys($player['id']);
$scopeId = $scopeEntry['scope'] ?? 'default';

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
    <div class="goals-grid">
        <div class="goal-card">
            <div class="glitch-overlay"></div>
            <div class="goal-card__meta">Фракція: <?php echo strtoupper(htmlspecialchars($player['faction'], ENT_QUOTES)); ?></div>
            <h2><?php echo htmlspecialchars($scopeEntry['title'] ?? 'Цілі станції', ENT_QUOTES); ?></h2>
            <?php if (empty($scopeEntry)): ?>
                <p>Немає цілей для відображення.</p>
            <?php else: ?>
                <ol class="goal-list">
                    <?php foreach (($scopeEntry['goals'] ?? []) as $goal): ?>
                        <?php $key = goal_key($scopeId, (string) $goal); ?>
                        <li class="goal-list__item" data-goal-key="<?php echo htmlspecialchars($key, ENT_QUOTES); ?>">
                            <label class="goal-toggle">
                                <input type="checkbox" data-goal-key="<?php echo htmlspecialchars($key, ENT_QUOTES); ?>" <?php echo in_array($key, $completed, true) ? 'checked' : ''; ?>>
                                <span class="goal-toggle__box" aria-hidden="true"></span>
                                <span class="goal-toggle__text"><?php echo htmlspecialchars($goal, ENT_QUOTES); ?></span>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <?php if (!empty($scopeEntry['notes'])): ?>
                    <div class="muted micro">Примітка: <?php echo htmlspecialchars($scopeEntry['notes'], ENT_QUOTES); ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
