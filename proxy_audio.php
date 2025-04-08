<?php
// Set appropriate headers for audio streaming
header('Content-Type: audio/mpeg');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the URL from the query parameter
$url = isset($_GET['url']) ? $_GET['url'] : '';

// Validate URL
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid URL provided';
    exit;
}

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

// Execute cURL session
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Error fetching audio: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

// Get content type from response
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
if (!empty($contentType)) {
    header('Content-Type: ' . $contentType);
}

// Close cURL session
curl_close($ch);

// Output the audio data
echo $response;
?> 