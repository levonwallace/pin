<?php
// Include the configuration file
require_once 'config.php';

if (isset($_POST['user']) && isset($_POST['title']) && isset($_POST['x']) && isset($_POST['y'])) {
    $user = htmlspecialchars($_POST['user']);
    $title = htmlspecialchars($_POST['title']);
    $x = intval($_POST['x']);
    $y = intval($_POST['y']);
    $type = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : 'text';
    
    // Additional data for specific pin types
    $additionalData = [];
    if ($type === 'audio' && isset($_POST['audioUrl'])) {
        $additionalData['audioUrl'] = htmlspecialchars($_POST['audioUrl']);
    }

    $filename = 'pins.json';
    
    // Read pins from S3
    $json = s3ReadFile('pins', $filename);
    if ($json === false) {
        $pins = [];
    } else {
        $pins = json_decode($json, true);
        if ($pins === null) {
            $pins = [];
        }
    }

    $id = uniqid();
    $pins[$id] = [
        'id' => $id,
        'title' => $title,
        'user' => $user,
        'x' => $x,
        'y' => $y,
        'type' => $type,
        'created' => time()
    ];
    
    // Add additional data for specific pin types
    if (!empty($additionalData)) {
        $pins[$id] = array_merge($pins[$id], $additionalData);
    }

    // Write pins to S3
    s3WriteFile('pins', $filename, json_encode($pins));
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

// Return error response
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
exit;
?>
