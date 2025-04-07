<?php
if (isset($_GET['context'])) {
    // sanitize
    $context = preg_replace("/[^a-zA-Z0-9_-]/", "", $_GET['context']);
    $file = "chat_" . $context . ".txt";
    if (file_exists($file)) {
        // Return raw text
        header('Content-Type: text/plain; charset=utf-8');
        echo file_get_contents($file);
    } else {
        echo "";
    }
}
?>
