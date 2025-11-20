<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$players = load_json('players.json');
$protocols = load_json('protocols.json');
$quests = load_json('quests.json');
$phases = load_json('phases.json');
$timer = load_json('timer.json');

$errors = [];
$warnings = [];
$playerIds = array_column($players, 'id');
$phaseIds = array_column($phases['phases'] ?? [], 'id');
$protocolIds = array_column($protocols, 'id');
$questIds = array_column($quests, 'id');

foreach ($protocols as $protocol) {
    if (!in_array($protocol['phase'], $phaseIds, true)) {
        $errors[] = "Protocol {$protocol['id']} має невірну фазу {$protocol['phase']}";
    }
    if (isset($protocol['allowed_players'])) {
        foreach ($protocol['allowed_players'] as $pid) {
            if (!in_array($pid, $playerIds, true)) {
                $errors[] = "Protocol {$protocol['id']} посилається на відсутнього гравця {$pid}";
            }
        }
    }
}

foreach ($quests as $quest) {
    if (!in_array($quest['phase'], $phaseIds, true)) {
        $errors[] = "Quest {$quest['id']} має невідому фазу {$quest['phase']}";
    }
    if (empty($quest['actions'])) {
        $warnings[] = "Quest {$quest['id']} не має actions";
    }
}

foreach ($timer['time_triggers'] ?? [] as $trigger) {
    if (!in_array($trigger['quest_id'], $questIds, true)) {
        $errors[] = "Тригер посилається на відсутній quest {$trigger['quest_id']}";
    }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Діагностика сценарію</h1>
    <p class="muted">Рентген станції: шукає биті квести, відсутні протоколи, конфлікти фаз та помилки тригерів.</p>
    <div class="grid cols-2">
        <div>
            <h3>Помилки</h3>
            <?php if (empty($errors)): ?>
                <div class="protocol-card">Помилок не знайдено.</div>
            <?php else: ?>
                <?php foreach ($errors as $err): ?>
                    <div class="protocol-card" style="border-color: rgba(255,107,107,0.4);">⚠️ <?php echo htmlspecialchars($err, ENT_QUOTES); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div>
            <h3>Попередження</h3>
            <?php if (empty($warnings)): ?>
                <div class="protocol-card">Попереджень немає.</div>
            <?php else: ?>
                <?php foreach ($warnings as $warn): ?>
                    <div class="protocol-card"><?php echo htmlspecialchars($warn, ENT_QUOTES); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
