<?php
require 'c:\xampp\htdocs\eComstore\includes\db_connect.php';

$set_q = $conn->query("SELECT api_token, phone_number_id, waba_id, meta_template_name FROM whatsapp_settings WHERE id = 1");
$settings = $set_q->fetch_assoc();

$token = trim($settings['api_token']);
$waba_id = trim($settings['waba_id']);
$template_name = trim($settings['meta_template_name'] ?: 'order_status_update');

$ch = curl_init("https://graph.facebook.com/v19.0/{$waba_id}/message_templates?name={$template_name}");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo "TEMPLATE DETAILS:\n\n";
echo print_r(json_decode($res, true), true);
?>
