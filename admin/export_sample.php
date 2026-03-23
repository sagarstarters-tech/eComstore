<?php
include_once __DIR__ . '/../includes/session_setup.php';

// Ensure the user is logged in as an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo "You must be logged in as an admin to download the sample file.";
    exit;
}

$filename = "sample-products-import.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

// Define exact headers expected by import
$headers = [
    'Product ID',
    'Product Name',
    'Slug',
    'SKU',
    'Category',
    'Subcategory',
    'Brand',
    'Product Description',
    'Short Description',
    'Price',
    'Discount Price',
    'Stock Quantity',
    'Stock Status',
    'Thumbnail Image',
    'Additional Images (Comma Separated)',
    'Tags',
    'Status (Active/Inactive)',
    'SEO Title',
    'SEO Description',
    'SEO Keywords'
];
fputcsv($output, $headers);

// Provide one example row to help guide the administrator
$example = [
    '', // Leave blank for new products, provide ID to update
    'Sample Wireless Mouse', // Product Name
    'sample-wireless-mouse', // Slug
    'MOUSE-001', // SKU (Ignored in DB currently, but retained for template)
    'Electronics', // Category (Will be created if doesn\'t exist)
    '', 
    '',
    'A high quality wireless mouse with 2.4Ghz connector.', // Product Description
    '',
    '499.00', // Price
    '', 
    '50', // Stock
    'In Stock',
    'mouse.jpg', // Thumbnail (ensure image is in assets/images/)
    'mouse_side.jpg,mouse_bottom.jpg', // Gallery
    'mouse, wireless, tech', // Tags/Keywords
    'Active',
    'Buy Wireless Mouse Online', // SEO Title
    'Shop the best wireless mouse for your workspace.', // SEO Description
    'mouse, pc-accessories' // SEO Keywords
];
fputcsv($output, $example);

fclose($output);
exit;
?>
