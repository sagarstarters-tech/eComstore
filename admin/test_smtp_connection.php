<?php
require_once 'core/AuthMiddleware.php';
if (!AuthMiddleware::isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Session Role: ' . ($_SESSION['role'] ?? 'NONE')]);
    exit;
}
require_once '../includes/db_connect.php';
require_once '../includes/mail_functions.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'test_smtp') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Gather settings from POST, or fallback to config if env selected
$provider = $_POST['smtp_provider'] ?? 'env';

if ($provider === 'env') {
    // Rely completely on getMailerInstance() logic reading constants
    $mail = getMailerInstance(); 
} else {
    $host = $_POST['smtp_host'] ?? '';
    $port = $_POST['smtp_port'] ?? '';
    $user = $_POST['smtp_username'] ?? '';
    $pass = $_POST['smtp_password'] ?? '';
    $encryption = $_POST['smtp_encryption'] ?? 'tls';
    $sender_email = $_POST['smtp_sender_email'] ?? '';
    $sender_name = $_POST['smtp_sender_name'] ?? '';

    // If password is '********', we need to fetch the real one from DB
    if ($pass === '********' || empty($pass)) {
        $q = $conn->query("SELECT setting_value FROM settings WHERE setting_key='smtp_password'");
        if ($q && $q->num_rows > 0) {
            $enc_pass = $q->fetch_assoc()['setting_value'];
            if (!empty($enc_pass)) {
                $encryption_key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_fallback_secret_key_123!';
                if (strpos($enc_pass, '::') !== false) {
                    list($encrypted_data, $iv) = explode('::', base64_decode($enc_pass), 2);
                    $pass = openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
                } else {
                    $pass = $enc_pass;
                }
            }
        }
    }

    if (empty($host) || empty($user) || empty($pass)) {
        echo json_encode(['success' => false, 'message' => 'Host, Username, and Password are required for testing custom providers.']);
        exit;
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = strtolower($encryption);
    $mail->Port       = (int)$port;
    
    // Bypass SSL certificate verification for local environments (like XAMPP)
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    $from_e = !empty($sender_email) ? $sender_email : $user;
    $from_n = !empty($sender_name) ? $sender_name : 'SMTP Test System';
    $mail->setFrom($from_e, $from_n);
}

// Find Admin Email to send the test to
$admin_email = 'admin@store.com';
$settings_q = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'admin_email'");
if ($settings_q && $settings_q->num_rows > 0) {
    $admin_email = $settings_q->fetch_assoc()['setting_value'];
}
// Try to fallback to current logged in user email if admin email not valid
if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
    $uid = $_SESSION['user_id'];
    $u_q = $conn->query("SELECT email FROM users WHERE id=$uid");
    if ($u_q && $u_q->num_rows > 0) {
        $admin_email = $u_q->fetch_assoc()['email'];
    }
}

try {
    $mail->addAddress($admin_email);
    $mail->isHTML(true);
    $mail->Subject = 'Test SMTP Connection - Sagar Starters';
    $mail->Body    = '<b>Success!</b> Your SMTP email settings have been configured properly.';
    $mail->AltBody = 'Success! Your SMTP email settings have been configured properly.';
    
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Email Sent Successfully to ' . $admin_email]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'SMTP Configuration Error: ' . $mail->ErrorInfo]);
}
?>
