<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$players = load_json('players.json');
$goals = load_json('goals.json');
$status = null;
$error = null;

function find_goal_entry(array $entries, string $scope): ?array
{
    foreach ($entries as $entry) {
        if (($entry['scope'] ?? '') === $scope) {
            return $entry;
        }
    }
    return null;
}

$selectedId = $_GET['player'] ?? ($_POST['player_id'] ?? ($players[0]['id'] ?? null));
$existing = $selectedId ? find_goal_entry($goals, 'player:' . $selectedId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playerId = trim($_POST['player_id'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $goalLines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $_POST['goals_text'] ?? ''))));
    $notes = trim($_POST['notes'] ?? '');

    if ($playerId === '' || empty($goalLines)) {
        $error = 'Оберіть персонажа та додайте хоча б одну ціль.';
    } else {
        $playerName = $playerId;
        foreach ($players as $p) {
            if (($p['id'] ?? '') === $playerId) {
                $playerName = $p['name'] ?? $playerId;
                break;
            }
        }

        $scope = 'player:' . $playerId;
        $updated = false;
        foreach ($goals as &$entry) {
            if (($entry['scope'] ?? '') === $scope) {
                $entry['title'] = $title !== '' ? $title : ('Цілі: ' . $playerName);
                $entry['goals'] = $goalLines;
                $entry['notes'] = $notes;
                $updated = true;
                break;
            }
        }
        unset($entry);

        if (!$updated) {
            $goals[] = [
                'scope' => $scope,
                'title' => $title !== '' ? $title : ('Цілі: ' . $playerName),
                'goals' => $goalLines,
                'notes' => $notes,
            ];
        }

        save_json('goals.json', $goals);
        append_terminal_message('admin_terminal', 'info', '[GOALS] оновлено цілі для ' . $playerId);
        $status = 'Цілі оновлено для ' . htmlspecialchars($playerName, ENT_QUOTES);
        $existing = find_goal_entry($goals, $scope);
        $selectedId = $playerId;
    }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <div class="panel__header">
        <div>
            <p class="micro muted">Сценарні цілі</p>
            <h1>Керування цілями персонажів</h1>
        </div>
        <div class="micro muted">Оновлення потрапляють у персональні кабінети одразу після збереження.</div>
    </div>
    <?php if ($status): ?>
        <div class="alert success"><?php echo $status; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
    <?php endif; ?>
    <form method="post" class="stack">
        <div class="grid cols-2">
            <label>Персонаж
                <select name="player_id" class="form-control">
                    <?php foreach ($players as $player): ?>
                        <option value="<?php echo htmlspecialchars($player['id'], ENT_QUOTES); ?>" <?php echo ($selectedId === $player['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($player['name'], ENT_QUOTES); ?> (<?php echo htmlspecialchars($player['id'], ENT_QUOTES); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Заголовок блоку
                <input class="form-control" name="title" value="<?php echo htmlspecialchars($existing['title'] ?? '', ENT_QUOTES); ?>" placeholder="Цілі: ім’я персонажа">
            </label>
        </div>
        <label>Цілі (по одному на рядок)
            <textarea class="form-control" name="goals_text" rows="6" placeholder="Кожна ціль з нового рядка"><?php echo htmlspecialchars(isset($existing['goals']) ? implode("\n", $existing['goals']) : '', ENT_QUOTES); ?></textarea>
        </label>
        <label>Примітка (опційно)
            <textarea class="form-control" name="notes" rows="2" placeholder="Додаткові інструкції для персонажа"><?php echo htmlspecialchars($existing['notes'] ?? '', ENT_QUOTES); ?></textarea>
        </label>
        <button class="button" type="submit" name="assign_goals" value="1">Зберегти цілі</button>
    </form>
</section>
<section class="panel">
    <div class="panel__header">
        <h2>Поточні персональні цілі</h2>
        <div class="micro muted">Відображаються лише записи зі scope player:ID</div>
    </div>
    <div class="list">
        <?php
        $hasPlayerGoals = false;
        foreach ($goals as $entry):
            if (strpos($entry['scope'] ?? '', 'player:') !== 0) {
                continue;
            }
            $hasPlayerGoals = true;
            $pid = substr($entry['scope'], 7);
            $name = $pid;
            foreach ($players as $pl) {
                if (($pl['id'] ?? '') === $pid) {
                    $name = $pl['name'] ?? $pid;
                    break;
                }
            }
        ?>
            <article class="list-item">
                <div>
                    <div class="micro muted"><?php echo htmlspecialchars($pid, ENT_QUOTES); ?></div>
                    <strong><?php echo htmlspecialchars($name, ENT_QUOTES); ?></strong>
                </div>
                <div>
                    <div class="micro muted">Цілей: <?php echo count($entry['goals'] ?? []); ?></div>
                    <a class="button secondary" href="?player=<?php echo urlencode($pid); ?>">Редагувати</a>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$hasPlayerGoals): ?>
            <p class="muted">Ще немає персональних цілей. Додайте перші через форму вище.</p>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
