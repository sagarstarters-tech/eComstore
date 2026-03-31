<?php
function sync_cart_to_db($conn) {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
        try {
            $conn->query("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `cart_data` TEXT DEFAULT NULL AFTER `password`");
        } catch (\Throwable $e) { }

        $user_id = intval($_SESSION['user_id']);
        $cart_json = isset($_SESSION['cart']) ? json_encode($_SESSION['cart']) : json_encode([]);
        $stmt = $conn->prepare("UPDATE users SET cart_data = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $cart_json, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function load_cart_from_db($conn, $user_id) {
    try {
        $conn->query("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `cart_data` TEXT DEFAULT NULL AFTER `password`");
    } catch (\Throwable $e) { }

    $stmt = $conn->prepare("SELECT cart_data FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $db_cart = !empty($row['cart_data']) ? json_decode($row['cart_data'], true) : [];
            if (!is_array($db_cart)) $db_cart = [];
            
            // Merge with current session cart if exists
            $session_cart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
            
            $merged_cart = $db_cart;
            foreach ($session_cart as $product_id => $qty) {
                if (isset($merged_cart[$product_id])) {
                    $merged_cart[$product_id] += $qty;
                } else {
                    $merged_cart[$product_id] = $qty;
                }
            }
            
            $_SESSION['cart'] = $merged_cart;
            
            // Save back the merged cart
            $cart_json = json_encode($merged_cart);
            $upd = $conn->prepare("UPDATE users SET cart_data = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param("si", $cart_json, $user_id);
                $upd->execute();
                $upd->close();
            }
        }
        $stmt->close();
    }
}
?>
