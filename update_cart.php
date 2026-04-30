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
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($cart_item_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    exit;
}

$conn->begin_transaction();

try {
    $sql_check_item = "SELECT ci.*, p.stock, p.price 
                       FROM cartitems ci 
                       JOIN products p ON ci.product_id = p.product_id 
                       JOIN cart c ON ci.cart_id = c.cart_id 
                       WHERE ci.cart_item_id = ? AND c.user_id = ?";
    $stmt_check = $conn->prepare($sql_check_item);
    $stmt_check->bind_param("ii", $cart_item_id, $user_id);
    $stmt_check->execute();
    $item = $stmt_check->get_result()->fetch_assoc();
    
    if (!$item) {
        throw new Exception('Товар не найден в корзине');
    }
    
    if ($quantity > $item['stock']) {
        throw new Exception('Недостаточно товара на складе');
    }
    
    $sql_update = "UPDATE cartitems SET quantity = ? WHERE cart_item_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $quantity, $cart_item_id);
    $stmt_update->execute();
    
    $subtotal = $item['price'] * $quantity;
    
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
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'subtotal' => $subtotal,
        'subtotal_formatted' => number_format($subtotal, 2) . ' ₽',
        'total' => $total,
        'total_formatted' => number_format($total, 2) . ' ₽'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>