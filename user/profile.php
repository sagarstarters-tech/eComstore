<?php
include '../includes/header.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header("Location: profile.php");
        exit;
    }

    $name = $conn->real_escape_string($_POST['name']);
    
    $phone_raw = trim($_POST['phone'] ?? '');
    $phone = $conn->real_escape_string($phone_raw);
    $phone_clean = str_replace([' ', '-', '(', ')', '+'], '', $phone_raw);
    $phone_clean_sql = $conn->real_escape_string($phone_clean);

    if (strlen($phone_clean) > 5) {
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')=? AND id != ?");
        $stmt_check->bind_param("si", $phone_clean_sql, $user_id);
        $stmt_check->execute();
        $check_res = $stmt_check->get_result();
        if ($check_res->num_rows > 0) {
            $_SESSION['error'] = "This phone number is already registered to another account.";
            header("Location: profile.php");
            exit;
        }
        $stmt_check->close();
    }

    $address = $conn->real_escape_string($_POST['address']);
    $city = $conn->real_escape_string($_POST['city']);
    $state = $conn->real_escape_string($_POST['state']);
    $country = $conn->real_escape_string($_POST['country']);
    $zip_code = $conn->real_escape_string($_POST['zip_code']);
    
    // Handle Profile Photo Upload
    $has_new_photo = false;
    $new_filename = "";
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = '../assets/images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $has_new_photo = true;
                $_SESSION['profile_photo'] = $new_filename;
                $user['profile_photo'] = $new_filename;
            }
        }
    }
    
    if ($has_new_photo) {
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, city=?, state=?, country=?, zip_code=?, profile_photo=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $name, $phone, $address, $city, $state, $country, $zip_code, $new_filename, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, city=?, state=?, country=?, zip_code=? WHERE id=?");
        $stmt->bind_param("sssssssi", $name, $phone, $address, $city, $state, $country, $zip_code, $user_id);
    }
    $stmt->execute();
    $stmt->close();
    $_SESSION['name'] = $name;
    $success = "Profile updated successfully.";
    $user['name'] = $name;
    $user['phone'] = $phone;
    $user['address'] = $address;
    $user['city'] = $city;
    $user['state'] = $state;
    $user['country'] = $country;
    $user['zip_code'] = $zip_code;
}
?>
<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card product-card mb-4">
                <div class="card-body text-center p-4">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img src="../assets/images/<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile" class="rounded-circle mb-3 object-fit-cover" style="width: 120px; height: 120px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <?php else: ?>
                        <i class="fas fa-user-circle fa-5x primary-blue mb-3"></i>
                    <?php endif; ?>
                    <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="small text-muted mb-3"><i class="fas fa-phone me-1"></i> <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'No phone set'; ?></p>
                    <a href="../includes/auth.php?action=logout" class="btn btn-danger btn-custom w-100">Logout</a>
                </div>
            </div>
            <div class="card product-card">
                <div class="card-body p-4">
                    <h5 class="montserrat fw-bold mb-3">Shipping Address</h5>
                    <address class="small text-muted mb-0">
                        <?php if(!empty($user['address'])): ?>
                            <?php echo htmlspecialchars($user['address']); ?><br>
                            <?php echo htmlspecialchars($user['city']); ?>, <?php echo htmlspecialchars($user['state']); ?> <?php echo htmlspecialchars($user['zip_code']); ?><br>
                            <?php echo htmlspecialchars($user['country']); ?>
                        <?php else: ?>
                            <em>No address information saved. Please update your profile.</em>
                        <?php endif; ?>
                    </address>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card product-card">
                <div class="card-body p-4">
                    <h4 class="montserrat primary-blue mb-4">Edit Profile</h4>
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="mb-4 text-center">
                            <label for="profile_photo" class="form-label d-block text-start">Profile Photo</label>
                            <input class="form-control" type="file" name="profile_photo" id="profile_photo" accept="image/*">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <?php echo render_phone_input('phone', $user['phone'] ?? '', true); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zip_code" class="form-control" value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">State/Province</label>
                                <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom px-4 mt-2">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
