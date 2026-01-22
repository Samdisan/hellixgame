<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$phase = current_phase();
$timer = timer_status(false);
$message = '';

$triggers = load_message_triggers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $label = trim($_POST['label'] ?? '');
        $pattern = trim($_POST['pattern'] ?? '');
        $response = trim($_POST['response'] ?? '');
        $target = $_POST['target'] ?? 'both';

        if ($pattern !== '' && $response !== '') {
            $triggers[] = [
                'id' => uniqid('trg_', true),
                'label' => $label !== '' ? $label : $pattern,
                'pattern' => $pattern,
                'response' => $response,
                'target' => $target,
            ];
            save_message_triggers($triggers);
            $message = 'Тригер додано.';
        } else {
            $message = 'Потрібен шаблон та відповідь.';
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $before = count($triggers);
        $triggers = array_values(array_filter($triggers, fn($t) => ($t['id'] ?? '') !== $id));
        if (count($triggers) !== $before) {
            save_message_triggers($triggers);
            $message = 'Тригер видалено.';
        }
    }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="panel" data-live-timer>
    <div class="panel-head">
        <h1>Тригери термінала</h1>
        <div class="muted">Автовідповіді на заявки гравців</div>
    </div>

    <?php if ($message): ?>
        <div class="alert"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
    <?php endif; ?>

    <div class="grid two-cols responsive">
        <div>
            <h3>Існуючі тригери</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Назва</th>
                        <th>Шаблон</th>
                        <th>Відповідь</th>
                        <th>Ціль</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($triggers)): ?>
                        <tr><td colspan="5" class="muted">Немає тригерів</td></tr>
                    <?php else: ?>
                        <?php foreach ($triggers as $trigger): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trigger['label'] ?? $trigger['pattern'] ?? '', ENT_QUOTES); ?></td>
                                <td><code><?php echo htmlspecialchars($trigger['pattern'] ?? '', ENT_QUOTES); ?></code></td>
                                <td><?php echo htmlspecialchars($trigger['response'] ?? '', ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($trigger['target'] ?? 'both', ENT_QUOTES); ?></td>
                                <td>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($trigger['id'] ?? '', ENT_QUOTES); ?>">
                                        <button class="button button-ghost" type="submit">Видалити</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div>
            <h3>Додати тригер</h3>
            <form method="post" class="stack">
                <input type="hidden" name="action" value="add">
                <label>Назва (опц.)
                    <input name="label" placeholder="Напр. SOS" />
                </label>
                <label>Шаблон повідомлення
                    <input name="pattern" placeholder="Ключове слово" required />
                </label>
                <label>Відповідь системи
                    <textarea name="response" rows="4" placeholder="Текст, {player} підставить ID" required></textarea>
                </label>
                <label>Куди надсилати
                    <select name="target">
                        <option value="both">Усі термінали</option>
                        <option value="admin_terminal">Лише адмін</option>
                        <option value="public_terminal">Лише публічний</option>
                    </select>
                </label>
                <button class="button" type="submit">Зберегти тригер</button>
            </form>
        </div>
    </div>
</section>
<section class="panel">
    <h2>Додати фазу</h2>
    <p class="muted">Заповніть ключові поля та одразу прив’яжіть квести, що спрацюють на старті або завершенні.</p>
    <form class="phase-form" method="post" action="/api/add-phase.php">
        <div class="grid two">
            <label>Ідентифікатор
                <input required name="id" placeholder="PH_NEW" aria-describedby="idHelp" />
                <div id="idHelp" class="micro muted">Використовуйте префікс PH_ для швидкого пошуку.</div>
            </label>
            <label>Назва
                <input required name="label" placeholder="Нова фаза" />
            </label>
        </div>
        <label>Опис
            <textarea required name="description" rows="3" placeholder="Коротке пояснення фази"></textarea>
        </label>
        <div class="grid three">
            <label>Порядок
                <input name="order" type="number" min="1" step="1" placeholder="<?php echo count($phase['phases'] ?? []) + 1; ?>" />
            </label>
            <label>Плановий старт (elapsed, сек)
                <input name="planned_start_elapsed_sec" type="number" min="0" step="60" placeholder="0" />
            </label>
            <label>Планове завершення (elapsed, сек)
                <input name="planned_end_elapsed_sec" type="number" min="0" step="60" placeholder="900" />
                <div class="micro muted">Використовується для підказки зворотного відліку до наступної фази.</div>
            </label>
            <label>Інтенсивність UI
                <select name="ui_intensity">
                    <option value="">—</option>
                    <option>low</option>
                    <option>medium</option>
                    <option>high</option>
                    <option>critical</option>
                </select>
            </label>
        </div>
        <div class="grid two">
            <label>Квести на старті фази
                <input name="on_start_quests" placeholder="Q_INTRO, Q_START_OUTBREAK" />
                <div class="micro muted">Через кому — ці квести запустяться одразу при активації фази.</div>
            </label>
            <label>Квести при завершенні
                <input name="on_end_quests" placeholder="Q_WRAP_UP" />
                <div class="micro muted">Через кому — ці квести спрацюють коли фаза завершується.</div>
            </label>
        </div>
        <div class="phase-form__footer">
            <div class="micro muted">Збереження одразу додає фазу до таймлайна та показує її в карті фаз/квестів.</div>
            <div>
                <input type="hidden" name="redirect" value="/admin/admin-message-triggers.php" />
                <button class="button" type="submit">Зберегти фазу</button>
            </div>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
