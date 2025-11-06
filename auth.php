<?php
session_start();

$username = $_SESSION['user'] ?? null;
if (!$username) {
    $_SESSION['auth_error'] = 'Brak aktywnej sesji.';
    header('Location: logout.php');
    exit;
}

$sessionsFile = __DIR__ . '/secure/sessions.json';

if (!file_exists($sessionsFile)) {
    $_SESSION['auth_error'] = 'Błąd systemu — brak pliku sesji.';
    header('Location: logout.php');
    exit;
}

$sessions = json_decode(file_get_contents($sessionsFile), true);

if (!isset($sessions[$username])) {
    // Brak sesji - wymuś logout
    $_SESSION['auth_error'] = 'Twoja sesja wygasła lub została usunięta.';
    header('Location: logout.php');
    exit;
}

// ⚠️ NIE sprawdzamy time() - last_active tutaj!

// OK — sesja istnieje, pozwalamy na dostęp
?>
