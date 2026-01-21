<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$enabled = isset($_POST['ross_single_promotion_enabled']);
$redirect = $_POST['redirect'] ?? '/admin/admin-phases.php';

$votes = load_json('access-votes.json');
$meta = $votes['_meta'] ?? [];
$settings = $meta['settings'] ?? [];

$settings['ross_single_promotion_enabled'] = $enabled;
$settings['ross_single_promotion_cooldown_sec'] = 7200;

$meta['settings'] = $settings;
$votes['_meta'] = $meta;

save_json('access-votes.json', $votes);

header('Location: ' . $redirect);
exit;
