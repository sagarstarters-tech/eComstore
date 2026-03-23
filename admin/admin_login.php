<?php
include 'admin_header.php';
require_once BASE_PATH . '/includes/RateLimiter.php';

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    header("Location: index.php");
    exit;
}

$limiter = new RateLimiter(5, 900); // 5 attempts per 15 min

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if ($limiter->isBlocked()) {
        $mins = ceil($limiter->getRemainingLockSeconds() / 60);
        $error = "Too many failed login attempts. Please try again in {$mins} minute(s).";
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='admin'");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $limiter->reset();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['profile_photo'] = $user['profile_photo'] ?? '';
                    $stmt->close();
                    header("Location: index.php");
                    exit;
                }
            }
            $stmt->close();
        }
        $limiter->recordFailure();
        $left = $limiter->getAttemptsLeft();
        $error = "Invalid admin credentials!" . ($left > 0 ? " ({$left} attempt(s) remaining)" : " Account temporarily locked.");
    }
}
?>
<div class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow border-0" style="border-radius: 15px;">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                        <h3 class="montserrat fw-bold">Admin Portal</h3>
                        <p class="text-muted">Secure access only</p>
                    </div>
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger px-3 py-2 text-center"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <?php echo csrf_input(); ?>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Admin Email</label>
                            <input type="email" name="email" class="form-control form-control-lg" placeholder="admin@store.com" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg" placeholder="••••••••" required>
                            <div class="form-check mt-1">
                                <input class="form-check-input show-password-toggle" type="checkbox" id="showPwAdmin">
                                <label class="form-check-label small text-muted" for="showPwAdmin">Show password</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" style="border-radius: 25px;">Login to Dashboard</button>
                    </form>
                    <div class="text-center mt-4">
                        <a href="<?php echo store_url('index.php'); ?>" class="text-muted text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Store</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'admin_footer.php'; ?>
