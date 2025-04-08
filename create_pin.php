<?php
if (isset($_POST['user']) && isset($_POST['title']) && isset($_POST['x']) && isset($_POST['y'])) {
    $user = htmlspecialchars($_POST['user']);
    $title = htmlspecialchars($_POST['title']);
    $x = intval($_POST['x']);
    $y = intval($_POST['y']);
    $type = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : 'text';
    $audioUrl = isset($_POST['audioUrl']) ? htmlspecialchars($_POST['audioUrl']) : null;

    $file = 'pins.json';

    if (file_exists($file)) {
        $json = file_get_contents($file);
        $pins = json_decode($json, true);
    } else {
        $pins = [];
    }

    $id = uniqid();
    $pins[$id] = [
        'id' => $id,
        'title' => $title,
        'user' => $user,
        'x' => $x,
        'y' => $y,
        'type' => $type
    ];

    if ($type === 'audio' && $audioUrl) {
        $pins[$id]['audioUrl'] = $audioUrl;
    }

    file_put_contents($file, json_encode($pins));
}
?>
