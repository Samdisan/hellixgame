function startTerminalFeed(selector, fallbackTarget = 'public_terminal') {
    const container = document.querySelector(selector);
    if (!container) return;

    if (container.dataset.feedStarted) return;
    container.dataset.feedStarted = '1';

    const target = container.dataset.target || fallbackTarget;
    const playerId = container.dataset.playerId || '';
    const canDelete = container.dataset.canDelete === '1';
    const pollMs = parseInt(container.dataset.pollMs || '6000', 10);

    async function refresh() {
        try {
            const query = new URLSearchParams({
                target,
                ts: Date.now().toString(),
            });
            if (playerId) {
                query.set('player_id', playerId);
            }
            const res = await fetch('/api/get-terminal-messages.php?' + query.toString());
            const payload = await res.json();
            const filtered = (payload.messages || [])
                .sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
            container.innerHTML = filtered
                .map((line) => renderLine(line))
                .join('');
        } catch (e) {
            container.innerHTML = '<div class="terminal-line line-warning">[нет доступу до стрічки]</div>';
        }
    }

    async function deleteMessage(id, btn) {
        if (!id) return;
        btn?.setAttribute('disabled', 'disabled');
        const form = new FormData();
        form.append('id', id);
        try {
            await fetch('/api/delete-terminal-message.php', { method: 'POST', body: form });
            refresh();
        } catch (e) {
            // ignore
        } finally {
            btn?.removeAttribute('disabled');
        }
    }

    function renderLine(line) {
        const ts = new Date(line.timestamp).toLocaleTimeString('uk-UA', { hour12: false });
        const typeClass = 'line-' + (line.type || 'info');
        const body = escapeHtml(line.message || '');
        const badge = escapeHtml(line.type || '');
        const deleteBtn = canDelete ? `<button class="terminal-delete" type="button" data-delete-id="${escapeHtml(line.id || '')}" aria-label="Видалити">✕</button>` : '';
        return `<div class="terminal-line ${typeClass}" data-message-id="${escapeHtml(line.id || '')}">` +
            `<span class="muted">${ts}</span>` +
            `<span class="badge">${badge}</span>` +
            `<span class="terminal-line__body">${body}${deleteBtn}</span>` +
            `</div>`;
    }

    container.addEventListener('click', (evt) => {
        const btn = evt.target.closest('[data-delete-id]');
        if (!btn) return;
        deleteMessage(btn.dataset.deleteId, btn);
    });

    refresh();
    setInterval(refresh, isNaN(pollMs) ? 6000 : Math.max(1000, pollMs));
}

function startRotators() {
    document.querySelectorAll('[data-rotator]').forEach((el) => {
        const messages = (el.dataset.rotator || '').split('|').filter(Boolean);
        if (!messages.length) return;
        let index = 0;
        el.textContent = messages[index];
        setInterval(() => {
            index = (index + 1) % messages.length;
            el.textContent = messages[index];
        }, 3200);
    });
}

function startHintTicker() {
    const windowEl = document.querySelector('.glitch-hint-window');
    if (!windowEl) return;
    const target = windowEl.querySelector('.glitch-hint');
    let hints = [];
    try {
        hints = JSON.parse(windowEl.dataset.hints || '[]');
    } catch (e) {
        hints = [];
    }
    if (!target || !hints.length) return;

    let index = 0;
    const showHint = () => {
        target.textContent = hints[index];
        windowEl.classList.add('is-visible');
        const visibleFor = 5000 + Math.floor(Math.random() * 2000);
        setTimeout(() => {
            windowEl.classList.remove('is-visible');
            index = (index + 1) % hints.length;
            setTimeout(showHint, 500);
        }, visibleFor);
    };

    showHint();
}

