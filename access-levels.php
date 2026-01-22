<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');

$player = find_player($_SESSION['player_id']);
if (!$player) {
    header('Location: login.php');
    exit;
}

$players = load_json('players.json');
$votes = load_json('access-votes.json');
$approvers = ['PL_STATION_ROSS', 'PL_STATION_CROW_PSY', 'PL_STATION_SATO', 'PL_WHO_PROGRAMMER'];
$isApprover = in_array($player['id'], $approvers, true);
$accessMeta = $votes['_meta'] ?? [];
$accessSettings = $accessMeta['settings'] ?? [];
$rossOverrideEnabled = !empty($accessSettings['ross_single_promotion_enabled']);
$rossOverrideCooldown = (int) ($accessSettings['ross_single_promotion_cooldown_sec'] ?? 7200);
$rossOverrideLastUsed = (int) ($accessSettings['ross_single_promotion_last_used'] ?? 0);
$rossOverrideRemaining = $rossOverrideEnabled ? max(0, ($rossOverrideLastUsed + $rossOverrideCooldown) - time()) : null;
$whoOverrideEnabled = !empty($accessSettings['who_programmer_single_promotion_enabled']);
$whoOverrideCooldown = (int) ($accessSettings['who_programmer_single_promotion_cooldown_sec'] ?? 7200);
$whoOverrideLastUsed = (int) ($accessSettings['who_programmer_single_promotion_last_used'] ?? 0);
$whoOverrideRemaining = $whoOverrideEnabled ? max(0, ($whoOverrideLastUsed + $whoOverrideCooldown) - time()) : null;

include __DIR__ . '/partials/header.php';
?>
<?php if (!$isApprover): ?>
<section class="panel">
    <div class="panel__header">
        <div>
            <p class="micro muted">Керування рівнями доступу</p>
            <h1>Рівні доступу</h1>
        </div>
        <div class="badge level">Доступ обмежено</div>
    </div>
    <p class="muted">Цей модуль доступний лише для: Глен Росс, Кроу (психолог), Кіра Сато, Програміст ВООЗ. Зверніться до них або до адміна станції.</p>
</section>
<?php include __DIR__ . '/partials/footer.php'; return; endif; ?>

<section class="panel">
    <div class="panel__header">
        <div>
            <p class="micro muted">Керування рівнями доступу</p>
            <h1>Рівні доступу</h1>
        </div>
        <div class="badge level">Ви: <?php echo htmlspecialchars($player['name'], ENT_QUOTES); ?></div>
    </div>
    <?php if ($player['id'] === 'PL_STATION_ROSS' && $rossOverrideEnabled): ?>
        <div class="micro muted" style="margin-bottom: 10px;">
            Спец-доступ: одноосібне підвищення на 1 рівень раз на 2 години.
            <?php if ($rossOverrideRemaining !== null && $rossOverrideRemaining > 0): ?>
                Доступно через <?php echo human_time((int) $rossOverrideRemaining); ?>.
            <?php else: ?>
                Доступно зараз.
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($player['id'] === 'PL_WHO_PROGRAMMER' && $whoOverrideEnabled): ?>
        <div class="micro muted" style="margin-bottom: 10px;">
            Спец-доступ: одноосібне підвищення на 1 рівень раз на 2 години (програміст ВООЗ).
            <?php if ($whoOverrideRemaining !== null && $whoOverrideRemaining > 0): ?>
                Доступно через <?php echo human_time((int) $whoOverrideRemaining); ?>.
            <?php else: ?>
                Доступно зараз.
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="table-wrapper" data-access-table>
        <table>
            <thead>
                <tr>
                    <th>Персонаж</th>
                    <th>Фракція</th>
                    <th>Роль</th>
                    <th>Рівень</th>
                    <th>Підтвердження</th>
                    <th>Дія</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $p): ?>
                    <?php
                        $approvals = array_unique($votes[$p['id']]['approvals'] ?? []);
                        $canApprove = in_array($_SESSION['player_id'], $approvers, true);
                        $alreadyVoted = in_array($_SESSION['player_id'], $approvals, true);
                        $nextLevel = min(3, (int) $p['access_level'] + 1);
                        $maxed = (int) $p['access_level'] >= 3;
                    ?>
                    <tr data-player-id="<?php echo htmlspecialchars($p['id'], ENT_QUOTES); ?>" data-approvals="<?php echo count($approvals); ?>" data-level="<?php echo (int)$p['access_level']; ?>">
                        <td><strong><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></strong></td>
                        <td class="muted"><?php echo strtoupper(htmlspecialchars($p['faction'], ENT_QUOTES)); ?></td>
                        <td class="muted micro"><?php echo htmlspecialchars($p['role'], ENT_QUOTES); ?></td>
                        <td class="badge level"><?php echo (int) $p['access_level']; ?></td>
                        <td>
                            <div class="pill">Підтвердження: <span class="pill-emph" data-approvals-count><?php echo count($approvals); ?></span>/3</div>
                        </td>
                        <td>
                            <?php if ($maxed): ?>
                                <span class="muted micro">Максимум (адмін тільки)</span>
                            <?php elseif (!$canApprove): ?>
                                <span class="muted micro">Доступно лише спеціалістам</span>
                            <?php elseif ($alreadyVoted): ?>
                                <span class="muted micro">Ваш голос зафіксовано</span>
                            <?php else: ?>
                                <button class="button" data-approve data-target="<?php echo htmlspecialchars($p['id'], ENT_QUOTES); ?>" data-next="<?php echo $nextLevel; ?>">Підвищити до <?php echo $nextLevel; ?></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="muted micro">Щойно двоє спеціалістів погоджуються, рівень піднімається автоматично й голоси обнуляються.</div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
