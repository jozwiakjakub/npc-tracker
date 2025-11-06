<?php
session_start();

$username = $_SESSION['user'] ?? null;
if (!$username) {
    echo 'expired';
    exit;
}

$sessionsFile = __DIR__ . '/secure/sessions.json';
if (!file_exists($sessionsFile)) {
    echo 'expired';
    exit;
}

$sessions = json_decode(file_get_contents($sessionsFile), true);

// Jeśli brak wpisu użytkownika — sesja wygasła
if (!isset($sessions[$username])) {
    echo 'expired';
    exit;
}

// Sprawdź czy minęło więcej niż 35 minut (2100 sekund)
$lastActive = $sessions[$username]['last_active'] ?? 0;
if (time() - $lastActive > 2100) {
    unset($sessions[$username]);
    file_put_contents($sessionsFile, json_encode($sessions));
    echo 'expired';
    exit;
}

// Aktualizacja znacznika aktywności
$sessions[$username]['last_active'] = time();
file_put_contents($sessionsFile, json_encode($sessions));

echo 'ok';
