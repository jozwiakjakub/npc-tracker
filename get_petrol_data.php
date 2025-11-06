<?php
header('Content-Type: application/json');
$dataFile = __DIR__ . '/petrol_data.json';
echo file_exists($dataFile) ? file_get_contents($dataFile) : '{}';
