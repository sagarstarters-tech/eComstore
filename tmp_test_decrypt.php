<?php
require_once 'includes/db_connect.php';
$encryption_key = 'default_fallback_secret_key_123!';

$q = $conn->query("SELECT setting_value FROM settings WHERE setting_key='smtp_password'");
if ($q && $row = $q->fetch_assoc()) {
    $enc_pass = $row['setting_value'];
    echo "Encrypted stored string: $enc_pass\n";
    
    $decoded = base64_decode($enc_pass);
    if (strpos($decoded, '::') !== false) {
        list($encrypted_data, $iv) = explode('::', $decoded, 2);
        $pass = openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
        echo "Decrypted password length: " . strlen($pass) . "\n";
        echo "Decrypted password: " . $pass . "\n";
    } else {
        echo "Separator :: not found in decoded string.\n";
    }
}
