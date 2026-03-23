<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php';
require_once 'includes/mail_functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "--- Testing Hostinger SMTP (FORCED DB) ---\n";

$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
$db_settings = [];
while ($row = $res->fetch_assoc()) {
    $db_settings[$row['setting_key']] = $row['setting_value'];
}

$host = $db_settings['smtp_host'] ?? 'smtp.hostinger.com';
$port = (int)($db_settings['smtp_port'] ?? 465);
$user = $db_settings['smtp_username'] ?? '';
$enc_pass = $db_settings['smtp_password'] ?? '';
$encryption_key = 'default_fallback_secret_key_123!';
$decoded = base64_decode($enc_pass);
list($encrypted_data, $iv) = explode('::', $decoded, 2);
$pass = openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
$secure = strtolower($db_settings['smtp_encryption'] ?? 'ssl');

echo "Host: $host\n";
echo "Port: $port\n";
echo "User: $user\n";
echo "Secure: $secure\n";

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->SMTPDebug = 3;
$mail->Debugoutput = 'echo';

$mail->Host       = $host;
$mail->SMTPAuth   = true;
$mail->Username   = $user;
$mail->Password   = $pass;
$mail->SMTPSecure = $secure;
$mail->Port       = $port;

// Standard Hostinger fix: force SSL/TLS options if needed
$mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);

$mail->setFrom($user, "SMTP Test System");
$mail->addAddress($user); 
$mail->isHTML(true);
$mail->Subject = 'Hostinger SMTP Test';
$mail->Body    = 'Test body.';

try {
    if($mail->send()) {
        echo "\nSUCCESS!\n";
    } else {
        echo "\nFAILED! PHPMailer Error: " . $mail->ErrorInfo . "\n";
    }
} catch (Exception $e) {
    echo "\nEXCEPTION! " . $e->getMessage() . "\n";
}
