function startTerminalFeed(selector, target = 'public_terminal') {
    const container = document.querySelector(selector);
    if (!container) return;

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

    function renderLine(line) {
        const ts = new Date(line.timestamp).toLocaleTimeString('uk-UA', { hour12: false });
        const typeClass = 'line-' + (line.type || 'info');
        return `<div class="terminal-line ${typeClass}">` +
            `<span class="muted">${ts}</span>` +
            `<span class="badge">${line.type}</span>` +
            `<span>${line.message}</span>` +
            `</div>`;
    }

    refresh();
    setInterval(refresh, 6000);
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

function setupProtocolPopups() {
    const modal = document.querySelector('.protocol-modal');
    if (!modal) return;
    const overlay = modal.querySelector('.protocol-modal__overlay');
    const title = modal.querySelector('[data-modal-title]');
    const meta = modal.querySelector('[data-modal-meta]');
    const body = modal.querySelector('[data-modal-body]');

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
        const markUrl = trigger.dataset.markUrl;

        title.textContent = `${label} (${id})`;
        meta.textContent = `Рівень ${level} · Фаза ${phase}${locked ? ' · лише перегляд' : ''}`;
        body.textContent = description;

        modal.querySelector('[data-modal-content]').textContent = content;
        modal.classList.add('open');

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
}

function startLiveTimer() {
    const containers = document.querySelectorAll('[data-live-timer]');
    if (!containers.length) return;

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
                if (triggersTable && payload.timer && Array.isArray(payload.timer.time_triggers)) {
                    triggersTable.innerHTML = payload.timer.time_triggers.map((trigger) => {
                        const left = Math.max(0, (trigger.at_seconds || 0) - (payload.timer.elapsed || 0));
                        return `<tr><td>${formatHuman(left)}</td><td>${trigger.quest_id || ''}</td></tr>`;
                    }).join('');
                }
            });
        } catch (e) {
            // ignore polling errors
        }
    }

    refresh();
    setInterval(refresh, 3000);
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

window.addEventListener('DOMContentLoaded', () => {
    startTerminalFeed('.terminal-feed');
    startRotators();
    startHintTicker();
    startLiveTimer();
    setupProtocolPopups();
});
