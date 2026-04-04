<?php
require_once 'includes/db_connect.php';
$q = $conn->query("SELECT * FROM whatsapp_settings");
while($s = $q->fetch_assoc()):
echo "ID: " . $s['id'] . "\n";
echo "MESSAGE_TEMPLATE:\n" . $s['message_template'] . "\n";
echo "META_TEMPLATE_NAME: [" . ($s['meta_template_name'] ?? 'NOT SET') . "]\n";
echo "META_TEMPLATE_LANG: [" . ($s['meta_template_lang'] ?? 'NOT SET') . "]\n";
echo "WA_HEADER_IMAGE_URL: [" . ($s['wa_header_image_url'] ?? 'NOT SET') . "]\n";
echo str_repeat('-', 20) . "\n";
endwhile;
echo "DONE\n";
