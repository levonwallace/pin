<?php
$banned = file_exists("banned.json") ? json_decode(file_get_contents("banned.json"), true) : ["users" => [], "ips" => [], "cookies" => []];
if (!is_array($banned)) $banned = ["users" => [], "ips" => [], "cookies" => []];

$user = $_POST["user"] ?? $_GET["user"] ?? ($_COOKIE["chat_user"] ?? "");
$ip = $_SERVER["REMOTE_ADDR"];
$cookieId = $_COOKIE["ban_cookie_id"] ?? '';

if (in_array($user, $banned["users"]) || in_array($ip, $banned["ips"]) || in_array($cookieId, $banned["cookies"])) {
  header("Location: 404.html");
  exit;
}
?>
