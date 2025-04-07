<?php
if (isset($_POST['user']) && isset($_POST['x']) && isset($_POST['y'])) {
    $user = htmlspecialchars($_POST['user']);
    $x = intval($_POST['x']);
    $y = intval($_POST['y']);

    // --- Handle position ---
    $posFile = 'positions.json';
    $positions = file_exists($posFile) ? json_decode(file_get_contents($posFile), true) : [];
    $positions[$user] = ['x' => $x, 'y' => $y];
    file_put_contents($posFile, json_encode($positions));

    // --- Handle last seen ---
    $seenFile = 'last_seen.json';
    $lastSeen = file_exists($seenFile) ? json_decode(file_get_contents($seenFile), true) : [];
    $lastSeen[$user] = time(); // store timestamp
    file_put_contents($seenFile, json_encode($lastSeen));
}
?>
