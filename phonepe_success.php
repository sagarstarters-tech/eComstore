<?php
// phonepe_success.php
include 'includes/header.php';

// ── Extract Transaction IDs Robustly (Redirect Flow) ──────────
$merchantTransactionId = $_REQUEST['merchantTransactionId'] ?? $_REQUEST['transactionId'] ?? '';
$code = $_REQUEST['code'] ?? '';
$amount = $_REQUEST['amount'] ?? 0;

// PhonePe often sends a base64 encoded 'response' parameter containing the full status
if (isset($_REQUEST['response'])) {
    $res_payload = json_decode(base64_decode($_REQUEST['response']), true);
    if ($res_payload) {
        if (empty($code)) $code = $res_payload['code'] ?? '';
        if (empty($merchantTransactionId)) {
            $merchantTransactionId = $res_payload['data']['merchantTransactionId'] ?? $res_payload['data']['transactionId'] ?? '';
        }
        if (empty($amount)) $amount = $res_payload['data']['amount'] ?? 0;
    }
}

$merchantTransactionId = $conn->real_escape_string($merchantTransactionId);

// Log the redirect request for debugging if it's the first time
// file_put_contents('logs/phonepe_redirect.log', "[" . date('Y-m-d H:i:s') . "] REDIRECT: " . json_encode($_REQUEST) . "\n", FILE_APPEND);

if (strtoupper($code) === 'PAYMENT_SUCCESS' || strtoupper($code) === 'SUCCESS') {
    // Clear cart since payment succeeded
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }

    // Try to get Order ID from DB
    $order_id = 'N/A';
    $txn_q = $conn->query("SELECT order_id, amount, status, raw_payload FROM phonepe_transactions WHERE transaction_id = '$merchantTransactionId'");
    if ($txn_q && $txn_q->num_rows > 0) {
        $txn = $txn_q->fetch_assoc();
        $order_id = $txn['order_id'];
        
        // If the webhook hasn't fired yet (e.g. localhost), update the DB manually here
        if ($txn['status'] !== 'SUCCESS') {
            $conn->query("UPDATE phonepe_transactions SET status = 'SUCCESS' WHERE transaction_id = '$merchantTransactionId'");
            $conn->query("UPDATE orders SET status = 'processing', payment_method = 'phonepe' WHERE id = $order_id");
            
            // Send Order Confirmation Email
            require_once 'includes/mail_functions.php';
            $ord_q = $conn->query("SELECT * FROM orders WHERE id=$order_id");
            if($ord_q->num_rows > 0) {
                $ord = $ord_q->fetch_assoc();
                $user_id = $ord['user_id'];
                $grand_total = $ord['total_amount'];
        
                $usr_q = $conn->query("SELECT * FROM users WHERE id=$user_id");
                $usr = $usr_q->fetch_assoc();
                
                $cart_items = [];
                $item_q = $conn->query("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id");
                while($itm = $item_q->fetch_assoc()) {
                    $cart_items[] = [
                        'name' => $itm['name'],
                        'qty' => $itm['quantity'],
                        'price' => $itm['price']
                    ];
                }
                
                sendOrderConfirmationEmail($conn, $order_id, $usr['email'], $usr['name'], $cart_items, $grand_total, $global_currency ?? '₹', 'phonepe');
                
                // Activate Digital Downloads
                require_once 'includes/digital_product_functions.php';
                activateDigitalDownloads($conn, $order_id);
            }
        }
    }

    
    // PhonePe returns amount in paisa
    $displayAmount = number_format($amount / 100, 2);

    ?>
    <div class="container mt-5 pt-5 mb-5 text-center">
        <div class="card shadow-sm border-0 p-5 mx-auto" style="max-width: 600px;">
            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
            <h2 class="fw-bold mb-3">Payment Successful</h2>
            <p class="text-muted fs-5 mb-4">Your payment has been processed successfully.</p>
            
            <div class="bg-light p-4 rounded text-start mb-4 border">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary fw-bold">Order ID:</span>
                    <span class="fw-bold text-dark">#<?php echo htmlspecialchars($order_id); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary fw-bold">Transaction ID:</span>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($merchantTransactionId); ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-secondary fw-bold">Amount Paid:</span>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($global_settings['currency_symbol'] ?? '₹'); ?><?php echo $displayAmount; ?></span>
                </div>
            </div>
            
            <a href="user/orders.php" class="btn btn-primary btn-lg btn-custom px-5">View My Orders</a>
        </div>
    </div>
    
    <!-- PhonePe Requested Popup -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            alert("Payment Successful!\nOrder ID: <?php echo htmlspecialchars($order_id); ?>\nTransaction ID: <?php echo htmlspecialchars($merchantTransactionId); ?>");
        });
    </script>
    <?php
} else {
    // It's a failure code
    echo "<script>window.location.href = 'phonepe_failure.php?transactionId=" . urlencode($merchantTransactionId) . "';</script>";
}

include 'includes/footer.php';
?>
