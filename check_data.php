<?php
define('BASE_PATH', __DIR__);
require 'includes/db_connect.php';
$slides_q = $conn->query("SELECT * FROM hero_slides WHERE is_active = 1 LIMIT 1");
if ($slide = $slides_q->fetch_assoc()) {
    echo "Slide Data:\n";
    foreach ($slide as $key => $value) {
        if (strpos($key, 'btn') !== false) {
            echo "$key: '$value'\n";
        }
    }
} else {
    echo "No active slides found.\n";
}
