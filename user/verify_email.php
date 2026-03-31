<?php
include '../includes/header.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $msg = "<div class='alert alert-danger'>Invalid or missing verification token.</div>";
} else {
    $token = $_GET['token'];
    
    $stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE verification_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $check = $stmt->get_result();

    if ($check->num_rows > 0) {
        $user = $check->fetch_assoc();
        $stmt->close();
        
        if ($user['is_verified'] == 1) {
            $msg = "<div class='alert alert-info'>Account is already verified! You can now login.</div>";
        } else {
            $upd = $conn->prepare("UPDATE users SET is_verified=1, verification_token=NULL WHERE verification_token=?");
            $upd->bind_param("s", $token);
            $upd->execute();
            $upd->close();
            $msg = "<div class='alert alert-success'>Email verified successfully! You can now login to your account.</div>";
        }
    } else {
        $stmt->close();
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
