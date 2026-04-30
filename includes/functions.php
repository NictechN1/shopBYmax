<?php
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function getCartCount($conn, $user_id) {
    $sql = "SELECT SUM(ci.quantity) as total 
            FROM cart c 
            LEFT JOIN cartitems ci ON c.cart_id = ci.cart_id 
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['total'] ?? 0;
}

function getProductById($conn, $product_id) {
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getUserById($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getUserCart($conn, $user_id) {
    $sql = "SELECT cart_id FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getCartItems($conn, $cart_id) {
    $sql = "SELECT ci.*, p.name, p.price, p.photo, p.stock 
            FROM cartitems ci 
            JOIN products p ON ci.product_id = p.product_id 
            WHERE ci.cart_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    return $stmt->get_result();
}

function calculateCartTotal($conn, $cart_id) {
    $sql = "SELECT SUM(p.price * ci.quantity) as total 
            FROM cartitems ci 
            JOIN products p ON ci.product_id = p.product_id 
            WHERE ci.cart_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['total'] ?? 0;
}

function getUserOrders($conn, $user_id, $limit = null) {
    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getOrderItems($conn, $order_id) {
    $sql = "SELECT oi.*, p.name, p.photo 
            FROM orderitems oi 
            JOIN products p ON oi.product_id = p.product_id 
            WHERE oi.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getProductReviews($conn, $product_id, $status = 'approved') {
    $sql = "SELECT r.*, u.username 
            FROM reviews r 
            JOIN users u ON r.user_id = u.user_id 
            WHERE r.product_id = ? AND r.status = ? 
            ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $product_id, $status);
    $stmt->execute();
    return $stmt->get_result();
}

function getProductRating($conn, $product_id) {
    $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total 
            FROM reviews 
            WHERE product_id = ? AND status = 'approved'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function userHasReviewed($conn, $user_id, $product_id) {
    $sql = "SELECT review_id FROM reviews WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function getAllCategories($conn) {
    $sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category";
    $result = $conn->query($sql);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    return $categories;
}

function formatPrice($price) {
    return number_format($price, 2, '.', ' ') . ' ₽';
}

function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

function getStatusLabel($status) {
    $labels = [
        'pending' => 'Ожидает',
        'processing' => 'В обработке',
        'completed' => 'Выполнен',
        'cancelled' => 'Отменен'
    ];
    return $labels[$status] ?? $status;
}

function updateProductStock($conn, $product_id, $quantity) {
    $sql = "UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $quantity, $product_id, $quantity);
    return $stmt->execute() && $stmt->affected_rows > 0;
}

function clearUserCart($conn, $user_id) {
    $cart = getUserCart($conn, $user_id);
    if ($cart) {
        $sql = "DELETE FROM cartitems WHERE cart_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cart['cart_id']);
        return $stmt->execute();
    }
    return false;
}

function createOrder($conn, $user_id, $total_amount, $delivery_address, $delivery_method, $payment_method) {
    $sql = "INSERT INTO orders (user_id, total_amount, delivery_address, delivery_method, payment_method, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idsss", $user_id, $total_amount, $delivery_address, $delivery_method, $payment_method);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

function addOrderItem($conn, $order_id, $product_id, $quantity, $price) {
    $sql = "INSERT INTO orderitems (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
    return $stmt->execute();
}

function getNewProducts($conn, $limit = 8) {
    $sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function searchProducts($conn, $query, $limit = 12, $offset = 0) {
    $search = "%$query%";
    $sql = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $search, $search, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result();
}

function countSearchResults($conn, $query) {
    $search = "%$query%";
    $sql = "SELECT COUNT(*) as total FROM products WHERE name LIKE ? OR description LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

function uploadFile($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_types)) {
        return false;
    }
    
    $new_filename = time() . '_' . uniqid() . '.' . $ext;
    $target_path = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $new_filename;
    }
    
    return false;
}
?>