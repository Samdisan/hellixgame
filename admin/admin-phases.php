<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$phaseData = current_phase();
$current = $phaseData['current'];
$phaseStarted = $phaseData['started_elapsed'] ?? 0;
$phases = $phaseData['phases'];
$nextPhase = $phaseData['next_phase'] ?? null;
$ids = array_column($phases, 'id');
$currentIndex = $ids ? array_search($current, $ids, true) : -1;
$timer = timer_status();
$phaseRuntime = max(0, ($timer['elapsed'] ?? 0) - $phaseStarted);
$activeProtocols = array_filter(load_json('protocols.json'), function ($protocol) {
    return $protocol['active'] ?? false;
});
$quests = load_json('quests.json');
$questsByPhase = [];
foreach ($quests as $quest) {
    $phaseId = $quest['phase'] ?? 'unassigned';
    $questsByPhase[$phaseId][] = $quest;
}
$questLookup = [];
foreach ($quests as $quest) {
    $questLookup[$quest['id']] = $quest;
}
$protocolsById = [];
foreach (load_json('protocols.json') as $protocol) {
    $protocolsById[$protocol['id']] = $protocol;
}
include __DIR__ . '/../partials/header.php';
?>
<section class="panel" data-live-timer>
    <div class="glitch-overlay"></div>
    <h1>Керування фазами</h1>
    <p class="muted">Фази, квести та глобальний таймер тепер на одній панелі. Статус оновлюється наживо.</p>
    <?php if (($_GET['error'] ?? '') === 'reset_code'): ?>
        <div class="notice error">Невірний код reset. Використайте 30071992, щоб скинути таймер і фази.</div>
    <?php endif; ?>
    <div class="grid cols-4 phase-live">
        <div class="timeline-item">
            <div class="muted">Статус таймера</div>
            <div class="phase-badge" data-timer-status><?php echo strtoupper($timer['state']); ?></div>
            <div class="micro">Оновлюється щосекунди</div>
        </div>
        <div class="timeline-item">
            <div>Минуло: <span data-timer-elapsed><?php echo human_time((int)$timer['elapsed']); ?></span></div>
            <div>Залишилось: <span data-timer-remaining><?php echo human_time((int)$timer['remaining']); ?></span></div>
        </div>
        <div class="timeline-item">
            <div class="muted">Поточна фаза</div>
            <div class="phase-badge"><?php echo htmlspecialchars($current ?? '—', ENT_QUOTES); ?></div>
            <div class="micro">Час у фазі: <?php echo human_time((int)$phaseRuntime); ?></div>
        </div>
        <div class="timeline-item">
            <div class="muted">До наступної</div>
            <div><span data-phase-next><?php echo isset($phaseData['current_meta']['to_next_sec']) ? human_time((int)$phaseData['current_meta']['to_next_sec']) : '—'; ?></span></div>
            <div class="micro">Далі: <?php echo htmlspecialchars($nextPhase['label'] ?? ($nextPhase['id'] ?? '—'), ENT_QUOTES); ?></div>
        </div>
    </div>
    <form method="post" action="/api/update-timer.php" class="quick-actions" style="margin-top:12px;">
        <input type="hidden" name="redirect" value="/admin/admin-phases.php">
        <div class="reset-guard">
            <label for="reset-code-phases" class="micro muted">Код reset (лише для скидання)</label>
            <input id="reset-code-phases" name="reset_code" type="password" inputmode="numeric" autocomplete="off" placeholder="30071992">
        </div>
        <button class="button" name="action" value="start" type="submit">Start</button>
        <button class="button secondary" name="action" value="pause" type="submit">Pause</button>
        <button class="button secondary" name="action" value="resume" type="submit">Resume</button>
        <button class="button secondary" name="action" value="reset" type="submit">Reset</button>
    </form>
</section>

