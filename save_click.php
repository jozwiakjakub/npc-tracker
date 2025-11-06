<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo "Błędne dane JSON";
    exit;
}

$username = isset($data['username']) ? trim($data['username']) : 'guest';
$marker = isset($data['marker']) ? trim($data['marker']) : 'unknown';
$value = isset($data['value']) ? trim($data['value']) : '';
$timestamp = isset($data['timestamp']) ? trim($data['timestamp']) : '';

if (!$timestamp) {
    // jeśli brak, ustaw teraz +2h
    $timestamp = (new DateTime('now', new DateTimeZone('UTC')))
                 ->add(new DateInterval('PT2H'))
                 ->format('Y-m-d H:i:s');
} else {
    // Zamień ISO na czytelny format z +2h
    try {
        $dt = new DateTime($timestamp);
        $dt->add(new DateInterval('PT2H'));
        $timestamp = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        $timestamp = (new DateTime('now', new DateTimeZone('UTC')))
                     ->add(new DateInterval('PT2H'))
                     ->format('Y-m-d H:i:s');
    }
}

// Linia do zapisu
$line = "{$username};{$marker};{$value};{$timestamp}\n";

// Ścieżka do pliku - względna lub absolutna
$file = __DIR__ . '/loc_story.txt';

if (file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    echo "Błąd zapisu do pliku";
    exit;
}

echo "Zapisano poprawnie";
