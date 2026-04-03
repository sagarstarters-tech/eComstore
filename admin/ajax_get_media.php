<?php
/**
 * AJAX Get Media
 * Returns a JSON array of image media from the media_library table.
 */

header('Content-Type: application/json; charset=UTF-8');

include_once __DIR__ . '/../includes/session_setup.php';
include_once __DIR__ . '/../includes/db_connect.php';

// Auth Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("SELECT * FROM media_library WHERE file_type = 'image' ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id' => $row['id'],
        'file_name' => $row['file_name'],
        'file_url' => '/' . ltrim($row['file_url'], '/'), // Ensure leading slash for absolute path from root
        'original_name' => $row['original_name'],
    ];
}

$stmt->close();

$totalStmt = $conn->query("SELECT COUNT(*) as c FROM media_library WHERE file_type = 'image'");
$total = $totalStmt->fetch_assoc()['c'];

echo json_encode([
    'success' => true,
    'data' => $items,
    'total' => $total,
    'pages' => ceil($total / $limit)
]);