function setupPlayerTerminalForm() {
    const form = document.querySelector('[data-player-terminal-form]');
    if (!form) return;

    const input = form.querySelector('input[name="message"]');
    const status = form.querySelector('[data-terminal-status]');

    form.addEventListener('submit', async (evt) => {
        evt.preventDefault();
        if (!input || input.value.trim() === '') return;

        const formData = new FormData();
        formData.append('message', input.value.trim());

        form.classList.add('is-sending');
        status.textContent = 'Відправка...';

        try {
            const res = await fetch('/api/player-terminal-message.php', { method: 'POST', body: formData });
            const payload = await res.json();
            if (!res.ok || payload.error) {
                status.textContent = 'Помилка: ' + (payload.error || res.statusText);
            } else {
                status.textContent = 'Надіслано у термінали станції.';
                input.value = '';
                startTerminalFeed('.terminal-feed');
            }
        } catch (e) {
            status.textContent = 'Помилка з’єднання. Спробуйте ще раз.';
        } finally {
            form.classList.remove('is-sending');
            setTimeout(() => status.textContent = '', 2500);
        }
    });
}

function setupGoalToggles() {
    const checkboxes = document.querySelectorAll('input[data-goal-key]');
    if (!checkboxes.length) return;

    checkboxes.forEach((box) => {
        box.addEventListener('change', async (e) => {
            const goalKey = e.target.dataset.goalKey || '';
            if (!goalKey) return;

            const desired = box.checked;
            box.disabled = true;

            try {
                const form = new FormData();
                form.append('goal_key', goalKey);
                form.append('completed', desired ? '1' : '0');
                const res = await fetch('/api/toggle-goal.php', {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin',
                });
                const payload = await res.json();
                if (!payload.success) {
                    throw new Error(payload.error || 'failed');
                }

                const keys = payload.completed_keys || [];
                box.checked = keys.includes(goalKey);
            } catch (err) {
                box.checked = !desired;
                alert('Не вдалося оновити статус цілі. Спробуйте ще раз.');
            } finally {
                box.disabled = false;
            }
        });
    });
}

function setupAccessVotes() {
    const table = document.querySelector('[data-access-table]');
    if (!table) return;

    const status = document.createElement('div');
    status.className = 'muted micro';
    status.style.margin = '8px 0';
    table.parentElement?.insertBefore(status, table);

    table.addEventListener('click', async (evt) => {
        const btn = evt.target.closest('[data-approve]');
        if (!btn) return;
        const row = btn.closest('tr');
        if (!row) return;

        btn.disabled = true;
        const target = btn.dataset.target;

        const form = new FormData();
        form.append('target', target);

        try {
            const res = await fetch('/api/access-vote.php', { method: 'POST', body: form });
            const payload = await res.json();
            if (!res.ok || payload.error) {
                let msg = payload.message || payload.error || res.statusText;
                if (payload.retry_in) {
                    const minutes = Math.max(1, Math.ceil(payload.retry_in / 60));
                    msg += ` (спробуйте через ~${minutes} хв)`;
                }
                status.textContent = 'Помилка: ' + msg;
                btn.disabled = false;
                return;
            }

            row.querySelector('[data-approvals-count]').textContent = payload.approvals;
            row.dataset.approvals = payload.approvals;
            row.dataset.level = payload.new_level;
            row.querySelector('.badge.level').textContent = payload.new_level;

            const stamp = document.createElement('span');
            stamp.className = 'muted micro';

            const remaining = typeof payload.remaining === 'number' ? payload.remaining : null;

            if (payload.leveled_up) {
                if (payload.ross_override_used) {
                    status.textContent = `Рівень оновлено до ${payload.new_level}. Одноосібне підвищення (Глен Росс).`;
                } else if (payload.who_override_used) {
                    status.textContent = `Рівень оновлено до ${payload.new_level}. Одноосібне підвищення (програміст ВООЗ).`;
                } else {
                    status.textContent = `Рівень оновлено до ${payload.new_level}. Голоси очищено.`;
                }
                stamp.textContent = 'Підвищено';
            } else {
                status.textContent = `Ваш голос зафіксовано. ${payload.approvals}/3 підтверджень.`;
                if (remaining !== null) {
                    status.textContent += ` | Залишилось підвищень: ${remaining}`;
                }
                stamp.textContent = 'Ваш голос зафіксовано';
            }

            btn.replaceWith(stamp);
        } catch (e) {
            status.textContent = 'Помилка з’єднання. Спробуйте ще раз.';
            btn.disabled = false;
        }
    });
}

