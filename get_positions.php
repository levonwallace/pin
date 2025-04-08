<?php
// Include the configuration file
require_once 'config.php';

$filename = 'positions.json';

// Read positions from S3
$json = s3ReadFile('pins', $filename);
if ($json === false) {
    $json = '{}';
}

// Return the positions as JSON
header('Content-Type: application/json');
echo $json;
exit;
?>
