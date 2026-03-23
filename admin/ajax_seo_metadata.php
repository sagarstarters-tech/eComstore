<?php
include_once __DIR__ . '/../includes/session_setup.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/SeoRepository.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

$repo = new SeoRepository($conn);
$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

$metadata = $repo->getMetadata($type, $id);

echo json_encode($metadata ?: [
    'meta_title' => '',
    'meta_description' => '',
    'canonical_url' => '',
    'og_title' => '',
    'og_description' => '',
    'og_image' => '',
    'schema_markup' => ''
]);
