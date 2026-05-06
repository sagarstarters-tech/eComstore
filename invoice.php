<?php
/**
 * Public Invoice View — Secure token-based access
 * Customers can view, print, and download their invoice via a link
 */
include_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/InvoiceService.php';
require_once __DIR__ . '/includes/invoice_render.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token) || strlen($token) !== 64 || !ctype_xdigit($token)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Access Denied</title><style>
        body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f5f5f5;margin:0;}
        .box{text-align:center;background:#fff;padding:50px;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.08);}
        h1{color:#e74c3c;font-size:48px;margin-bottom:10px;}
        p{color:#666;font-size:16px;}
    </style></head><body><div class="box"><h1>403</h1><p>Invalid or expired invoice link.</p></div></body></html>';
    exit;
}

$invoiceService = new InvoiceService($conn);
$invoice = $invoiceService->getInvoiceByToken($token);

if (!$invoice) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>Not Found</title><style>
        body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f5f5f5;margin:0;}
        .box{text-align:center;background:#fff;padding:50px;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.08);}
        h1{color:#e74c3c;font-size:48px;margin-bottom:10px;}
        p{color:#666;font-size:16px;}
    </style></head><body><div class="box"><h1>404</h1><p>Invoice not found or has been removed.</p></div></body></html>';
    exit;
}

// Mark as viewed
if ($invoice['status'] === 'generated' || $invoice['status'] === 'sent') {
    $conn->query("UPDATE invoices SET status = 'viewed' WHERE id = " . intval($invoice['id']));
}

// Get full invoice data
$data = $invoiceService->getFullInvoiceData($invoice['order_id']);
if (!$data) {
    http_response_code(500);
    die('Error loading invoice data.');
}

// Render with action buttons but NOT admin mode (no WhatsApp/back buttons)
echo renderInvoiceHTML($data, true, false);
