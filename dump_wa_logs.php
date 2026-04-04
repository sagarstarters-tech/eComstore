<?php
require_once 'includes/db_connect.php';
$q = $conn->query("SELECT * FROM whatsapp_logs ORDER BY id DESC LIMIT 10");
while($s = $q->fetch_assoc()):
echo "ID: " . $s['id'] . "\n";
echo "ORDER ID: " . $s['order_id'] . "\n";
echo "TO: " . $s['customer_number'] . "\n";
echo "STATUS: " . $s['status'] . "\n";
echo "SENT AT: " . $s['sent_at'] . "\n";
echo str_repeat('-', 20) . "\n";
endwhile;
echo "DONE\n";
