<?php
session_start();

$username = $_SESSION['user'] ?? null;
if (!$username) exit;

$sessionsFile = __DIR__ . '/secure/sessions.json';
if (!file_exists($sessionsFile)) exit;

$sessions = json_decode(file_get_contents($sessionsFile), true);

if (isset($sessions[$username])) {
    unset($sessions[$username]);
    file_put_contents($sessionsFile, json_encode($sessions));
}
?>
