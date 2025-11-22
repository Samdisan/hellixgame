<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$phaseData = current_phase();
$phase = $phaseData;
$timer = timer_status();
$messages = array_slice(array_reverse(load_json('terminal-messages.json')), 0, 8);
$protocols = load_json('protocols.json');
$activeProtocols = count(array_filter($protocols, fn($p) => !empty($p['active'])));
$nextPhaseLabel = $phaseData['next_phase']['label'] ?? ($phaseData['next_phase']['id'] ?? '—');
$toNext = $phaseData['current_meta']['to_next_sec'] ?? null;
$phases = $phaseData['phases'] ?? [];
$phaseIds = array_column($phases, 'id');
$currentPhaseId = $phaseData['current'] ?? null;
$currentIndex = $phaseIds ? array_search($currentPhaseId, $phaseIds, true) : -1;
$phaseRuntime = max(0, ($timer['elapsed'] ?? 0) - ($phaseData['started_elapsed'] ?? 0));
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
$timerTriggers = $timer['time_triggers'] ?? [];
include __DIR__ . '/../partials/header.php';
?>
<section class="panel" data-live-timer>
    <div class="glitch-overlay"></div>
    <h1>Головний командний центр</h1>
    <p class="muted">Ви на містку корабля HELIX. Тут сходяться фаза, час, статуси, протоколи — все, що рухає гру.</p>
    <div class="admin-hero">
        <div class="protocol-card">
            <div class="badge level">Фаза</div>
            <div class="phase-badge"><?php echo htmlspecialchars($phase['current'], ENT_QUOTES); ?></div>
            <div class="meta-line">Плин фази: <span data-phase-elapsed><?php echo human_time((int) ($phase['current_meta']['elapsed_sec'] ?? 0)); ?></span></div>
            <div class="meta-line">До завершення: <span data-phase-remaining><?php echo isset($phase['current_meta']['remaining_sec']) ? human_time((int) $phase['current_meta']['remaining_sec']) : '—'; ?></span></div>
            <div class="meta-line">До наступної: <span data-phase-next><?php echo $toNext !== null ? human_time((int)$toNext) : '—'; ?></span> → <?php echo htmlspecialchars($nextPhaseLabel, ENT_QUOTES); ?></div>
            <div class="meta-line">Одночасно: <span class="active-phase-list" data-active-phases>
                <?php foreach (($phase['active'] ?? []) as $ap): ?>
                    <span class="phase-chip"><?php echo htmlspecialchars($ap['id'], ENT_QUOTES); ?></span>
                <?php endforeach; ?>
            </span></div>
            <div class="glitch-hint">Перемикання фаз запускає каскади подій.</div>
        </div>
        <div class="protocol-card">
            <div class="badge level">Глобальний час</div>
            <div>Статус: <span data-timer-status><?php echo strtoupper($timer['state']); ?></span></div>
            <div>Минуло: <span data-timer-elapsed><?php echo human_time((int)$timer['elapsed']); ?></span></div>
            <div>Залишилось: <span data-timer-remaining><?php echo human_time((int)$timer['remaining']); ?></span></div>
            <form method="post" action="/api/update-timer.php" class="quick-actions" style="margin-top:12px;">
                <input type="hidden" name="redirect" value="/admin/admin.php">
                <button class="button" name="action" value="start" type="submit">Start</button>
                <button class="button secondary" name="action" value="pause" type="submit">Pause</button>
                <button class="button secondary" name="action" value="resume" type="submit">Resume</button>
                <button class="button secondary" name="action" value="reset" type="submit">Reset</button>
            </form>
        </div>
        <a class="protocol-card link-card" href="/admin/admin-protocols.php">
            <div class="badge level">Активні протоколи</div>
            <div style="font-size:24px; font-weight:700; color: var(--accent); text-decoration: underline;">Переглянути (<?php echo $activeProtocols; ?>)</div>
            <div class="glitch-hint">Відкрити повний перелік документів.</div>
        </a>
    </div>
    <div class="overlay-text">bridge online</div>
</section>

