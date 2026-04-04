<?php
require_once 'includes/db_connect.php';
$q = $conn->query("SELECT * FROM whatsapp_settings WHERE id = 1");
$settings = $q->fetch_assoc();

if (!$settings || empty($settings['api_token'])) {
    die("No settings found or API token missing.");
}

$token = trim($settings['api_token']);
$phone_id = trim($settings['phone_number_id']);
$waba_id = trim($settings['waba_id']);

if (empty($waba_id)) {
    // Try to fetch WABA ID
    $ch = curl_init("https://graph.facebook.com/v19.0/{$phone_id}?fields=whatsapp_business_account_id");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    $waba_id = $data['whatsapp_business_account_id'] ?? '';
}

if (empty($waba_id)) {
    die("Could not find WABA ID. Please make sure your token and Phone ID are correct.");
}

echo "WABA ID: $waba_id\n";
echo "Fetching templates...\n";

$ch = curl_init("https://graph.facebook.com/v19.0/{$waba_id}/message_templates?limit=100");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$data = json_decode($res, true);

if (isset($data['error'])) {
    die("Meta API Error: " . $data['error']['message']);
}

foreach ($data['data'] as $tpl) {
    if ($tpl['name'] === 'order_status_updates') {
        echo "Template Found: " . $tpl['name'] . " (" . $tpl['status'] . ")\n";
        echo "Language: " . $tpl['language'] . "\n";
        foreach ($tpl['components'] as $comp) {
            echo "Component: " . $comp['type'] . "\n";
            if (!empty($comp['text'])) {
                echo "Text: " . $comp['text'] . "\n";
                // Count placeholders
                preg_match_all('/\{\{(\d+)\}\}/', $comp['text'], $matches);
                echo "Placeholders: " . count(array_unique($matches[1] ?? [])) . " (" . implode(',', $matches[1] ?? []) . ")\n";
            }
        }
        break;
    }
}
