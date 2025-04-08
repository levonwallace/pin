<?php
// Include the configuration file
require_once 'config.php';

if (isset($_POST['user']) && isset($_POST['x']) && isset($_POST['y'])) {
    $user = htmlspecialchars($_POST['user']);
    $x = intval($_POST['x']);
    $y = intval($_POST['y']);

    // --- Handle position ---
    $posFilename = 'positions.json';
    
    // Read positions from S3
    $json = s3ReadFile('pins', $posFilename);
    if ($json === false) {
        $positions = [];
    } else {
        $positions = json_decode($json, true);
        if ($positions === null) {
            $positions = [];
        }
    }
    
    $positions[$user] = ['x' => $x, 'y' => $y];
    
    // Write positions to S3
    s3WriteFile('pins', $posFilename, json_encode($positions));

    // --- Handle last seen ---
    $seenFilename = 'last_seen.json';
    
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
    
    $lastSeen[$user] = time(); // store timestamp
    
    // Write last seen to S3
    s3WriteFile('pins', $seenFilename, json_encode($lastSeen));
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Return error response
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
exit;
?>
