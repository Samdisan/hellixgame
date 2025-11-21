<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$phaseData = current_phase();
$phaseConfig = load_json('phases.json');
$current = $phaseData['current'];
$phaseStarted = $phaseData['started_elapsed'] ?? 0;
$phases = $phaseData['phases'];
$nextPhase = $phaseData['next_phase'] ?? null;
$ids = array_column($phases, 'id');
$currentIndex = $ids ? array_search($current, $ids, true) : -1;
$timer = timer_status();
$subphaseStates = $phaseConfig['subphase_states'] ?? [];
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
$subphaseRows = [];
$globalElapsed = $timer['elapsed'] ?? 0;
foreach ($phases as $phaseCfg) {
    foreach ($phaseCfg['subphases'] ?? [] as $sub) {
        $window = $sub['time_window'] ?? [];
        $startAt = $window['start_elapsed_ge_sec'] ?? null;
        $endAt = $window['end_elapsed_le_sec'] ?? null;
        $state = $subphaseStates[$sub['id'] ?? '']['state'] ?? 'pending';
        $eta = null;
        if ($startAt !== null && $globalElapsed < $startAt) {
            $eta = $startAt - $globalElapsed;
        }
        $subphaseRows[] = [
            'phase' => $phaseCfg,
            'subphase' => $sub,
            'state' => $state,
            'eta' => $eta,
            'startAt' => $startAt,
            'endAt' => $endAt,
        ];
    }
}
usort($subphaseRows, function ($a, $b) {
    return ($a['startAt'] ?? 0) <=> ($b['startAt'] ?? 0);
});
include __DIR__ . '/../partials/header.php';
?>
<section class="panel" data-live-timer>
    <div class="glitch-overlay"></div>
    <h1>Керування фазами</h1>
    <p class="muted">Фази, квести та глобальний таймер тепер на одній панелі. Статус оновлюється наживо.</p>
    <div class="grid cols-4 phase-live">
        <div class="timeline-item">
            <div class="muted">Статус таймера</div>
            <div class="phase-badge" data-timer-status><?php echo strtoupper($timer['state']); ?></div>
            <div class="micro">Оновлюється кожні 3 секунди</div>
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
    <h2>Запуск підфаз і квестів</h2>
    <p class="muted">Показує умови, коли спрацюють підфази, які квести вони запускають, та чи наближається їхній час.</p>
    <div class="grid two">
        <div>
            <h3 class="micro">Підфази за часом</h3>
            <?php if (empty($subphaseRows)): ?>
                <p class="muted">Підфаз не налаштовано.</p>
            <?php else: ?>
                <table class="table dense">
                    <thead><tr><th>Підфаза</th><th>Умови</th><th>Квести</th><th>ETA/Статус</th></tr></thead>
                    <tbody>
                    <?php foreach ($subphaseRows as $row): ?>
                        <?php
                            $sub = $row['subphase'];
                            $phaseCfg = $row['phase'];
                            $window = $sub['time_window'] ?? [];
                            $conds = [];
                            if (isset($window['start_elapsed_ge_sec'])) {
                                $conds[] = 'elapsed ≥ ' . human_time((int)$window['start_elapsed_ge_sec']);
                            }
                            if (isset($window['end_elapsed_le_sec'])) {
                                $conds[] = 'elapsed ≤ ' . human_time((int)$window['end_elapsed_le_sec']);
                            }
                            $questsStart = array_map(fn($q) => $questLookup[$q]['label'] ?? $q, $sub['on_start_quests'] ?? []);
                            $stateLabel = strtoupper($row['state'] ?? 'pending');
                            $etaText = 'готова до запуску';
                            if (($row['state'] ?? '') === 'fired') {
                                $etaText = 'спрацювала';
                            } elseif ($row['eta'] !== null) {
                                $etaText = 'через ' . human_time((int)$row['eta']);
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="micro muted"><?php echo htmlspecialchars($phaseCfg['id'], ENT_QUOTES); ?></div>
                                <strong><?php echo htmlspecialchars($sub['label'] ?? $sub['id'], ENT_QUOTES); ?></strong>
                            </td>
                            <td class="micro muted"><?php echo $conds ? htmlspecialchars(implode(' · ', $conds), ENT_QUOTES) : 'умова не задана'; ?></td>
                            <td class="micro">
                                <?php echo $questsStart ? htmlspecialchars(implode(', ', $questsStart), ENT_QUOTES) : '—'; ?>
                            </td>
                            <td>
                                <span class="badge level"><?php echo $stateLabel; ?></span>
                                <div class="micro muted"><?php echo htmlspecialchars($etaText, ENT_QUOTES); ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div>
            <h3 class="micro">Часові тригери квестів</h3>
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
        </div>
    </div>
</section>

<section class="panel">
    <h2>Активні протоколи</h2>
    <?php if (empty($activeProtocols)): ?>
        <p class="muted">Наразі немає активних протоколів.</p>
    <?php else: ?>
        <p class="muted">Активних документів: <?php echo count($activeProtocols); ?>. Відкрийте повний перелік, щоб відредагувати.</p>
        <a class="button" href="/admin/admin-protocols.php">Відкрити всі протоколи</a>
    <?php endif; ?>
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
                    <?php if (!empty($phase['subphases'])): ?>
                        <div class="micro muted" style="margin-top:8px;">Підфази</div>
                        <ul class="micro">
                            <?php foreach ($phase['subphases'] as $sub): ?>
                                <?php
                                    $state = $subphaseStates[$sub['id'] ?? '']['state'] ?? 'pending';
                                    $window = $sub['time_window'] ?? [];
                                    $parts = [];
                                    if (isset($window['start_elapsed_ge_sec'])) {
                                        $parts[] = '>= ' . human_time((int)$window['start_elapsed_ge_sec']);
                                    }
                                    if (isset($window['end_elapsed_le_sec'])) {
                                        $parts[] = '<= ' . human_time((int)$window['end_elapsed_le_sec']);
                                    }
                                    $cond = $parts ? implode(' · ', $parts) : 'умова не задана';
                                ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($sub['label'] ?? $sub['id'], ENT_QUOTES); ?></strong>
                                    — <?php echo htmlspecialchars($cond, ENT_QUOTES); ?>
                                    <span class="badge level" style="margin-left:4px;"><?php echo strtoupper($state); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
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