function setupProtocolPopups() {
    const modal = document.querySelector('.protocol-modal');
    if (!modal) return;
    const overlay = modal.querySelector('.protocol-modal__overlay');
    const title = modal.querySelector('[data-modal-title]');
    const meta = modal.querySelector('[data-modal-meta]');
    const body = modal.querySelector('[data-modal-body]');
    const shareActions = modal.querySelector('[data-share-actions]');
    const shareStatus = modal.querySelector('[data-share-status]');
    const shareSend = modal.querySelector('[data-share-send]');
    const shareCancel = modal.querySelector('[data-share-cancel]');
    let currentShareId = null;
    let currentShareTrigger = null;

    const close = () => modal.classList.remove('open');
    const banner = document.querySelector('#new-protocol-banner');

    function decrementNewBanner() {
        if (!banner) return;
        const valueEl = banner.querySelector('[data-new-count-value]');
        const current = parseInt(banner.dataset.newCount || '0', 10);
        if (Number.isNaN(current) || current <= 0) return;
        const next = Math.max(0, current - 1);
        banner.dataset.newCount = String(next);
        if (valueEl) valueEl.textContent = next;
        if (next === 0) {
            banner.classList.add('hidden');
        }
    }
    modal.querySelectorAll('.protocol-modal__close, .protocol-modal__overlay').forEach((btn) => {
        btn.addEventListener('click', close);
    });

    document.addEventListener('click', async (evt) => {
        const trigger = evt.target.closest('.protocol-open');
        if (!trigger) return;
        const id = trigger.dataset.protocolId || '';
        const label = trigger.dataset.protocolLabel || 'Без назви';
        const level = trigger.dataset.protocolLevel || '';
        const phase = trigger.dataset.protocolPhase || '';
        const description = trigger.dataset.protocolDescription || '';
        const content = trigger.dataset.protocolContent || '';
        const locked = trigger.dataset.locked === '1';
        const isRedacted = trigger.dataset.protocolRedacted === '1';
        const isShareable = trigger.dataset.protocolShareable === '1';
        const isBroadcasted = trigger.dataset.protocolBroadcasted === '1';
        const markUrl = trigger.dataset.markUrl;

        modal.classList.toggle('redacted', isRedacted);
        title.textContent = `${label} (${id})`;
        meta.textContent = `Рівень ${level} · Фаза ${phase}${locked ? ' · лише перегляд' : ''}`;
        body.textContent = description;

        modal.querySelector('[data-modal-content]').textContent = content;
        modal.classList.add('open');

        if (shareActions) {
            shareActions.hidden = !(isShareable && !isBroadcasted);
            if (shareStatus) shareStatus.textContent = '';
            currentShareId = shareActions.hidden ? null : id;
            currentShareTrigger = shareActions.hidden ? null : trigger;
            if (shareSend) shareSend.disabled = false;
            if (shareCancel) shareCancel.disabled = false;
        }

        if (markUrl && !locked) {
            try {
                const card = trigger.closest('.protocol-card');
                const isNew = card?.classList.contains('new');
                if (isNew && trigger.dataset.marked !== '1') {
                    const form = new FormData();
                    form.append('protocol', id);
                    form.append('mode', 'json');
                    await fetch(markUrl, { method: 'POST', body: form });
                    card.classList.remove('new');
                    trigger.dataset.marked = '1';
                    decrementNewBanner();
                }
            } catch (e) {
                // ignore marking errors in UI
            }
        }
    });

    document.addEventListener('keydown', (evt) => {
        if (evt.key === 'Escape') close();
    });

    shareSend?.addEventListener('click', async () => {
        if (!currentShareId) return;
        shareSend.disabled = true;
        shareCancel && (shareCancel.disabled = true);
        if (shareStatus) shareStatus.textContent = 'Надсилання…';
        const form = new FormData();
        form.append('protocol', currentShareId);
        try {
            const res = await fetch('/api/broadcast-protocol.php', { method: 'POST', body: form });
            const payload = await res.json();
            if (!res.ok || payload.error) {
                if (shareStatus) shareStatus.textContent = payload.message || payload.error || 'Помилка розсилки';
                shareSend.disabled = false;
                shareCancel && (shareCancel.disabled = false);
                return;
            }
            if (shareStatus) shareStatus.textContent = 'Розіслано. Пошкоджена копія доступна команді.';
            shareActions.hidden = true;
            if (currentShareTrigger) {
                currentShareTrigger.dataset.protocolBroadcasted = '1';
            }
        } catch (e) {
            if (shareStatus) shareStatus.textContent = 'Проблема зі з’єднанням. Спробуйте ще раз.';
            shareSend.disabled = false;
            shareCancel && (shareCancel.disabled = false);
        }
    });

    shareCancel?.addEventListener('click', () => {
        if (!shareActions) return;
        if (shareStatus) shareStatus.textContent = 'Вирішено не розсилати.';
        shareActions.hidden = true;
    });
}

