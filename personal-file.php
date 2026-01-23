<?php
require_once __DIR__ . '/includes/helpers.php';
require_role('player');

$players = load_json('players.json');
$playerId = $_GET['id'] ?? '';
$player = null;
foreach ($players as $person) {
    if (($person['id'] ?? '') === $playerId) {
        $player = $person;
        break;
    }
}

include __DIR__ . '/partials/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h1>Особова справа</h1>
        <a class="button button-ghost" href="/personal-files.php">← Назад до списку</a>
    </div>

    <?php if (!$player): ?>
        <p class="muted">Досьє не знайдено.</p>
    <?php elseif ($player['id'] !== 'PL_STATION_GREN'): ?>
        <p class="muted">Детальна справа недоступна для цього співробітника.</p>
    <?php else: ?>
        <div class="stack">
            <h2>1. ОСОБИСТІ ДАНІ</h2>
            <p><strong>Ім'я:</strong> Саймон Гренн (Simon Grenn)</p>
            <p><strong>ID співробітника:</strong> PL_GRENN</p>
            <p><strong>Посада:</strong> Головний вірусолог, Керівник відділу стабільних штамів (Сектор C1)</p>
            <p><strong>Вік:</strong> 46 років</p>
            <p><strong>Група крові:</strong> AB(IV) Rh- (Рідкісна)</p>
            <p><strong>Локація:</strong> Арктична біолабораторія, Рівень Біозахисту 4.</p>

            <h2>2. ПСИХОЛОГІЧНИЙ ПРОФІЛЬ (Оцінка HR)</h2>
            <p><strong>Тип особистості:</strong> INTJ (Стратег).</p>
            <p><strong>Характеристика:</strong> Високий рівень інтелекту, схильність до мікроменеджменту. Емоційно відсторонений, соціопатичні риси проявляються у стресових ситуаціях. Ставить науковий результат вище етичних норм.</p>
            <p><strong>Слабкі місця:</strong> Гіперфіксація на роботі. Прихована емоційна залежність від схвалення колег, яких він поважає (зокрема, доктор Кроу).</p>
            <p><strong>Маркери поведінки:</strong> Часто перевіряє час, тремор рук (приховує), уникає прямого погляду при розмові про терміни здачі проектів.</p>

            <h2>3. ПРОФЕСІЙНА ІСТОРІЯ</h2>
            <p><strong>2015–2020:</strong> Всесвітня організація охорони здоров'я (ВООЗ). Голова групи швидкого реагування на епідемії. Звільнений через скандал із "негуманними методами тестування вакцин" у Конго (офіційно зам'ято).</p>
            <p><strong>2020–2023:</strong> Приватна практика. Консультант DARPA.</p>
            <p><strong>2023–дотепер:</strong> ILARIA Corp. Швидке просування по службі завдяки прориву в стабілізації ретровірусів.</p>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
