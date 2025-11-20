<?php require_once __DIR__ . '/../includes/helpers.php'; ?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HELIX ECHELON — Command Interface</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <script defer src="/assets/js/main.js"></script>
</head>
<body class="helix-shell">
<header class="top-bar">
    <div class="logo">HELIX ECHELON</div>
    <nav>
        <a href="/index.php">Головна</a>
        <a href="/expeditions.php">Експедиції</a>
        <a href="/protocols.php">Протоколи</a>
        <a href="/personal-files.php">Особисті справи</a>
        <a href="/terminal.php">Термінал</a>
        <a href="/#access">Вхід</a>
    </nav>
</header>
<?php if (!empty($_SESSION['access_type']) && $_SESSION['access_type'] === 'admin'): ?>
    <nav class="admin-nav">
        <a href="/admin/admin.php">Адмін-хаб</a>
        <a href="/admin/admin-timer.php">Глобальний таймер</a>
        <a href="/admin/admin-phases.php">Фази & квести</a>
        <a href="/admin/admin-terminal.php">Адмін-термінал</a>
        <a href="/admin/admin-protocols.php">Протоколи</a>
        <a href="/admin/admin-players.php">Гравці</a>
        <a href="/admin/admin-diagnostics.php">Діагностика</a>
    </nav>
<?php endif; ?>
<?php echo render_glitch_hint(); ?>
<main class="page">
