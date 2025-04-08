<?php
// Include the configuration file
require_once 'config.php';

if (isset($_POST['user']) && isset($_POST['pin'])) {
    $user = htmlspecialchars($_POST['user']);
    $pin = htmlspecialchars($_POST['pin']);

    $filename = 'contexts.json';
    
    // Read contexts from S3
    $json = s3ReadFile('pins', $filename);
    if ($json === false) {
        $contexts = [];
    } else {
        $contexts = json_decode($json, true);
        if ($contexts === null) {
            $contexts = [];
        }
    }

    $contexts[$user] = $pin;
    
    // Write contexts to S3
    s3WriteFile('pins', $filename, json_encode($contexts));
    
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
