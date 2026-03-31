<?php
include_once __DIR__ . '/session_setup.php';
include 'db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'mail_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Security check failed. Please submit the form again.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    if ($action === 'signup') {
        require_once __DIR__ . '/RateLimiter.php';
        $limiter = new RateLimiter(3, 600, 'signup_');
        
        if ($limiter->isBlocked()) {
            $mins = ceil($limiter->getRemainingLockSeconds() / 60);
            $_SESSION['error'] = "Too many signup attempts. Please try again in {$mins} minute(s).";
            header("Location: ../user/signup.php");
            exit;
        }

        if (empty($_POST['agree_terms'])) {
            $_SESSION['error'] = "You must agree to the Terms of Service and Privacy Policy.";
            $limiter->recordFailure();
            header("Location: ../user/signup.php");
            exit;
        }

        if (strlen($_POST['password'] ?? '') < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters long.";
            $limiter->recordFailure();
            header("Location: ../user/signup.php");
            exit;
        }

        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $address = $conn->real_escape_string($_POST['address'] ?? '');
        $city = $conn->real_escape_string($_POST['city'] ?? '');
        $state = $conn->real_escape_string($_POST['state'] ?? '');
        $country = $conn->real_escape_string($_POST['country'] ?? '');
        $zip_code = $conn->real_escape_string($_POST['zip_code'] ?? '');
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR phone=?");
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        $check = $stmt->get_result();
        
        if ($check->num_rows > 0) {
            $_SESSION['error'] = "This email or mobile number already exists!";
            $_SESSION['error_popup'] = "This email or mobile number already exists!";
            $stmt->close();
            $limiter->recordFailure();
            header("Location: ../user/signup.php");
            exit;
        }
        $stmt->close();

        $token = bin2hex(random_bytes(32)); // Generate secure random token

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, city, state, country, zip_code, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssssssssss", $name, $email, $password, $phone, $address, $city, $state, $country, $zip_code, $token);
        
        if ($stmt->execute()) {
            $stmt->close();
            // Actual SMTP Email Setup
            // Build verification link using SITE_URL constant (respects http/https & subdirectory)
            $base = defined('SITE_URL') && SITE_URL !== ''
                ? rtrim(SITE_URL, '/')
                : (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
            $verify_link = $base . "/user/verify_email.php?token=" . $token;

            require_once 'mail_functions.php';
            
            try {
                $mail = getMailerInstance($conn);

                // Recipients
                $mail->addAddress($email, $name);

                // Content
                $mail->isHTML(true);

                // Fetch template
                $tpl = getEmailTemplate($conn, 'signup_verification');
                if ($tpl) {
                    $vars = ['name' => $name, 'verify_link' => $verify_link];
                    $mail->Subject = parseTemplate($tpl['subject'], $vars);
                    $mail->Body = parseTemplate($tpl['body'], $vars);
                    $mail->AltBody = strip_tags($mail->Body);
                } else {
                    $mail->Subject = "Verify Your Account at Sagar Starter's";
                    $mail->Body    = "<h2>Welcome to Sagar Starter's!</h2>
                                      <p>Dear $name,</p>
                                      <p>Thank you for registering. Please click the link below to verify your email address:</p>
                                      <p><a href='$verify_link'>$verify_link</a></p>
                                      <br>
                                      <p>If you didn't request this, ignore this email.</p>";
                    $mail->AltBody = "Welcome to Sagar Starter's! Please verify your email by copying this link: $verify_link";
                }

                $mail->send();
                $_SESSION['success'] = "Registration successful! We have sent a verification link to <b>$email</b>. Please check your inbox (and spam folder) before logging in.";
            } catch (Exception $e) {
                // If it fails for local reasons, notify them
                $_SESSION['success'] = "Registration successful! (Developer Note: SMTP email failed. Ensure `mail_config.php` has valid Gmail credentials!) For local testing verify manually: <a href='$verify_link'>$verify_link</a>. Mailer Error: {$mail->ErrorInfo}";
            }
            
            $limiter->reset();
            header("Location: ../user/login.php");
        } else {
            $_SESSION['error'] = "Something went wrong!";
            $limiter->recordFailure();
            header("Location: ../user/signup.php");
        }
        exit;
    }

    if ($action === 'login') {
        require_once __DIR__ . '/RateLimiter.php';
        $limiter = new RateLimiter(5, 900);

        if ($limiter->isBlocked()) {
            $mins = ceil($limiter->getRemainingLockSeconds() / 60);
            $_SESSION['error'] = "Too many failed login attempts. Please try again in {$mins} minute(s).";
            header("Location: ../user/login.php");
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stmt->close();
            if (password_verify($password, $user['password'])) {
                // Check if verified
                if ($user['role'] !== 'admin' && $user['is_verified'] == 0) {
                    $_SESSION['error'] = "Please verify your email address before logging in. Check your inbox or registration success alert.";
                    header("Location: ../user/login.php");
                    exit;
                }

                $limiter->reset();
                session_regenerate_id(true); // Prevent Session Fixation
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_photo'] = $user['profile_photo'] ?? '';
                
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/index.php");
                } else {
                    header("Location: ../index.php");
                }
                exit;
            }
        }
        $limiter->recordFailure();
        $left = $limiter->getAttemptsLeft();
        $_SESSION['error'] = "Invalid credentials!" . ($left > 0 ? " ({$left} attempt(s) remaining)" : " Account temporarily locked for 15 minutes.");
        header("Location: ../user/login.php");
        exit;
    }
}

// Handle GET actions like logout
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'logout') {
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: ../user/login.php");
        exit;
    }
}
?>
