<?php
require 'c:\xampp\htdocs\eComstore\includes\db_connect.php';

$set_q = $conn->query("SELECT api_token, phone_number_id, waba_id FROM whatsapp_settings WHERE id = 1");
$settings = $set_q->fetch_assoc();

$token = trim($settings['api_token']);
$waba_id = trim($settings['waba_id']);
$tpl_name = 'order_status_update';

$url = "https://graph.facebook.com/v19.0/{$waba_id}/message_templates?name={$tpl_name}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$data = json_decode($res, true);

echo "TEMPLATE DEFINITION FOR: $tpl_name\n";
echo "====================================\n";
if (!empty($data['data'])) {
    foreach ($data['data'][0]['components'] as $comp) {
        echo "Type: " . $comp['type'] . "\n";
        if (isset($comp['text'])) echo "Text: " . $comp['text'] . "\n";
        if (isset($comp['format'])) echo "Format: " . $comp['format'] . "\n";
        echo "------------------\n";
    }
} else {
    print_r($data);
}
?>