<section class="panel">
    <h2>Фази</h2>
    <?php if (empty($phases)): ?>
        <p class="muted">Фаз поки немає. Додайте першу фазу нижче, щоб запустити таймлайн.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>ID</th><th>Назва</th><th>Опис</th><th>Статус</th><th>План часу</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($phases as $idx => $phase): ?>
                    <?php
                        if ($currentIndex === -1) {
                            $status = 'unassigned';
                        } elseif ($idx < $currentIndex) {
                            $status = 'past';
                        } elseif ($idx === $currentIndex) {
                            $status = 'current';
                        } elseif ($idx === $currentIndex + 1) {
                            $status = 'next';
                        } else {
                            $status = 'future';
                        }
                        $startQuests = $phase['on_start_quests'] ?? [];
                        $endQuests = $phase['on_end_quests'] ?? [];
                    ?>
                    <?php
                        $window = $phase['time_window'] ?? [];
                        $plannedStart = $window['planned_start_elapsed_sec'] ?? null;
                        $plannedEnd = $window['planned_end_elapsed_sec'] ?? null;
                        $plannedDuration = ($plannedStart !== null && $plannedEnd !== null) ? max(0, $plannedEnd - $plannedStart) : null;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($phase['id'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($phase['label'] ?? '', ENT_QUOTES); ?></td>
                        <td>
                            <?php echo htmlspecialchars($phase['description'] ?? '', ENT_QUOTES); ?>
                            <?php if (!empty($startQuests) || !empty($endQuests)): ?>
                                <div class="micro muted">
                                    <?php if (!empty($startQuests)): ?>На старті: <?php echo implode(', ', array_map('htmlspecialchars', $startQuests)); ?><?php endif; ?>
                                    <?php if (!empty($startQuests) && !empty($endQuests)): ?> · <?php endif; ?>
                                    <?php if (!empty($endQuests)): ?>На завершенні: <?php echo implode(', ', array_map('htmlspecialchars', $endQuests)); ?><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge level"><?php echo strtoupper($status); ?></span></td>
                        <td>
                            <?php if (!empty($current) && $phase['id'] === $current): ?>
                                <?php echo human_time((int) $phaseRuntime); ?>
                                <?php if ($plannedEnd !== null): ?>
                                    <div class="micro muted">до кінця вікна: <?php echo human_time(max(0, $plannedEnd - ($timer['elapsed'] ?? 0))); ?></div>
                                <?php endif; ?>
                            <?php elseif ($plannedDuration !== null): ?>
                                заплановано: <?php echo human_time((int) $plannedDuration); ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($current) && $phase['id'] === $current): ?>
                                Поточна
                            <?php elseif ($status === 'past'): ?>
                                <span class="micro muted">Вже відпрацьована</span>
                            <?php else: ?>
                                <form method="post" action="/api/set-phase.php">
                                    <input type="hidden" name="phase" value="<?php echo htmlspecialchars($phase['id'], ENT_QUOTES); ?>">
                                    <input type="hidden" name="redirect" value="/admin/admin-phases.php">
                                    <button class="button secondary" type="submit">Зробити поточною</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Часові тригери квестів</h2>
    <?php $triggers = $timer['time_triggers'] ?? []; ?>
    <?php if (empty($triggers)): ?>
        <p class="muted">Немає time-тригерів.</p>
    <?php else: ?>
        <table class="table dense" data-timer-triggers>
            <thead><tr><th>Умова</th><th>Квест</th><th>Статус</th></tr></thead>
            <tbody>
                <?php foreach ($triggers as $trigger): ?>
                    <?php
                        $conds = [];
                        if ($trigger['elapsed_ge_sec'] !== null && $trigger['elapsed_ge_sec'] !== '') {
                            $conds[] = 'elapsed ≥ ' . human_time((int)$trigger['elapsed_ge_sec']);
                        }
                        if ($trigger['remaining_le_sec'] !== null && $trigger['remaining_le_sec'] !== '') {
                            $conds[] = 'remaining ≤ ' . human_time((int)$trigger['remaining_le_sec']);
                        }
                        $questLabel = $questLookup[$trigger['quest_id']]['label'] ?? ($trigger['quest_id'] ?? '');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars(implode(' & ', $conds), ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($questLabel, ENT_QUOTES); ?></td>
                        <td><span class="badge level"><?php echo !empty($trigger['fired']) ? 'FIRED' : 'PENDING'; ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Активні протоколи</h2>
    <a class="button" href="/admin/admin-protocols.php">Активні протоколи</a>
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
                <input name="order" type="number" min="1" step="1" placeholder="<?php echo count($phases) + 1; ?>" />
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
                <input type="hidden" name="redirect" value="/admin/admin-phases.php" />
                <button class="button" type="submit">Зберегти фазу</button>
            </div>
        </div>
    </form>
</section>

<section class="panel">
    <h2>Карта фаз, умов та квестів</h2>
    <?php if (empty($phases)): ?>
        <p class="muted">Додайте хоча б одну фазу, щоб прив'язати квести.</p>
    <?php else: ?>
        <div class="phase-quest-grid">
            <?php foreach ($phases as $phase): ?>
                <?php
                    $phaseQuestList = $questsByPhase[$phase['id']] ?? [];
                    $window = $phase['time_window'] ?? [];
                    $plannedStart = $window['planned_start_elapsed_sec'] ?? null;
                    $plannedEnd = $window['planned_end_elapsed_sec'] ?? null;
                    $duration = ($plannedStart !== null && $plannedEnd !== null) ? human_time(max(0, (int)$plannedEnd - (int)$plannedStart)) : '—';
                    $isCurrent = $phase['id'] === $current;
                ?>
                <div class="protocol-card">
                    <div class="badge level"><?php echo htmlspecialchars($phase['id'], ENT_QUOTES); ?></div>
                    <strong><?php echo htmlspecialchars($phase['label'] ?? $phase['title'] ?? '', ENT_QUOTES); ?></strong>
                    <div class="micro muted" style="margin-top:6px;">Тривалість: <?php echo $duration; ?><?php echo $isCurrent ? ' · активна' : ''; ?></div>
                    <div class="micro muted">Наступна: <?php echo htmlspecialchars($nextPhase['label'] ?? ($nextPhase['id'] ?? '—'), ENT_QUOTES); ?> (ручний перехід)</div>
                    <p class="muted" style="margin:8px 0;"><?php echo htmlspecialchars($phase['description'] ?? '', ENT_QUOTES); ?></p>
                    <?php if (!empty($phase['long_description'])): ?>
                        <p class="micro muted"><?php echo htmlspecialchars($phase['long_description'], ENT_QUOTES); ?></p>
                    <?php endif; ?>
                    <div class="chips" style="margin-top:8px;">
                        <span class="chip">UI: <?php echo htmlspecialchars($phase['ui_intensity'] ?? '—', ENT_QUOTES); ?></span>
                        <span class="chip">Hints: <?php echo htmlspecialchars($phase['hint_frequency'] ?? '—', ENT_QUOTES); ?></span>
                    </div>
                    <div class="quest-list">
                        <?php if (!empty($phaseQuestList)): ?>
                            <?php foreach ($phaseQuestList as $quest): ?>
                                <div class="quest-chip">
                                    <div>
                                        <strong><?php echo htmlspecialchars($quest['label'] ?? $quest['id'], ENT_QUOTES); ?></strong>
                                        <div class="micro muted"><?php echo htmlspecialchars($quest['description'] ?? '', ENT_QUOTES); ?></div>
                                    </div>
                                    <form method="post" action="/api/run-quest.php" class="inline">
                                        <input type="hidden" name="quest_id" value="<?php echo htmlspecialchars($quest['id'], ENT_QUOTES); ?>">
                                        <input type="hidden" name="redirect" value="/admin/admin-phases.php">
                                        <button class="button secondary" type="submit">Запустити</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="micro muted">Немає квестів для цієї фази.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
