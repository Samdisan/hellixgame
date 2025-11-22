function startTerminalFeed(selector, fallbackTarget = 'public_terminal') {
    const container = document.querySelector(selector);
    if (!container) return;

    if (container.dataset.feedStarted) return;
    container.dataset.feedStarted = '1';

    const target = container.dataset.target || fallbackTarget;
    const canDelete = container.dataset.canDelete === '1';
    const pollMs = parseInt(container.dataset.pollMs || '6000', 10);

    async function refresh() {
        try {
            const res = await fetch('/api/get-terminal-messages.php?target=' + encodeURIComponent(target) + '&ts=' + Date.now());
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
                status.textContent = 'Помилка: ' + (payload.message || payload.error || res.statusText);
                btn.disabled = false;
                return;
            }

            row.querySelector('[data-approvals-count]').textContent = payload.approvals;
            row.dataset.approvals = payload.approvals;
            row.dataset.level = payload.new_level;
            row.querySelector('.badge.level').textContent = payload.new_level;

            const stamp = document.createElement('span');
            stamp.className = 'muted micro';

            if (payload.leveled_up) {
                status.textContent = `Рівень оновлено до ${payload.new_level}. Голоси очищено.`;
                stamp.textContent = 'Підвищено';
            } else {
                status.textContent = `Ваш голос зафіксовано. ${payload.approvals}/3 підтверджень.`;
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
                const form = new FormData();
                form.append('protocol', id);
                form.append('mode', 'json');
                await fetch(markUrl, { method: 'POST', body: form });
                trigger.closest('.protocol-card')?.classList.remove('new');
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
    if (!containers.length) return;

    if (window.__helixTimerLoop) return;
    window.__helixTimerLoop = true;

    async function refresh() {
        try {
            const res = await fetch('/api/get-state.php?ts=' + Date.now());
            const payload = await res.json();
            containers.forEach((container) => {
                const elapsedEl = container.querySelector('[data-timer-elapsed]');
                const remainingEl = container.querySelector('[data-timer-remaining]');
                const statusEl = container.querySelector('[data-timer-status]');
                const phaseElapsedEl = container.querySelector('[data-phase-elapsed]');
                const phaseRemainingEl = container.querySelector('[data-phase-remaining]');
                const phaseNextEl = container.querySelector('[data-phase-next]');
                const activePhasesEl = container.querySelector('[data-active-phases]');
                const triggersTable = container.querySelector('[data-timer-triggers] tbody');

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
            });
        } catch (e) {
            // ignore polling errors
        }
    }

    refresh();
    setInterval(refresh, 1000);
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

window.addEventListener('DOMContentLoaded', () => {
    startTerminalFeed('.terminal-feed');
    startRotators();
    startHintTicker();
    startLiveTimer();
    setupProtocolPopups();
    setupAccessVotes();
    setupPlayerTerminalForm();
});
