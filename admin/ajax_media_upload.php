<?php
/**
 * AJAX Media Upload Handler
 * 
 * Handles secure file uploads for the Media Library.
 * Returns JSON response.
 */

header('Content-Type: application/json; charset=UTF-8');

// Boot
include_once __DIR__ . '/../includes/session_setup.php';
include_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
require_once __DIR__ . '/helpers/csrf.php';

// Auth check
try {
    AuthMiddleware::check($conn);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF check
$submitted_token = $_POST['_csrf_token'] ?? '';
$stored_token = $_SESSION['csrf_token'] ?? '';
if (empty($stored_token) || !hash_equals($stored_token, $submitted_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token mismatch. Refresh the page.']);
    exit;
}

// ── Configuration ─────────────────────────────────────────
$ALLOWED_IMAGE_TYPES = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'
];
$ALLOWED_VIDEO_TYPES = [
    'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'
];
$ALLOWED_TYPES = array_merge($ALLOWED_IMAGE_TYPES, $ALLOWED_VIDEO_TYPES);
$MAX_FILE_SIZE = 50 * 1024 * 1024; // 50 MB

$ALLOWED_EXTENSIONS = [
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
    'mp4', 'webm', 'ogg', 'mov'
];

// ── Validate Upload ──────────────────────────────────────
if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file received.']);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server temporary folder missing.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload stopped by PHP extension.',
    ];
    $msg = $error_messages[$file['error']] ?? 'Unknown upload error.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Size check
if ($file['size'] > $MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File exceeds 50 MB limit.']);
    exit;
}

// MIME type check (both reported and verified)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$real_mime = $finfo->file($file['tmp_name']);

if (!in_array($real_mime, $ALLOWED_TYPES)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed: ' . $real_mime]);
    exit;
}

// Extension check
$original_name = basename($file['name']);
$extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

if (!in_array($extension, $ALLOWED_EXTENSIONS)) {
    echo json_encode(['success' => false, 'message' => 'File extension not allowed: .' . $extension]);
    exit;
}

// ── Sanitize file name ───────────────────────────────────
// Remove any dangerous characters from the original name
$safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
$safe_name = substr($safe_name, 0, 100); // Limit length
$unique_name = $safe_name . '_' . uniqid() . '.' . $extension;

// ── Determine category ───────────────────────────────────
$is_image = in_array($real_mime, $ALLOWED_IMAGE_TYPES);
$file_type = $is_image ? 'image' : 'video';
$sub_folder = $is_image ? 'images' : 'videos';

// ── Upload path ──────────────────────────────────────────
$upload_dir = realpath(__DIR__ . '/../uploads') . '/media/' . $sub_folder;
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$dest_path = $upload_dir . '/' . $unique_name;
$relative_path = 'uploads/media/' . $sub_folder . '/' . $unique_name;

// Determine file URL (relative from admin/ — consistent with how banner images use ASSETS_URL)
$site_url = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$file_url = $site_url . '/' . $relative_path;

// ── SVG sanitization ─────────────────────────────────────
if ($extension === 'svg') {
    $svg_content = file_get_contents($file['tmp_name']);
    // Remove script tags and event handlers from SVG
    $svg_content = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $svg_content);
    $svg_content = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $svg_content);
    // Remove potentially dangerous elements
    $svg_content = preg_replace('/<\s*(iframe|object|embed|applet|form|input|button)[^>]*>/i', '', $svg_content);
    
    if (file_put_contents($dest_path, $svg_content) === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to save SVG file.']);
        exit;
    }
} else {
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
        exit;
    }
}

// ── Get image dimensions ─────────────────────────────────
$width = null;
$height = null;
if ($is_image && $extension !== 'svg') {
    $img_info = @getimagesize($dest_path);
    if ($img_info) {
        $width = $img_info[0];
        $height = $img_info[1];
    }
}

// ── Store in database ────────────────────────────────────
$stmt = $conn->prepare("
    INSERT INTO media_library 
        (file_name, original_name, file_path, file_url, file_type, mime_type, file_size, width, height, uploaded_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$user_id = $_SESSION['user_id'] ?? null;
$stmt->bind_param(
    'ssssssiiis',
    $unique_name,
    $original_name,
    $relative_path,
    $file_url,
    $file_type,
    $real_mime,
    $file['size'],
    $width,
    $height,
    $user_id
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully.',
        'data'    => [
            'id'            => $stmt->insert_id,
            'file_name'     => $unique_name,
            'original_name' => $original_name,
            'file_url'      => $file_url,
            'file_type'     => $file_type,
            'mime_type'     => $real_mime,
            'file_size'     => $file['size'],
            'width'         => $width,
            'height'        => $height,
        ]
    ]);
} else {
    // Cleanup file if DB insert fails
    if (file_exists($dest_path)) unlink($dest_path);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
