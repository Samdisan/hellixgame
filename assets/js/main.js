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

window.addEventListener('DOMContentLoaded', () => {
    startTerminalFeed('.terminal-feed');
    startRotators();
});
