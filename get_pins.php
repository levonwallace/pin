<?php
// Include the configuration file
require_once 'config.php';

$filename = 'pins.json';

// Read pins from S3
$json = s3ReadFile('pins', $filename);
if ($json === false) {
    $json = '{}';
}

// Return the pins as JSON
header('Content-Type: application/json');
echo $json;
exit;
?>
