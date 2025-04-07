<?php
$from = $_POST['from'] ?? '';
$to = $_POST['to'] ?? '';
$message = $_POST['message'] ?? '';

if (!$from || !$to || !$message) {
  http_response_code(400);
  echo "Missing fields";
  exit;
}

// Ensure the folder exists
$dir = "dms";
if (!is_dir($dir)) {
  mkdir($dir, 0777, true);
}

// Sort usernames alphabetically to ensure consistent filename
$users = [$from, $to];
sort($users, SORT_STRING);
$filename = $dir . "/" . $users[0] . "--" . $users[1] . ".json";

// Load existing DMs or create new array
$dms = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];
if (!is_array($dms)) $dms = [];

// Add new message
$dms[] = [
  "from" => $from,
  "to" => $to,
  "text" => $message,
  "timestamp" => time(),
  "unread" => true
];

// Save to file
if (file_put_contents($filename, json_encode($dms, JSON_PRETTY_PRINT)) === false) {
  http_response_code(500);
  echo "Failed to write file";
  exit;
}

echo "OK";
?>
