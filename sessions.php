<?php
header('Content-Type: application/json');

$limit_seconds = 2100; // 35 minut

$sessionsFile = __DIR__ . '/secure/sessions.json';
if (!file_exists($sessionsFile)) {
    echo json_encode([]);
    exit;
}

$sessions = json_decode(file_get_contents($sessionsFile), true);
$now = time();
$active = [];

foreach ($sessions as $user => $data) {
    $last_active = $data['last_active'] ?? 0;
    $inactive_time = $now - $last_active;

    if ($inactive_time <= $limit_seconds) {
        $active[] = [
            'username' => $user,
            'sid' => $data['sid'],
            'inactive_time' => $inactive_time
        ];
    } else {
        // Usuwamy użytkownika po 35 min
        unset($sessions[$user]);
    }
}

file_put_contents($sessionsFile, json_encode($sessions));
echo json_encode($active);