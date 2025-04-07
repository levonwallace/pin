<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    http_response_code(403);
    echo "Not authorized";
    exit;
}

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $pinsFile = "pins.json";

    if (file_exists($pinsFile)) {
        $pins = json_decode(file_get_contents($pinsFile), true);
        if (isset($pins[$id])) {
            unset($pins[$id]);
            file_put_contents($pinsFile, json_encode($pins));
            echo "Deleted";
            exit;
        }
    }
}

http_response_code(400);
echo "Invalid request";
