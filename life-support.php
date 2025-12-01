<?php
require_once __DIR__ . '/includes/helpers.php';

$metrics = load_json('life-support.json');
$phaseData = current_phase();
$activeIds = array_map(fn($p) => $p['id'] ?? '', $phaseData['active'] ?? []);
$isLifeFail = in_array('PH_LIFEFAIL', $activeIds, true);

include __DIR__ . '/partials/header.php';
?>

<section class="hero-panel hero-panel--compact">
    <div>
        <p class="eyebrow">Життєві показники станції</p>
        <h1>Контроль середовища HELIX</h1>
        <p class="lede">Кисень, напруга та стабільність тиску в реальному часі. У разі збою система переходить у режим червоних попереджень і показники падають до критичних значень.</p>
    </div>
    <div class="hero-callout">
        <div class="micro muted">Режим</div>
        <div class="hero-mode <?php echo $isLifeFail ? 'alert' : 'ok'; ?>">
            <?php echo $isLifeFail ? 'PH_LIFEFAIL — критичний спад' : 'Норма — системи стабільні'; ?>
        </div>
        <div class="micro muted">Джерело даних: фазовий таймер + сенсори HELIX</div>
    </div>
</section>

<section class="panel life-support" data-life-support data-metrics="<?php echo htmlspecialchars(json_encode($metrics, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>" data-lifefail="<?php echo $isLifeFail ? '1' : '0'; ?>">
    <div class="life-support__header">
        <div>
            <p class="micro muted">Відстеження у живому часі</p>
            <h2>Системи життєзабезпечення</h2>
        </div>
        <div class="life-support__badge" data-life-support-state>
            <?php echo $isLifeFail ? 'Критичний режим' : 'Стабільно'; ?>
        </div>
    </div>
    <div class="life-support__grid">
        <?php foreach ($metrics as $metric): ?>
            <article class="life-card" data-metric-id="<?php echo htmlspecialchars($metric['id'], ENT_QUOTES); ?>" data-normal="<?php echo htmlspecialchars($metric['normal'], ENT_QUOTES); ?>" data-fail="<?php echo htmlspecialchars($metric['fail'], ENT_QUOTES); ?>" data-max="<?php echo htmlspecialchars($metric['max'], ENT_QUOTES); ?>">
                <div class="life-card__head">
                    <div>
                        <p class="micro muted">Сенсор</p>
                        <h3><?php echo htmlspecialchars($metric['label'], ENT_QUOTES); ?></h3>
                        <p class="micro muted"><?php echo htmlspecialchars($metric['description'] ?? '', ENT_QUOTES); ?></p>
                    </div>
                    <div class="life-card__value" data-metric-value><?php echo htmlspecialchars($metric['normal'], ENT_QUOTES); ?></div>
                    <div class="life-card__unit"><?php echo htmlspecialchars($metric['unit'], ENT_QUOTES); ?></div>
                </div>
                <div class="life-card__bar">
                    <div class="life-card__fill" data-metric-bar></div>
                    <div class="life-card__markers">
                        <span>Норма</span>
                        <span>Критично</span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="life-support__legend">
        <div class="legend-pill legend-pill--ok">Норма</div>
        <div class="legend-pill legend-pill--drop">Критичний спад</div>
        <div class="legend-pill legend-pill--pulse">Фаза PH_LIFEFAIL</div>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
