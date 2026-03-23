<?php 
include '../includes/header.php'; 
require_once '../includes/db_connect.php';

$error = '';
$success = '';
$token_valid = false;
$email = '';

if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    // Check if token exists and is not expired
    $query = "SELECT email, id FROM users WHERE reset_token='$token' AND reset_token_expiry > NOW()";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $token_valid = true;
        $user = $result->fetch_assoc();
        $email = $user['email'];
        $user_id = $user['id'];
        
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                
                // Update password and clear reset token
                $update_sql = "UPDATE users SET password='$hashed_password', reset_token=NULL, reset_token_expiry=NULL WHERE id=$user_id";
                
                if ($conn->query($update_sql)) {
                    $success = "Your password has been successfully reset. You can now login.";
                    $token_valid = false; // Hide form after success
                } else {
                    $error = "Failed to update password. Please try again later.";
                }
            }
        }
    } else {
        $error = "Invalid or expired password reset link. Please request a new one.";
    }
} else {
    $error = "No reset token provided.";
}
?>

<div class="container mt-5 mb-5" style="min-height: 50vh;">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card product-card">
                <div class="card-body p-5">
                    <h3 class="text-center montserrat primary-blue mb-4">Reset Password</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php if (!$token_valid && !$success): ?>
                            <p class="text-center"><a href="forgot_password.php" class="btn btn-primary btn-custom">Request New Link</a></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                        <p class="text-center"><a href="login.php" class="btn btn-primary btn-custom w-100">Go to Login</a></p>
                    <?php endif; ?>
                    
                    <?php if ($token_valid): ?>
                    <p class="text-center text-muted mb-4">Create a new password for <?php echo htmlspecialchars($email); ?></p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                            <div class="form-check mt-1">
                                <input class="form-check-input show-password-toggle" type="checkbox" id="showPwReset1">
                                <label class="form-check-label small text-muted" for="showPwReset1">Show password</label>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            <div class="form-check mt-1">
                                <input class="form-check-input show-password-toggle" type="checkbox" id="showPwReset2">
                                <label class="form-check-label small text-muted" for="showPwReset2">Show password</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom w-100 mb-3">Save New Password</button>
                    </form>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
