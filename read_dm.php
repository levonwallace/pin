<?php
header("Content-Type: application/json");

$from = $_GET["from"] ?? '';
$to = $_GET["to"] ?? '';

if (!$from || !$to) {
  echo json_encode([]);
  exit;
}

// Sort usernames alphabetically to ensure consistent filename
$users = [$from, $to];
sort($users, SORT_STRING);
$filename = "dms/" . $users[0] . "--" . $users[1] . ".json";

// Read messages
if (!file_exists($filename)) {
  echo json_encode([]);
  exit;
}

// Read and decode the messages
$content = file_get_contents($filename);
if ($content === false) {
  echo json_encode([]);
  exit;
}

$dms = json_decode($content, true);
if (!is_array($dms)) {
  echo json_encode([]);
  exit;
}

// Mark messages as read if they were sent TO the current user
$updated = false;
foreach ($dms as &$msg) {
  if ($msg["to"] === $from && !empty($msg["unread"])) {
    $msg["unread"] = false;
    $updated = true;
  }
}

// Save the updated message list if we marked any as read
if ($updated) {
  file_put_contents($filename, json_encode($dms, JSON_PRETTY_PRINT));
}

echo json_encode($dms);
?>
