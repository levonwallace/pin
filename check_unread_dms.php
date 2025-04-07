<?php
header("Content-Type: application/json");

$me = $_GET["user"] ?? '';
if (!$me) {
  echo json_encode([]);
  exit;
}

$unreadFrom = [];

foreach (glob("dms/*.json") as $file) {
  $messages = json_decode(file_get_contents($file), true);
  foreach ($messages as $msg) {
    if ($msg["to"] === $me && !empty($msg["unread"])) {
      $unreadFrom[] = $msg["from"];
    }
  }
}

echo json_encode(array_unique($unreadFrom));
