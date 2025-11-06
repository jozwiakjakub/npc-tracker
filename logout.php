<?php
session_start();

$username = $_SESSION['user'] ?? null;

if ($username) {
    $sessionsFile = __DIR__ . '/secure/sessions.json';
    if (file_exists($sessionsFile)) {
        $sessions = json_decode(file_get_contents($sessionsFile), true);
        unset($sessions[$username]);
        file_put_contents($sessionsFile, json_encode($sessions));
    }
}

// Usuń sesję PHP na wszelki wypadek
session_unset();
session_destroy();

header('Location: login.php');
exit;
