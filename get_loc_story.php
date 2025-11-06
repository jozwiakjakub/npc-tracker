<?php
header('Content-Type: application/json');

$file = __DIR__ . '/loc_story.txt';
if (!file_exists($file)) {
    echo json_encode([]);
    exit;
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Używamy czasu lokalnego (Polska)
$timezone = new DateTimeZone('Europe/Warsaw');
$now = new DateTime('now', $timezone);

$entries = [];

// Parsowanie i filtrowanie do dokładnie ostatniej godziny
foreach ($lines as $line) {
    $parts = explode(';', $line);
    if (count($parts) !== 4) continue;

    list($username, $marker, $value, $timestamp) = array_map('trim', $parts);
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $timestamp, $timezone);
    if (!$dt) continue;

    // Odrzuć, jeśli minęło 3600 sekund lub więcej
    if (($now->getTimestamp() - $dt->getTimestamp()) >= 3600) continue;

    $entries[] = [
        'username' => $username,
        'marker' => $marker,
        'value' => $value,
        'timestamp' => $timestamp,
        'dt' => $dt
    ];
}

// Znajdź najnowszy wpis (dowolnego usera) dla każdego markera
$latestByMarker = [];
foreach ($entries as $entry) {
    $marker = $entry['marker'];
    if (!isset($latestByMarker[$marker]) || $entry['dt'] > $latestByMarker[$marker]['dt']) {
        $latestByMarker[$marker] = $entry;
    }
}

// Przygotuj wynik — bez filtrowania wartości "nie ma"
$results = [];
foreach ($latestByMarker as $entry) {
    unset($entry['dt']);
    $results[] = $entry;
}

echo json_encode($results);
