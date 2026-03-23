<?php include '../includes/header.php'; ?>
<div class="container mt-5 mb-5" style="min-height: 50vh;">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card product-card">
                <div class="card-body p-5">
                    <h3 class="text-center montserrat primary-blue mb-4">Forgot Password</h3>
                    <p class="text-center text-muted">Enter your email and we'll send you a link to reset your password.</p>
                    <?php
                    require_once '../includes/db_connect.php';
                    require_once '../includes/mail_functions.php';

                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $email = $conn->real_escape_string($_POST['email']);
                        $check = $conn->query("SELECT id, name FROM users WHERE email='$email'");

                        if ($check->num_rows > 0) {
                            $user = $check->fetch_assoc();
                            
                            // Generate token and expiry (1 hour)
                            $token = bin2hex(random_bytes(32));
                            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                            $conn->query("UPDATE users SET reset_token='$token', reset_token_expiry='$expiry' WHERE email='$email'");

                            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/user/reset_password.php?token=" . $token;

                            try {
                                $mail = getMailerInstance();
                                $mail->addAddress($email, $user['name']);
                                $mail->isHTML(true);

                                // Fetch template
                                $tpl = getEmailTemplate($conn, 'password_reset');
                                if ($tpl) {
                                    $vars = ['name' => $user['name'], 'reset_link' => $reset_link];
                                    $mail->Subject = parseTemplate($tpl['subject'], $vars);
                                    $mail->Body = parseTemplate($tpl['body'], $vars);
                                } else {
                                    $mail->Subject = 'Password Reset Request';
                                    $mail->Body = "
                                        <h3>Password Reset</h3>
                                        <p>Hi {$user['name']},</p>
                                        <p>You requested a password reset. Click the link below to set a new password. This link will expire in 1 hour.</p>
                                        <p><a href='$reset_link'>$reset_link</a></p>
                                        <p>If you didn't request this, you can safely ignore this email.</p>
                                    ";
                                }
                                $mail->send();
                            } catch (Exception $e) {
                                // Silent fail as per standard security practices for forgot password, but optional error log
                            }
                        }
                        
                        // Always show the same success message to prevent user enumeration
                        echo '<div class="alert alert-success">If that email is in our database, we have sent a reset link to it.</div>';
                    }
                    ?>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom w-100 mb-3">Send Reset Link</button>
                    </form>
                    <p class="text-center"><a href="login.php" class="text-decoration-none">Back to Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
