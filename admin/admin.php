<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

// Централізований вхід переносимо на комбіновану сторінку фаз/квестів.
header('Location: /admin/admin-phases.php');
exit;
