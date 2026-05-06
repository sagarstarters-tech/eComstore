<?php
/**
 * Admin Invoice View — Standalone printable page
 * Opens in a new tab with action buttons (Print, Download PDF, WhatsApp)
 */
include_once __DIR__ . '/../includes/session_setup.php';
include_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
AuthMiddleware::check($conn);

require_once __DIR__ . '/../includes/InvoiceService.php';
require_once __DIR__ . '/../includes/invoice_render.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    http_response_code(400);
    die('<h2 style="text-align:center;margin-top:60px;font-family:sans-serif;color:#c0392b;">Invalid Order ID</h2>');
}

$invoiceService = new InvoiceService($conn);

// Auto-generate invoice if it doesn't exist yet
$invoice = $invoiceService->getInvoiceByOrder($order_id);
if (!$invoice) {
    $result = $invoiceService->generateInvoice($order_id);
    if (!$result['success']) {
        die('<h2 style="text-align:center;margin-top:60px;font-family:sans-serif;color:#c0392b;">Error: ' . htmlspecialchars($result['message']) . '</h2>');
    }
}

// Get full data for rendering
$data = $invoiceService->getFullInvoiceData($order_id);
if (!$data) {
    die('<h2 style="text-align:center;margin-top:60px;font-family:sans-serif;color:#c0392b;">Invoice data not found.</h2>');
}

// Render the invoice HTML (standalone page, with action buttons, admin mode)
echo renderInvoiceHTML($data, true, true);
