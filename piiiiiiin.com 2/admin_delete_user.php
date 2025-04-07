<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["user"])) {
    $username = $_POST["user"];

    // Sanitize username but preserve original for exact matches
    $usernameSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $username);

    // Files to clean up user data
    $jsonFiles = ["positions.json", "contexts.json", "last_seen.json"];
    foreach ($jsonFiles as $file) {
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (isset($data[$username])) { // Use original username
                unset($data[$username]);
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            }
        }
    }

    // Handle chat files more carefully
    foreach (glob("chat_*.txt") as $chatFile) {
        $lines = file($chatFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $filtered = array_filter($lines, function ($line) use ($username) {
            // Only remove exact matches of "username: message"
            $pattern = '/^' . preg_quote($username, '/') . ':/';
            return !preg_match($pattern, $line);
        });
        
        // Only write back if we actually removed something
        if (count($filtered) !== count($lines)) {
            file_put_contents($chatFile, implode(PHP_EOL, $filtered) . PHP_EOL);
        }
    }

    // Clean up DM files
    $dmFiles = glob("dms/*--{$username}.json") + glob("dms/{$username}--*.json");
    foreach ($dmFiles as $dmFile) {
        if (file_exists($dmFile)) {
            unlink($dmFile);
        }
    }

    echo "User '$username' has been removed from all systems.";
} else {
    echo "No user specified.";
}
?>
