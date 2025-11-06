<?php
header('Content-Type: application/json');

$file = __DIR__ . '/loc_story.txt';
if (!file_exists($file)) {
    echo json_encode([]);
    exit;
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$lines = array_reverse($lines); // zaczynamy od najnowszych

$seen = [];
$unique = [];

foreach ($lines as $line) {
    $parts = explode(';', $line);
    if (count($parts) !== 4) continue;

    list($username, $marker, $value, $timestamp) = array_map('trim', $parts);
    $key = "$username;$marker;$value";

    if (isset($seen[$key])) continue;

    $seen[$key] = true;
    $unique[] = [
        'username' => $username,
        'marker' => $marker,
        'value' => $value,
        'timestamp' => $timestamp
    ];

    if (count($unique) >= 100) break;
}

echo json_encode($unique);
