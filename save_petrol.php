<?php
$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'];
$value = $data['value'];
$username = $data['username'] ?? 'nieznany';

$filename = 'petrol_data.json';
$petrolData = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];

$petrolData[$name] = [
    'value' => $value,
    'username' => $username
];

file_put_contents($filename, json_encode($petrolData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Zapisano";
