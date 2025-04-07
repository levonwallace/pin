<?php
$user = $_POST["user"] ?? '';
$ip = $_SERVER["REMOTE_ADDR"];
$cookieId = $_COOKIE["ban_cookie_id"] ?? '';

// Load current ban list
$banned = file_exists("banned.json") ? json_decode(file_get_contents("banned.json"), true) : [];
if (!is_array($banned)) $banned = ["users" => [], "ips" => [], "cookies" => []];

// Add to ban list
if ($user) $banned["users"][] = $user;
if ($ip) $banned["ips"][] = $ip;
if ($cookieId) $banned["cookies"][] = $cookieId;

// Remove duplicates
$banned["users"] = array_unique($banned["users"]);
$banned["ips"] = array_unique($banned["ips"]);
$banned["cookies"] = array_unique($banned["cookies"]);

file_put_contents("banned.json", json_encode($banned));
?>
