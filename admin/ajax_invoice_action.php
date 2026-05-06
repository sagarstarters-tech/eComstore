<?php
/**
 * AJAX Invoice Action Handler
 * Handles: generate, send_whatsapp, bulk_generate, update_settings
 */
include_once __DIR__ . '/../includes/session_setup.php';
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

// Auth guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

require_once __DIR__ . '/../includes/InvoiceService.php';
$invoiceService = new InvoiceService($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Generate invoice for a single order ─────────────
    case 'generate':
        $order_id = intval($_POST['order_id'] ?? 0);
        if (!$order_id) {
            echo json_encode(['success' => false, 'error' => 'Missing order ID']);
            exit;
        }
        $result = $invoiceService->generateInvoice($order_id);
        echo json_encode($result);
        break;

    // ── Send invoice via WhatsApp ───────────────────────
    case 'send_whatsapp':
        $order_id = intval($_POST['order_id'] ?? 0);
        if (!$order_id) {
            echo json_encode(['success' => false, 'error' => 'Missing order ID']);
            exit;
        }
        // Generate first if not exists
        $invoiceService->generateInvoice($order_id);
        $result = $invoiceService->sendViaWhatsApp($order_id);
        echo json_encode($result);
        break;

    // ── Bulk generate invoices ──────────────────────────
    case 'bulk_generate':
        $statuses = ['processing', 'shipped', 'delivered', 'completed'];
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $stmt = $conn->prepare("
            SELECT o.id FROM orders o
            LEFT JOIN invoices i ON o.id = i.order_id
            WHERE i.id IS NULL AND o.status IN ($placeholders)
        ");
        $types = str_repeat('s', count($statuses));
        $stmt->bind_param($types, ...$statuses);
        $stmt->execute();
        $result = $stmt->get_result();

        $generated = 0;
        $errors = 0;
        while ($row = $result->fetch_assoc()) {
            $res = $invoiceService->generateInvoice($row['id']);
            if ($res['success']) $generated++;
            else $errors++;
        }
        $stmt->close();

        echo json_encode([
            'success'   => true,
            'generated' => $generated,
            'errors'    => $errors,
            'message'   => "$generated invoices generated" . ($errors ? ", $errors failed" : "")
        ]);
        break;

    // ── Update invoice settings ─────────────────────────
    case 'update_settings':
        $data = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'invoice_') === 0) {
                $data[$key] = trim($value);
            }
        }
        // Handle checkboxes (not sent when unchecked)
        if (!isset($_POST['invoice_auto_generate'])) $data['invoice_auto_generate'] = '0';
        if (!isset($_POST['invoice_auto_send_whatsapp'])) $data['invoice_auto_send_whatsapp'] = '0';

        $invoiceService->updateSettings($data);
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully.']);
        break;

    // ── Resend invoice via WhatsApp ─────────────────────
    case 'resend_whatsapp':
        $order_id = intval($_POST['order_id'] ?? 0);
        if (!$order_id) {
            echo json_encode(['success' => false, 'error' => 'Missing order ID']);
            exit;
        }
        // Reset whatsapp_sent flag to allow resend
        $conn->query("UPDATE invoices SET whatsapp_sent = 0 WHERE order_id = $order_id");
        $result = $invoiceService->sendViaWhatsApp($order_id);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
