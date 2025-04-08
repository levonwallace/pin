<?php
// Include the configuration file
require_once 'config.php';

$contextsFilename = 'contexts.json';
$seenFilename = 'last_seen.json';

// Read contexts from S3
$json = s3ReadFile('pins', $contextsFilename);
if ($json === false) {
    $contexts = [];
} else {
    $contexts = json_decode($json, true);
    if ($contexts === null) {
        $contexts = [];
    }
}

// Read last seen from S3
$json = s3ReadFile('pins', $seenFilename);
if ($json === false) {
    $lastSeen = [];
} else {
    $lastSeen = json_decode($json, true);
    if ($lastSeen === null) {
        $lastSeen = [];
    }
}

// Combine into a single response
$response = [];

foreach ($contexts as $user => $context) {
    $response[$user] = [
        'room' => $context, 
        'last_seen' => isset($lastSeen[$user]) ? $lastSeen[$user] : null
    ];
}

// Return the contexts as JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>

