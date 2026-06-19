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
            
            // DB cart is the source of truth.
            // Only add NEW guest session items (items not already in DB cart).
            // This prevents deleted items from reappearing after logout/login.
            $session_cart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
            
            $final_cart = $db_cart;
            foreach ($session_cart as $product_id => $qty) {
                if (!isset($final_cart[$product_id])) {
                    // Only add items that are NOT in the DB cart (truly new guest additions)
                    $final_cart[$product_id] = $qty;
                }
                // If item exists in DB cart, keep the DB quantity (don't merge/add)
            }
            
            $_SESSION['cart'] = $final_cart;
            
            // Save back the final cart to DB
            $cart_json = json_encode($final_cart);
            $upd = $conn->prepare("UPDATE users SET cart_data = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param("si", $cart_json, $user_id);
                $upd->execute();
                $upd->close();
            }
        } else {
            // No DB record found — keep session cart as-is (new user scenario)
        }
        $stmt->close();
    }
}
?>
