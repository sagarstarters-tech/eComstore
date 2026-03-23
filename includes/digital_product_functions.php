<?php
/**
 * Activates digital downloads for a given order.
 * Generates unique tokens and sets expiry dates.
 */
function activateDigitalDownloads($conn, $order_id) {
    // Fetch user and items
    $order_q = $conn->query("SELECT user_id FROM orders WHERE id = $order_id");
    if (!$order_q || $order_q->num_rows === 0) return false;
    $order = $order_q->fetch_assoc();
    $user_id = $order['user_id'];

    $items_q = $conn->query("SELECT oi.product_id, p.product_type, p.download_expiry_days 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = $order_id AND p.product_type = 'downloadable'");

    while ($item = $items_q->fetch_assoc()) {
        $product_id = $item['product_id'];
        
        // Remove existing token if any for this user/product (to refresh it on new purchase)
        // Or keep it? Let's refresh.
        //$conn->query("DELETE FROM user_downloads WHERE user_id = $user_id AND product_id = $product_id");

        $token = bin2hex(random_bytes(16));
        $expiry_date = 'NULL';
        if ($item['download_expiry_days'] > 0) {
            $expiry_date = "'" . date('Y-m-d H:i:s', strtotime("+{$item['download_expiry_days']} days")) . "'";
        }

        // Check if already activated to avoid duplicates on refresh/webhooks
        $check = $conn->query("SELECT id FROM user_downloads WHERE order_id = $order_id AND product_id = $product_id");
        if ($check->num_rows === 0) {
            $conn->query("INSERT INTO user_downloads (user_id, product_id, order_id, download_token, expiry_date) 
                          VALUES ($user_id, $product_id, $order_id, '$token', $expiry_date)");
        }
    }
    return true;
}
?>
