<?php include __DIR__ . '/partials/header.php'; ?>
<section class="panel">
    <div class="glitch-overlay"></div>
    <h1>HELIX ECHELON — внутрішня система станції</h1>
    <p>Станція оживає, хоча не чекала гостей. Ви бачите логотип HELIX, дату <strong>24 січня 2026</strong> і локацію, яку система старанно приховує.</p>
    <div class="banner"><span class="scan-pulse"></span><span data-rotator="BIOLOGICAL SYSTEM… receiving external signals…|SERVER ROOM: unstable|ACCESS LEVEL: insufficient|PROXIMITY ALERT: unknown beacon detected|MEMORY FRAGMENTS: drifting"></span></div>
    <div class="quick-actions">
        <a class="button" href="/expeditions.php">Експедиції</a>
        <a class="button" href="/protocols.php">Протоколи</a>
        <a class="button secondary" href="#access">Вхід у систему</a>
    </div>
    <div class="overlay-text">intro intake</div>
</section>

<section class="grid cols-2">
    <div class="panel">
        <h2>Головна сцена входу</h2>
        <p>Екран ніби справний, але дрібні глічі постійно нагадують: станція пам’ятає попередні вторгнення. Ви відкрили внутрішню консоль, що працює в автономному режимі.</p>
        <ul>
            <li>Публічний доступ до протоколів та реєстру експедицій.</li>
            <li>Код доступу відкриває персональну консоль або місток майстра.</li>
        </ul>
    </div>
    <div class="panel" id="access">
        <h2>Термінал</h2>
        <div class="terminal">
            <div class="terminal-line line-info"><span class="muted">[SYS]</span><span class="badge">INFO</span><span>З'єднання встановлено. Внутрішні датчики приходять до тями.</span></div>
            <div class="terminal-line line-warning"><span class="muted">[SYS]</span><span class="badge">WARNING</span><span>Гліч-пам’ять: фрагменти зберігаються у нестабільних секторах.</span></div>
            <div class="terminal-line line-protocol"><span class="muted">[SYS]</span><span class="badge">PROTOCOL</span><span>Режим доступу: лише коди, без логінів. Команда очікує.</span></div>
        </div>
        <div class="protocol-card" style="margin-top:16px;">
            <h3>Вхід за кодом</h3>
            <p class="muted">Станція сканує зовнішній сигнал. Введіть один параметр — система визначить, чи ви гравець, чи майстер.</p>
            <?php $error = isset($_GET['error']) ? 'Невірний код доступу.' : null; ?>
            <?php if ($error): ?><div class="protocol-card" style="border-color: rgba(255,107,107,0.4); color: var(--critical);">⚠️ <?php echo htmlspecialchars($error, ENT_QUOTES); ?></div><?php endif; ?>
            <form method="post" action="/api/login.php" class="stacked" style="margin-top:12px;">
                <input class="form-control" type="text" name="code" placeholder="Код доступу" required>
                <div class="quick-actions" style="margin-top:10px;">
                    <button class="button" type="submit">Підтвердити</button>
                    <span class="badge level flicker">SCANNING PIPELINE</span>
                </div>
            </form>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
