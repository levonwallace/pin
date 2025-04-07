<?php
if (isset($_POST['user']) && isset($_POST['pin'])) {
    $user = htmlspecialchars($_POST['user']);
    $pin = htmlspecialchars($_POST['pin']);

    $file = 'contexts.json';

    if (file_exists($file)) {
        $json = file_get_contents($file);
        $contexts = json_decode($json, true);
    } else {
        $contexts = [];
    }

    $contexts[$user] = $pin;

    file_put_contents($file, json_encode($contexts));
}
?>
