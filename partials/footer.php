</main>
<div class="protocol-modal">
    <div class="protocol-modal__overlay"></div>
    <div class="protocol-modal__dialog">
        <button class="protocol-modal__close" aria-label="Закрити">×</button>
        <h3 data-modal-title></h3>
        <div class="micro muted" data-modal-meta></div>
        <p data-modal-body class="muted"></p>
        <div class="protocol-modal__content" data-modal-content></div>
        <div class="protocol-modal__actions" data-share-actions hidden>
            <div class="muted micro" data-share-hint>Цей документ можна розіслати команді у пошкодженому вигляді.</div>
            <div class="button-row">
                <button class="button warning" type="button" data-share-send>Розіслати команді</button>
                <button class="button secondary" type="button" data-share-cancel>Не розсилати</button>
            </div>
            <div class="micro" data-share-status></div>
        </div>
    </div>
</div>
<div class="player-popup" data-player-popup-modal hidden>
    <div class="player-popup__overlay" data-popup-close></div>
    <div class="player-popup__dialog">
        <div class="player-popup__header">
            <div class="badge warning">Нагадування</div>
            <button class="player-popup__close" type="button" aria-label="Закрити" data-popup-close>×</button>
        </div>
        <div class="player-popup__body" data-popup-body></div>
        <div class="micro muted">Сповіщення зберігаються у терміналі. Закрийте, коли прочитано.</div>
    </div>
</div>
<footer class="footer">
    <div>Станція HELIX — автономний режим спостереження.</div>
</footer>
</body>
</html>