function startLiveTimer() {
    const containers = document.querySelectorAll('[data-live-timer]');
    if (window.__helixTimerLoop) return;
    window.__helixTimerLoop = true;

    function syncBodyClasses(payload) {
        const lifeFail = payload?.phases?.life_support || null;
        const currentPhase = (payload?.phases?.current || '').toLowerCase();
        const lifeActive = Boolean(lifeFail?.active);
        const uiMode = payload?.phases?.ui_mode || 'normal';

        const phaseClasses = Array.from(document.body.classList).filter((c) => c.startsWith('phase-'));
        phaseClasses.forEach((c) => document.body.classList.remove(c));
        document.body.classList.remove('intensity-critical', 'intensity-warning');

        if (lifeActive) {
            document.body.classList.add('phase-ph_lifefail', 'intensity-critical');
            return;
        }

        if (currentPhase) {
            document.body.classList.add(`phase-${currentPhase}`);
        }

        if (uiMode === 'warning') {
            document.body.classList.add('intensity-warning');
        } else if (uiMode === 'critical') {
            document.body.classList.add('intensity-critical');
        } else {
            document.body.classList.remove('intensity-warning', 'intensity-critical');
        }
    }

    async function refresh() {
        try {
            const res = await fetch('/api/get-state.php?ts=' + Date.now());
            const payload = await res.json();
            const lifeFail = payload.phases?.life_support || null;
            const uiMode = payload.phases?.ui_mode || 'normal';
            const lifeActive = Boolean(lifeFail?.active);
            const repaired = (lifeFail?.outcome || '') === 'repaired';
            syncBodyClasses(payload);
            containers.forEach((container) => {
                const elapsedEl = container.querySelector('[data-timer-elapsed]');
                const remainingEl = container.querySelector('[data-timer-remaining]');
                const statusEl = container.querySelector('[data-timer-status]');
                const phaseElapsedEl = container.querySelector('[data-phase-elapsed]');
                const phaseRemainingEl = container.querySelector('[data-phase-remaining]');
                const phaseNextEl = container.querySelector('[data-phase-next]');
                const activePhasesEl = container.querySelector('[data-active-phases]');
                const triggersTable = container.querySelector('[data-timer-triggers] tbody');
                const lifefailBlock = container.querySelector('[data-lifefail-block]');
                const lifefailElapsedEl = container.querySelector('[data-lifefail-elapsed]');
                const lifefailRemainingEl = container.querySelector('[data-lifefail-remaining]');
                const lifefailStatusEl = container.querySelector('[data-lifefail-status]');

                if (elapsedEl && payload.timer) {
                    elapsedEl.textContent = formatHuman(payload.timer.elapsed);
                }
                if (remainingEl && payload.timer) {
                    remainingEl.textContent = formatHuman(payload.timer.remaining);
                }
                if (statusEl && payload.timer) {
                    statusEl.textContent = (payload.timer.state || '').toUpperCase();
                }
                if (phaseElapsedEl && payload.phases && payload.phases.current_meta) {
                    phaseElapsedEl.textContent = formatHuman(payload.phases.current_meta.elapsed_sec);
                }
                if (phaseRemainingEl && payload.phases && payload.phases.current_meta) {
                    const remain = payload.phases.current_meta.remaining_sec;
                    phaseRemainingEl.textContent = typeof remain === 'number' ? formatHuman(remain) : '—';
                }
                if (phaseNextEl && payload.phases && payload.phases.current_meta) {
                    const next = payload.phases.current_meta.to_next_sec;
                    phaseNextEl.textContent = typeof next === 'number' ? formatHuman(next) : '—';
                }
                if (activePhasesEl && payload.phases && Array.isArray(payload.phases.active)) {
                    activePhasesEl.innerHTML = payload.phases.active.map((ap) => {
                        const remain = ap.remaining_sec !== null && ap.remaining_sec !== undefined ? formatHuman(ap.remaining_sec) : '—';
                        const title = ap.title || ap.id || '';
                        return `<div class="phase-chip"><span>${escapeHtml(ap.id || '')}</span><span class="micro muted">${remain}</span><span class="micro">${escapeHtml(title)}</span></div>`;
                    }).join('');
                    if (!payload.phases.active.length) {
                        activePhasesEl.innerHTML = '<div class="micro muted">—</div>';
                    }
                }
                if (triggersTable && payload.timer && Array.isArray(payload.timer.time_triggers)) {
                    triggersTable.innerHTML = payload.timer.time_triggers.map((trigger) => {
                        const conds = [];
                        if (trigger.elapsed_ge_sec !== undefined && trigger.elapsed_ge_sec !== null) {
                            conds.push(`elapsed >= ${formatHuman(trigger.elapsed_ge_sec)}`);
                        }
                        if (trigger.remaining_le_sec !== undefined && trigger.remaining_le_sec !== null) {
                            conds.push(`remaining <= ${formatHuman(trigger.remaining_le_sec)}`);
                        }
                        return `<tr><td>${conds.join(' & ')}</td><td>${trigger.quest_id || ''}</td></tr>`;
                    }).join('');
                }

                if (lifefailBlock) {
                    const active = Boolean(lifeFail?.active);
                    lifefailBlock.hidden = !active;
                    if (lifefailStatusEl) lifefailStatusEl.textContent = active ? 'АКТИВНО' : '—';
                    if (lifefailElapsedEl) lifefailElapsedEl.textContent = active ? formatHuman(lifeFail.elapsed_sec || 0) : '—';
                    if (lifefailRemainingEl) {
                        if (active && lifeFail.remaining_sec !== null && lifeFail.remaining_sec !== undefined) {
                            lifefailRemainingEl.textContent = formatHuman(lifeFail.remaining_sec);
                        } else {
                            lifefailRemainingEl.textContent = '—';
                        }
                    }
                }
            });
        } catch (e) {
            // ignore polling errors
        }
    }

    refresh();
    setInterval(refresh, 3000);
}

