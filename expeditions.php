<?php
require_once __DIR__ . '/includes/helpers.php';
$players = load_json('players.json');
$factions = [
    'station' => 'Персонал станції',
    'who' => 'Експедиція ВООЗ',
    'ilaria' => 'ILARIA Corporation'
];

$grouped = [];
foreach ($players as $player) {
    $grouped[$player['faction']][] = $player;
}
include __DIR__ . '/partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Список персоналу станції</h1>
    <div class="overlay-text">registry online</div>
</section>

<section class="grid cols-3">
    <?php foreach ($factions as $key => $label): ?>
        <div class="panel">
            <h3><?php echo $label; ?></h3>
            <?php foreach ($grouped[$key] ?? [] as $person): ?>
                <div class="protocol-card roster-card">
                    <div><strong><?php echo htmlspecialchars($person['name'], ENT_QUOTES); ?></strong> — <?php echo htmlspecialchars($person['role'], ENT_QUOTES); ?></div>
                    <div class="muted">Рівень: <?php echo (int) $person['access_level']; ?></div>
                    <?php
                        $statusClass = [
                            'active' => 'status-active',
                            'search' => 'status-search',
                            'quarantine' => 'status-quarantine',
                            'unknown' => 'status-unknown'
                        ][$person['status']] ?? 'status-unknown';
                        $statusLabel = [
                            'active' => 'активний',
                            'search' => 'в пошуку',
                            'quarantine' => 'в карантині',
                            'unknown' => 'невідомо',
                        ][$person['status']] ?? 'невідомо';
                    ?>
                    <div class="badge <?php echo $statusClass; ?>">Статус: <?php echo $statusLabel; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
