<?php
include '../includes/header.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $msg = "<div class='alert alert-danger'>Invalid or missing verification token.</div>";
} else {
    $token = $conn->real_escape_string($_GET['token']);
    
    $check = $conn->query("SELECT id, is_verified FROM users WHERE verification_token='$token'");
    if ($check->num_rows > 0) {
        $user = $check->fetch_assoc();
        
        if ($user['is_verified'] == 1) {
            $msg = "<div class='alert alert-info'>Account is already verified! You can now login.</div>";
        } else {
            $conn->query("UPDATE users SET is_verified=1, verification_token=NULL WHERE verification_token='$token'");
            $msg = "<div class='alert alert-success'>Email verified successfully! You can now login to your account.</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'>Invalid verification token.</div>";
    }
}
?>

<div class="container mt-5 py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <h2 class="mb-4 fw-bold montserrat primary-blue">Email Verification</h2>
            <?php echo $msg; ?>
            <div class="mt-4">
                <a href="login.php" class="btn btn-primary btn-custom px-4 py-2">Go to Login</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
