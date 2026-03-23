<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php';
require_once 'includes/mail_functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "--- Testing Hostinger SMTP ---\n";

try {
    $mail = getMailerInstance($conn);
    
    // Explicitly check properties
    echo "Host: " . $mail->Host . "\n";
    echo "Port: " . $mail->Port . "\n";
    echo "Username: " . $mail->Username . "\n";
    echo "SMTPSecure: " . $mail->SMTPSecure . "\n";
    
    // Enable debug output
    $mail->SMTPDebug = 3; 
    $mail->Debugoutput = 'echo';

    $admin_email = $global_settings['admin_email'] ?? 'admin@store.com';
    $mail->addAddress($admin_email);
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Debug Test';
    $mail->Body    = 'Testing Hostinger SMTP from a script.';
    
    echo "Attempting to send...\n";
    if($mail->send()) {
        echo "\nSUCCESS! Email sent to $admin_email\n";
    } else {
        echo "\nFAILED! PHPMailer Error: " . $mail->ErrorInfo . "\n";
    }
} catch (Exception $e) {
    echo "\nEXCEPTION! " . $e->getMessage() . "\n";
}