<section class="panel" data-live-timer>
    <h2>Фази та квести (об’єднаний хаб)</h2>
    <p class="muted">Управляйте фазами, квестами та таймером без переходу на інші сторінки. Дані оновлюються щосекунди.</p>
    <div class="grid cols-4 phase-live">
        <div class="timeline-item">
            <div class="muted">Статус таймера</div>
            <div class="phase-badge" data-timer-status><?php echo strtoupper($timer['state']); ?></div>
            <div class="micro">Живе оновлення</div>
        </div>
        <div class="timeline-item">
            <div>Минуло: <span data-timer-elapsed><?php echo human_time((int)$timer['elapsed']); ?></span></div>
            <div>Залишилось: <span data-timer-remaining><?php echo human_time((int)$timer['remaining']); ?></span></div>
        </div>
        <div class="timeline-item">
            <div class="muted">Поточна фаза</div>
            <div class="phase-badge"><?php echo htmlspecialchars($currentPhaseId ?? '—', ENT_QUOTES); ?></div>
            <div class="micro">Час у фазі: <span data-phase-elapsed><?php echo human_time((int)$phaseRuntime); ?></span></div>
        </div>
        <div class="timeline-item">
            <div class="muted">До наступної</div>
            <div><span data-phase-next><?php echo $toNext !== null ? human_time((int)$toNext) : '—'; ?></span></div>
            <div class="micro">Далі: <?php echo htmlspecialchars($nextPhaseLabel, ENT_QUOTES); ?></div>
        </div>
    </div>
    <form method="post" action="/api/update-timer.php" class="quick-actions" style="margin-top:12px;">
        <input type="hidden" name="redirect" value="/admin/admin.php">
        <button class="button" name="action" value="start" type="submit">Start</button>
        <button class="button secondary" name="action" value="pause" type="submit">Pause</button>
        <button class="button secondary" name="action" value="resume" type="submit">Resume</button>
        <button class="button secondary" name="action" value="reset" type="submit">Reset</button>
    </form>

    <div class="phase-quest-grid" style="margin-top:16px;">
        <?php if (empty($phases)): ?>
            <div class="protocol-card">
                <div class="muted">Фази ще не додані.</div>
                <div class="micro">Створіть першу фазу, щоб запустити таймлайн.</div>
            </div>
        <?php else: ?>
            <?php foreach ($phases as $idx => $ph): ?>
                <?php
                    $status = 'unassigned';
                    if ($currentIndex !== -1) {
                        if ($idx < $currentIndex) {
                            $status = 'past';
                        } elseif ($idx === $currentIndex) {
                            $status = 'current';
                        } elseif ($idx === $currentIndex + 1) {
                            $status = 'next';
                        } else {
                            $status = 'future';
                        }
                    }
                    $window = $ph['time_window'] ?? [];
                    $plannedStart = $window['planned_start_elapsed_sec'] ?? null;
                    $plannedEnd = $window['planned_end_elapsed_sec'] ?? null;
                    $duration = ($plannedStart !== null && $plannedEnd !== null)
                        ? human_time(max(0, (int)$plannedEnd - (int)$plannedStart))
                        : '—';
                    $phaseQuestList = $questsByPhase[$ph['id']] ?? [];
                ?>
                <div class="protocol-card">
                    <div class="badge level"><?php echo htmlspecialchars($ph['id'], ENT_QUOTES); ?></div>
                    <strong><?php echo htmlspecialchars($ph['label'] ?? ($ph['title'] ?? ''), ENT_QUOTES); ?></strong>
                    <div class="micro muted" style="margin-top:6px;">Статус: <?php echo strtoupper($status); ?> · Заплановано: <?php echo $duration; ?></div>
                    <p class="muted" style="margin:8px 0;"><?php echo htmlspecialchars($ph['description'] ?? '', ENT_QUOTES); ?></p>
                    <?php if (!empty($ph['outcome'])): ?>
                        <div class="badge level" style="margin-bottom:8px;">
                            Результат: <?php echo $ph['outcome'] === 'repaired' ? 'система відремонтована' : 'система не відремонтована'; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($phaseQuestList)): ?>
                        <div class="quest-list">
                            <?php foreach ($phaseQuestList as $quest): ?>
                                <div class="quest-chip">
                                    <div>
                                        <strong><?php echo htmlspecialchars($quest['label'] ?? $quest['id'], ENT_QUOTES); ?></strong>
                                        <div class="micro muted"><?php echo htmlspecialchars($quest['description'] ?? '', ENT_QUOTES); ?></div>
                                    </div>
                                    <form method="post" action="/api/run-quest.php" class="inline">
                                        <input type="hidden" name="quest_id" value="<?php echo htmlspecialchars($quest['id'], ENT_QUOTES); ?>">
                                        <input type="hidden" name="redirect" value="/admin/admin.php">
                                        <button class="button secondary" type="submit">Запустити</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="micro muted">Квести не прив’язані.</div>
                    <?php endif; ?>
                    <div class="micro muted" style="margin-top:8px;">Стартові квести: <?php echo implode(', ', array_map('htmlspecialchars', $ph['on_start_quests'] ?? [])); ?></div>
                    <div class="micro muted">Фінішні квести: <?php echo implode(', ', array_map('htmlspecialchars', $ph['on_end_quests'] ?? [])); ?></div>
                    <div style="margin-top:10px;">
                        <?php if (!empty($currentPhaseId) && $ph['id'] === $currentPhaseId): ?>
                            <span class="badge level">Поточна</span>
                        <?php elseif ($status === 'past'): ?>
                            <span class="micro muted">Вже відпрацьована</span>
                        <?php else: ?>
                            <form method="post" action="/api/set-phase.php" class="inline">
                                <input type="hidden" name="phase" value="<?php echo htmlspecialchars($ph['id'], ENT_QUOTES); ?>">
                                <input type="hidden" name="redirect" value="/admin/admin.php">
                                <button class="button secondary" type="submit">Зробити поточною</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($ph['id'] === 'PH_LIFEFAIL'): ?>
                            <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                                <form method="post" action="/api/set-phase-outcome.php" class="inline">
                                    <input type="hidden" name="phase_id" value="PH_LIFEFAIL">
                                    <input type="hidden" name="outcome" value="repaired">
                                    <input type="hidden" name="redirect" value="/admin/admin.php">
                                    <button class="button secondary" type="submit" <?php echo ($ph['outcome'] ?? null) === 'repaired' ? 'disabled' : ''; ?>>Система відремонтована</button>
                                </form>
                                <form method="post" action="/api/set-phase-outcome.php" class="inline">
                                    <input type="hidden" name="phase_id" value="PH_LIFEFAIL">
                                    <input type="hidden" name="outcome" value="not_repaired">
                                    <input type="hidden" name="redirect" value="/admin/admin.php">
                                    <button class="button secondary" type="submit" <?php echo ($ph['outcome'] ?? null) === 'not_repaired' ? 'disabled' : ''; ?>>Система не відремонтована</button>
                                </form>
                            </div>
                            <div class="micro muted">Фіксуйте фінал фази вручну: відремонтовано або ні.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="panel" style="margin-top:16px;">
        <h3>Часові тригери квестів</h3>
        <?php if (empty($timerTriggers)): ?>
            <p class="muted">Немає time-тригерів.</p>
        <?php else: ?>
            <table class="table dense" data-timer-triggers>
                <thead><tr><th>Умова</th><th>Квест</th><th>Статус</th></tr></thead>
                <tbody>
                    <?php foreach ($timerTriggers as $trigger): ?>
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

    <div class="panel" style="margin-top:16px;">
        <h3>Швидке додавання фази</h3>
        <form class="phase-form" method="post" action="/api/add-phase.php">
            <div class="grid two">
                <label>Ідентифікатор
                    <input required name="id" placeholder="PH_NEW" />
                </label>
                <label>Назва
                    <input required name="label" placeholder="Нова фаза" />
                </label>
            </div>
            <label>Опис
                <textarea required name="description" rows="2" placeholder="Коротке пояснення фази"></textarea>
            </label>
            <div class="grid three">
                <label>Порядок
                    <input name="order" type="number" min="1" step="1" placeholder="<?php echo count($phases) + 1; ?>" />
                </label>
                <label>Плановий старт (elapsed сек)
                    <input name="planned_start_elapsed_sec" type="number" min="0" step="60" placeholder="0" />
                </label>
                <label>Планове завершення (elapsed сек)
                    <input name="planned_end_elapsed_sec" type="number" min="0" step="60" placeholder="900" />
                </label>
            </div>
            <div class="grid two">
                <label>Квести на старті
                    <input name="on_start_quests" placeholder="Q_INTRO" />
                </label>
                <label>Квести при завершенні
                    <input name="on_end_quests" placeholder="Q_NEXT" />
                </label>
            </div>
            <div class="phase-form__footer">
                <input type="hidden" name="redirect" value="/admin/admin.php" />
                <button class="button" type="submit">Додати фазу</button>
            </div>
        </form>
    </div>
</section>

<section class="panel">
    <h2>Останні події</h2>
    <div class="terminal">
        <?php foreach ($messages as $msg): ?>
            <div class="terminal-line line-<?php echo htmlspecialchars($msg['type'], ENT_QUOTES); ?>">
                <span class="muted"><?php echo date('H:i:s', strtotime($msg['timestamp'])); ?></span>
                <span class="badge"><?php echo strtoupper($msg['target']); ?></span>
                <span><?php echo htmlspecialchars($msg['message'], ENT_QUOTES); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
