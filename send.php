<?php
if (isset($_POST['user']) && isset($_POST['message']) && isset($_POST['context'])) {
    $user = strip_tags($_POST['user']);
    $message = $_POST['message']; // we'll handle formatting in JS
    $context = preg_replace("/[^a-zA-Z0-9_-]/", "", $_POST['context']); // sanitize context name

    $file = "chat_" . $context . ".txt";
    $line = $user . ": " . $message; 
    file_put_contents($file, $line . "\n", FILE_APPEND);
}
?>