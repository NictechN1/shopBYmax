<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Необходимо авторизоваться']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;

if ($cart_item_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    exit;
}

$conn->begin_transaction();

try {
    $sql_check = "SELECT ci.cart_item_id 
                  FROM cartitems ci 
                  JOIN cart c ON ci.cart_id = c.cart_id 
                  WHERE ci.cart_item_id = ? AND c.user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $cart_item_id, $user_id);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows == 0) {
        throw new Exception('Товар не найден');
    }
    
    $sql_delete = "DELETE FROM cartitems WHERE cart_item_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $cart_item_id);
    $stmt_delete->execute();
    
    $sql_cart = "SELECT cart_id FROM cart WHERE user_id = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $cart = $stmt_cart->get_result()->fetch_assoc();
    
    $sql_total = "SELECT SUM(p.price * ci.quantity) as total 
                  FROM cartitems ci 
                  JOIN products p ON ci.product_id = p.product_id 
                  WHERE ci.cart_id = ?";
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param("i", $cart['cart_id']);
    $stmt_total->execute();
    $total_result = $stmt_total->get_result();
    $total = $total_result->fetch_assoc()['total'] ?? 0;
    
    $sql_count = "SELECT COUNT(*) as count FROM cartitems WHERE cart_id = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $cart['cart_id']);
    $stmt_count->execute();
    $count_result = $stmt_count->get_result();
    $item_count = $count_result->fetch_assoc()['count'] ?? 0;
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'total' => $total,
        'total_formatted' => number_format($total, 2) . ' ₽',
        'cart_empty' => ($item_count == 0)
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>