function startLifeSupportBoard() {
    const board = document.querySelector('[data-life-support]');
    if (!board) return;

    const metrics = (() => {
        try {
            return JSON.parse(board.dataset.metrics || '[]');
        } catch (e) {
            return [];
        }
    })();

    if (!metrics.length) return;

    let lifeFail = board.dataset.lifefail === '1';
    const liveValues = {};

    function setStateBadge() {
        const badge = board.querySelector('[data-life-support-state]');
        if (!badge) return;
        badge.textContent = lifeFail ? 'Критичний режим' : 'Стабільно';
        board.dataset.lifefail = lifeFail ? '1' : '0';
    }

    function applyValues() {
        const warningMode = document.body.classList.contains('intensity-warning');
        metrics.forEach((metric) => {
            const id = metric.id;
            let target = lifeFail ? Number(metric.fail) : Number(metric.normal);
            if (warningMode && !lifeFail) {
                target += (Math.random() - 0.5) * 6;
            }
            const max = Number(metric.max) || target || 1;
            if (!liveValues[id]) {
                liveValues[id] = target;
            } else {
                const current = liveValues[id];
                liveValues[id] = current + (target - current) * 0.15;
            }

            const card = board.querySelector(`[data-metric-id="${id}"]`);
            if (!card) return;
            const valueEl = card.querySelector('[data-metric-value]');
            const barEl = card.querySelector('[data-metric-bar]');
            if (valueEl) {
                valueEl.textContent = Math.round(liveValues[id]);
            }
            if (barEl) {
                const percent = Math.max(0, Math.min(100, (liveValues[id] / max) * 100));
                barEl.style.width = `${percent}%`;
            }
        });
    }

    async function pollState() {
        try {
            const res = await fetch('/api/get-state.php?ts=' + Date.now());
            const payload = await res.json();
            const failNow = Boolean(payload.phases?.life_support?.active);
            if (failNow !== lifeFail) {
                lifeFail = failNow;
                setStateBadge();
            }
        } catch (e) {
            // ignore
        }
    }

    setStateBadge();
    applyValues();
    setInterval(applyValues, 350);
    setInterval(pollState, 1000);
}

