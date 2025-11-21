<?php
require_once __DIR__ . '/../includes/helpers.php';

$timer = timer_status();
$phases = current_phase();
$players = load_json('players.json');
$protocols = load_json('protocols.json');

respond_json([
    'timer' => $timer,
    'phases' => $phases,
    'players' => $players,
    'protocols' => $protocols,
]);
