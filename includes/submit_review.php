<?php
/**
 * Handle new review submissions (AJAX POST)
 */
ob_start(); // Prevent any accidental whitespace/output before JSON

require_once __DIR__ . '/session_setup.php';
require_once __DIR__ . '/db_connect.php';

// Always respond with JSON
header('Content-Type: application/json');

// Discard any buffered output so far (stray whitespace from includes, etc.)
ob_clean();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id      = intval($_SESSION['user_id']);
$product_id   = isset($_POST['product_id'])   ? intval($_POST['product_id'])         : 0;
$rating       = isset($_POST['rating'])        ? intval($_POST['rating'])             : 0;
$review_title = isset($_POST['review_title'])  ? trim($_POST['review_title'])         : '';
$review_text  = isset($_POST['review_text'])   ? trim($_POST['review_text'])          : '';

// Basic validation
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please select a star rating (1–5).']);
    exit;
}
if (empty($review_text)) {
    echo json_encode(['success' => false, 'message' => 'Please write your review before submitting.']);
    exit;
}

// Optional Image Upload Handling (max 3 images)
$uploaded_images = [];
if (!empty($_FILES['review_images']['name'][0])) {
    $upload_dir = dirname(__DIR__) . '/assets/images/reviews/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_count = count($_FILES['review_images']['name']);
    $max_files  = min($file_count, 3);

    for ($i = 0; $i < $max_files; $i++) {
        if ($_FILES['review_images']['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['review_images']['tmp_name'][$i];
            $ext      = strtolower(pathinfo($_FILES['review_images']['name'][$i], PATHINFO_EXTENSION));

            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed)) {
                $new_name = uniqid('rev_') . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $uploaded_images[] = 'reviews/' . $new_name;
                }
            }
        }
    }
}

$images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;
$status      = 'approved';

try {
    // Check if this user already reviewed this product
    $dup_stmt = $conn->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ? LIMIT 1");
    if ($dup_stmt) {
        $dup_stmt->bind_param("ii", $product_id, $user_id);
        $dup_stmt->execute();
        $dup_stmt->store_result();
        if ($dup_stmt->num_rows > 0) {
            $dup_stmt->close();
            echo json_encode(['success' => false, 'message' => 'You have already submitted a review for this product.']);
            exit;
        }
        $dup_stmt->close();
    }

    // Insert review
    $stmt = $conn->prepare(
        "INSERT INTO product_reviews (product_id, user_id, rating, review_title, review_text, images, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("iiissss", $product_id, $user_id, $rating, $review_title, $review_text, $images_json, $status);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to save review: ' . $stmt->error]);
        $stmt->close();
        exit;
    }

    $stmt->close();

    // Recalculate average rating & review count
    $calc_stmt = $conn->prepare(
        "SELECT AVG(rating) AS avg_rating, COUNT(id) AS total_reviews
         FROM product_reviews WHERE product_id = ? AND status = 'approved'"
    );
    if ($calc_stmt) {
        $calc_stmt->bind_param("i", $product_id);
        $calc_stmt->execute();
        $calc_result  = $calc_stmt->get_result()->fetch_assoc();
        $calc_stmt->close();

        $avg_rating    = $calc_result['avg_rating']    ? round($calc_result['avg_rating'], 2) : 0.00;
        $total_reviews = $calc_result['total_reviews'] ? intval($calc_result['total_reviews']) : 0;

        // Update products table
        $upd_stmt = $conn->prepare("UPDATE products SET average_rating = ?, review_count = ? WHERE id = ?");
        if ($upd_stmt) {
            $upd_stmt->bind_param("dii", $avg_rating, $total_reviews, $product_id);
            $upd_stmt->execute();
            $upd_stmt->close();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Thank you! Your review has been submitted successfully.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