function formatHuman(seconds) {
    const sec = Math.max(0, Math.floor(seconds || 0));
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    const parts = [];
    if (h > 0) parts.push(`${h}h`);
    parts.push(`${String(m).padStart(2, '0')}m`);
    parts.push(`${String(s).padStart(2, '0')}s`);
    return parts.join(' ');
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function startPlayerPopups() {
    const modal = document.querySelector('[data-player-popup-modal]');
    if (!modal) return;

    const bodyEl = modal.querySelector('[data-popup-body]');
    const closeEls = modal.querySelectorAll('[data-popup-close]');
    const seenKey = 'helix_seen_player_popups';
    const seenMessagesKey = 'helix_seen_player_popup_messages';
    let queue = [];
    let showing = null;
    let seen = [];
    let seenBodies = [];
    let autoHideTimer = null;
    let showingSince = 0;

    try {
        const saved = localStorage.getItem(seenKey);
        seen = saved ? JSON.parse(saved) : [];
    } catch (e) {
        seen = [];
    }

    try {
        const savedBodies = localStorage.getItem(seenMessagesKey);
        seenBodies = savedBodies ? JSON.parse(savedBodies) : [];
    } catch (e) {
        seenBodies = [];
    }

    function saveSeen() {
        try {
            localStorage.setItem(seenKey, JSON.stringify(seen.slice(-60)));
            localStorage.setItem(seenMessagesKey, JSON.stringify(seenBodies.slice(-60)));
        } catch (e) {
            // ignore storage errors
        }
    }

    function markSeen(id) {
        if (!id || seen.includes(id)) return;
        seen.push(id);
        saveSeen();
    }

    function markBodySeen(msg) {
        if (!msg) return;
        if (seenBodies.includes(msg)) return;
        seenBodies.push(msg);
        saveSeen();
    }

    function hideModal() {
        if (autoHideTimer) {
            clearTimeout(autoHideTimer);
            autoHideTimer = null;
        }
        modal.hidden = true;
        showing = null;
        showingSince = 0;
        showNext();
    }

    closeEls.forEach((el) => el.addEventListener('click', hideModal));

    function showNext() {
        if (showing || !queue.length) return;
        const next = queue.shift();
        showing = next.id || 'unknown';
        if (bodyEl) {
            bodyEl.textContent = next.message || '';
        }
        modal.hidden = false;
        markSeen(next.id);
        markBodySeen(next.message || '');
        showingSince = Date.now();
        if (autoHideTimer) clearTimeout(autoHideTimer);
        autoHideTimer = setTimeout(() => {
            hideModal();
        }, 10000);
    }

    async function poll() {
        try {
            const res = await fetch('/api/get-terminal-messages.php?target=player_popup&ts=' + Date.now());
            const payload = await res.json();
            const messages = (payload.messages || []).sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));
            const queuedBodies = new Set(queue.map((msg) => (msg.message || '').trim()).filter(Boolean));
            const queuedIds = new Set(queue.map((msg) => msg.id || '').filter(Boolean));
            messages.forEach((msg) => {
                const id = msg.id || '';
                const body = (msg.message || '').trim();
                if (!id || seen.includes(id)) return;
                if (body && seenBodies.includes(body)) return;
                if (id && queuedIds.has(id)) return;
                if (body && queuedBodies.has(body)) return;
                queue.push(msg);
            });
            showNext();
        } catch (e) {
            // ignore fetch errors
        }
    }

    poll();
    setInterval(() => {
        if (!showingSince || !showing) return;
        if (Date.now() - showingSince >= 11000) {
            hideModal();
        }
    }, 2000);
    setInterval(poll, 8000);
}

window.addEventListener('DOMContentLoaded', () => {
    startTerminalFeed('.terminal-feed');
    startRotators();
    startHintTicker();
    startLiveTimer();
    startLifeSupportBoard();
    setupProtocolPopups();
    setupAccessVotes();
    setupPlayerTerminalForm();
    setupGoalToggles();
    startPlayerPopups();
});
