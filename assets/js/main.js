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

window.addEventListener('DOMContentLoaded', () => {
    startTerminalFeed('.terminal-feed');
    startRotators();
    startHintTicker();
    startLiveTimer();
});
