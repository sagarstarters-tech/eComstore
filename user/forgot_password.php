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
                        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                            echo '<div class="alert alert-danger">Security check failed. Please submit the form again.</div>';
                        } else {
                            require_once '../includes/RateLimiter.php';
                            $limiter = new RateLimiter(3, 600, 'forgot_');

                            if ($limiter->isBlocked()) {
                                $mins = ceil($limiter->getRemainingLockSeconds() / 60);
                                echo "<div class='alert alert-danger'>Too many requests. Please try again in {$mins} minute(s).</div>";
                            } else {
                                $limiter->recordFailure(); // count all attempts toward limit
                                $email = $_POST['email'] ?? '';
                                
                                $stmt = $conn->prepare("SELECT id, name FROM users WHERE email=?");
                                $stmt->bind_param("s", $email);
                                $stmt->execute();
                                $check = $stmt->get_result();

                                if ($check->num_rows > 0) {
                                    $user = $check->fetch_assoc();
                                    
                                    // Generate token and expiry (1 hour)
                                    $token = bin2hex(random_bytes(32));
                                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                                    $upd = $conn->prepare("UPDATE users SET reset_token=?, reset_token_expiry=? WHERE email=?");
                                    $upd->bind_param("sss", $token, $expiry, $email);
                                    $upd->execute();
                                    $upd->close();

                                    $base = defined('SITE_URL') && SITE_URL !== '' ? rtrim(SITE_URL, '/') : (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
                                    $reset_link = $base . "/user/reset_password.php?token=" . $token;

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
                                        echo '<div class="alert alert-success">Password reset link has been sent to your email address!</div>';
                                    } catch (Exception $e) {
                                        echo '<div class="alert alert-danger">Failed to send reset email. Please try again later.</div>';
                                    }
                                } else {
                                    echo '<div class="alert alert-danger">No account found with that email address. Please check and try again.</div>';
                                }
                                $stmt->close();
                            }
                        }
                    }
                    ?>
                    <form method="POST">
                        <?php echo csrf_field(); ?>
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
