<?php
if (!isset($_POST['user'])) {
    http_response_code(400);
    echo "Missing user";
    exit;
}

$user = htmlspecialchars($_POST['user']);

// Files to clean up
$files = [
    "positions.json",
    "contexts.json",
    "last_seen.json",
    "pins.json",
    "chat.json"
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);

        // Delete user-specific content
        if ($file === "pins.json") {
            $data = array_filter($data, fn($pin) => $pin['user'] !== $user);
        } elseif ($file === "chat.json") {
            $data = array_filter($data, fn($msg) => $msg['user'] !== $user);
        } else {
            unset($data[$user]);
        }

        file_put_contents($file, json_encode($data));
    }
}

echo "User deleted.";
?>
