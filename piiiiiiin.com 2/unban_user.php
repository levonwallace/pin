<?php
$user = $_POST["user"] ?? '';

// Load the current banned list
$banned = file_exists("banned.json") ? json_decode(file_get_contents("banned.json"), true) : ["users" => [], "ips" => [], "cookies" => []];

// Remove user from "users"
$banned["users"] = array_filter($banned["users"], fn($u) => $u !== $user);

// Also try to remove their IP from "ips" (look it up in positions.json)
$positions = file_exists("positions.json") ? json_decode(file_get_contents("positions.json"), true) : [];
$ipToRemove = '';

// Look for this user’s IP (optional but helpful)
if (isset($positions[$user]["ip"])) {
    $ipToRemove = $positions[$user]["ip"];
} elseif (isset($positions[$user])) {
    // fallback for flat structure
    $ipToRemove = $positions[$user];
}

// Remove matching IP
$banned["ips"] = array_filter($banned["ips"], fn($ip) => $ip !== $ipToRemove);

// Optionally remove from "cookies" too (if you ever track per-user cookie IDs)
if (isset($_COOKIE["ban_cookie_id"])) {
    $cookieId = $_COOKIE["ban_cookie_id"];
    $banned["cookies"] = array_filter($banned["cookies"], fn($id) => $id !== $cookieId);
}

// Save it back
file_put_contents("banned.json", json_encode($banned));
?>
