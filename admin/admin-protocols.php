<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$protocols = load_json('protocols.json');
$players = load_json('players.json');

function parse_allowed_players($input): array {
    if (is_array($input)) {
        return array_values(array_filter(array_map('trim', $input)));
    }
    $raw = trim((string)$input);
    if ($raw === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw))));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle'])) {
        $id = $_POST['toggle'];
        foreach ($protocols as &$protocol) {
            if ($protocol['id'] === $id) {
                $field = $_POST['field'];
                $protocol[$field] = !$protocol[$field];
                append_terminal_message('admin_terminal', 'info', "[PROTOCOL] {$field} переключено для {$id}");
            }
        }
        unset($protocol);
    } elseif (isset($_POST['save_allowed'])) {
        $id = $_POST['save_allowed'];
        foreach ($protocols as &$protocol) {
            if ($protocol['id'] === $id) {
                $protocol['allowed_players'] = parse_allowed_players($_POST['allowed_players'] ?? []);
                append_terminal_message('admin_terminal', 'info', "[PROTOCOL] призначені гравці для {$id}");
            }
        }
        unset($protocol);
    } elseif (isset($_POST['new_protocol'])) {
        $protocols[] = [
            'id' => $_POST['id'],
            'label' => $_POST['label'],
            'description' => $_POST['description'],
            'content' => $_POST['content'],
            'level' => (int) $_POST['level'],
            'public' => !empty($_POST['public']),
            'phase' => $_POST['phase'],
            'active' => !empty($_POST['active']),
            'publish_time' => gmdate('c'),
            'allowed_players' => parse_allowed_players($_POST['allowed_players'] ?? [])
        ];
        append_terminal_message('admin_terminal', 'protocol', 'Додано протокол ' . $_POST['id']);
    }
    save_json('protocols.json', $protocols);
    header('Location: admin-protocols.php');
    exit;
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>Протоколи системи</h1>
    <p class="muted">Таблиця секретних документів. Створюйте, вмикайте, оголошуйте через термінал — тут ви формуєте офіційну правду станції.</p>
    <table class="table">
        <thead><tr><th>ID</th><th>Label</th><th>Level</th><th>Public</th><th>Active</th><th>Фаза</th><th>Гравці</th><th>Дії</th></tr></thead>
        <tbody>
            <?php foreach ($protocols as $protocol): ?>
                <tr>
                    <td><?php echo htmlspecialchars($protocol['id'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($protocol['label'], ENT_QUOTES); ?></td>
                    <td><?php echo (int)$protocol['level']; ?></td>
                    <td><?php echo !empty($protocol['public']) ? 'yes' : 'no'; ?></td>
                    <td><?php echo !empty($protocol['active']) ? 'yes' : 'no'; ?></td>
                    <td><?php echo htmlspecialchars($protocol['phase'], ENT_QUOTES); ?></td>
                    <td>
                        <?php if (empty($protocol['allowed_players'])): ?>
                            <span class="muted">Всі з рівнем доступу</span>
                        <?php else: ?>
                            <div class="chips">
                                <?php foreach ($protocol['allowed_players'] as $pid): ?>
                                    <span class="chip"><?php echo htmlspecialchars($pid, ENT_QUOTES); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <form class="stack" method="post" style="margin-top:6px;">
                            <input type="hidden" name="save_allowed" value="<?php echo htmlspecialchars($protocol['id'], ENT_QUOTES); ?>">
                            <div class="chip-select">
                                <?php foreach ($players as $player): ?>
                                    <label class="chip checkbox-chip">
                                        <input type="checkbox" name="allowed_players[]" value="<?php echo htmlspecialchars($player['id'], ENT_QUOTES); ?>" <?php echo in_array($player['id'], $protocol['allowed_players'] ?? [], true) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($player['name'], ENT_QUOTES); ?>
                                        <span class="micro muted"><?php echo htmlspecialchars($player['id'], ENT_QUOTES); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="micro muted">Якщо не обрано жодного — протокол бачать усі, хто має потрібний рівень.</div>
                            <button class="button secondary" type="submit">Зберегти перелік</button>
                        </form>
                    </td>
                    <td>
                        <form class="inline" method="post">
                            <input type="hidden" name="field" value="public">
                            <button class="button secondary" name="toggle" value="<?php echo htmlspecialchars($protocol['id'], ENT_QUOTES); ?>" type="submit">Public</button>
                        </form>
                        <form class="inline" method="post">
                            <input type="hidden" name="field" value="active">
                            <button class="button secondary" name="toggle" value="<?php echo htmlspecialchars($protocol['id'], ENT_QUOTES); ?>" type="submit">Active</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<section class="panel">
    <h2>Додати протокол</h2>
    <form method="post">
        <input type="hidden" name="new_protocol" value="1">
        <div class="grid cols-2">
            <div>
                <label>ID<br><input class="form-control" name="id" required></label>
            </div>
            <div>
                <label>Label<br><input class="form-control" name="label" required></label>
            </div>
            <div>
                <label>Level<br><input class="form-control" type="number" name="level" value="1" min="1" max="5"></label>
            </div>
            <div>
                <label>Фаза<br><input class="form-control" name="phase" value="PH_INTRO"></label>
            </div>
        </div>
        <label>Опис<br><textarea class="form-control" name="description" rows="2"></textarea></label>
        <label>Контент<br><textarea class="form-control" name="content" rows="3"></textarea></label>
        <div style="margin-top:8px;">
            <label><input type="checkbox" name="public" value="1"> Public</label>
            <label style="margin-left:12px;"><input type="checkbox" name="active" value="1" checked> Active</label>
        </div>
        <label style="margin-top:8px; display:block;">Дозволені гравці (опційно)
            <select class="form-control" name="allowed_players[]" multiple size="8">
                <?php foreach ($players as $player): ?>
                    <option value="<?php echo htmlspecialchars($player['id'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($player['name'] . ' — ' . $player['id'], ENT_QUOTES); ?></option>
                <?php endforeach; ?>
            </select>
            <span class="micro muted">Якщо пусто — протокол доступний усім з достатнім рівнем.</span>
        </label>
        <button class="button" type="submit">Створити</button>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
