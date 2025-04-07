<?php
$contextsFile = 'contexts.json';
$seenFile = 'last_seen.json';

$contexts = file_exists($contextsFile) ? json_decode(file_get_contents($contextsFile), true) : [];
$lastSeen = file_exists($seenFile) ? json_decode(file_get_contents($seenFile), true) : [];

// Combine into a single response
$response = [];

foreach ($contexts as $user => $context) {
    $response[$user] = [
        'room' => $context, 
        'last_seen' => isset($lastSeen[$user]) ? $lastSeen[$user] : null
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>

