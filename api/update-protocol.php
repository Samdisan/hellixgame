<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role_api('admin');

$data = $_POST;
$id = $data['id'] ?? '';
if ($id === '') {
    respond_json(['error' => 'missing_id'], 400);
    exit;
}

$protocols = load_json('protocols.json');
$found = false;
foreach ($protocols as &$protocol) {
    if ($protocol['id'] === $id) {
        $protocol = array_merge($protocol, $data);
        $found = true;
        break;
    }
}
if (!$found) {
    $protocols[] = $data;
}

save_json('protocols.json', $protocols);
append_terminal_message('admin_terminal', 'protocol', 'Protocol updated: ' . $id);
respond_json(['status' => 'saved']);
