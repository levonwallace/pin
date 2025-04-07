<?php
$file = 'pins.json';
if (file_exists($file)) {
    header('Content-Type: application/json');
    echo file_get_contents($file);
} else {
    echo "{}";
}
?>
