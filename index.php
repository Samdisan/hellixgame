<?php include __DIR__ . '/partials/header.php'; ?>
<section class="panel landing-hero" id="access">
    <div class="glitch-overlay"></div>
    <div>
        <h1>HELIX ECHELON — внутрішня система станції</h1>
        <p>Після серії аномальних спалахів невідомих вірусних форм корпорація ILARIA створила автономну мережу ізоляційних станцій під назвою HELIX. Спершу це виглядало як проєкт контролю біозагроз, але з часом місія змінилася — вчені, військові та політичні радники почали діяти неузгоджено. Дані з однієї з баз зникли, а записи свідчать про порушення протоколів і «поведінкові мутації» серед персоналу. Наразі світ розділений на три фракції: Корпорація ILARIA, Експедиція ВООЗ та Внутрішні Станційні Групи. Кожна має свою правду — і власний код виживання.</p>
        <div class="banner"><span class="scan-pulse"></span><span data-rotator="BIOLOGICAL SYSTEM… receiving external signals…|SERVER ROOM: unstable|ACCESS LEVEL: insufficient|PROXIMITY ALERT: unknown beacon detected|MEMORY FRAGMENTS: drifting"></span></div>
        <div class="quick-actions">
            <a class="button" href="/expeditions.php">Експедиції</a>
            <a class="button" href="/protocols.php">Протоколи</a>
            <a class="button secondary" href="/terminal.php">Розгорнути термінал</a>
        </div>
        <div class="overlay-text">intro intake</div>
    </div>
    <div class="terminal-card">
        <h2>Термінал</h2>
        <div class="terminal terminal-feed" aria-live="polite"></div>
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
