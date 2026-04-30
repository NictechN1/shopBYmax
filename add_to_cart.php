<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Необходимо авторизоваться']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверный товар']);
    exit;
}

if ($quantity <= 0) {
    $quantity = 1;
}

$conn->begin_transaction();

try {
    $sql_check_stock = "SELECT stock FROM products WHERE product_id = ?";
    $stmt_check = $conn->prepare($sql_check_stock);
    $stmt_check->bind_param("i", $product_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $product = $result->fetch_assoc();
    
    if (!$product || $product['stock'] < $quantity) {
        throw new Exception('Недостаточно товара на складе');
    }
    
    $sql_cart = "SELECT cart_id FROM cart WHERE user_id = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();
    $cart = $result_cart->fetch_assoc();
    
    if (!$cart) {
        $sql_create = "INSERT INTO cart (user_id) VALUES (?)";
        $stmt_create = $conn->prepare($sql_create);
        $stmt_create->bind_param("i", $user_id);
        $stmt_create->execute();
        $cart_id = $conn->insert_id;
    } else {
        $cart_id = $cart['cart_id'];
    }
    
    $sql_check_item = "SELECT cart_item_id, quantity FROM cartitems WHERE cart_id = ? AND product_id = ?";
    $stmt_check_item = $conn->prepare($sql_check_item);
    $stmt_check_item->bind_param("ii", $cart_id, $product_id);
    $stmt_check_item->execute();
    $existing = $stmt_check_item->get_result()->fetch_assoc();
    
    if ($existing) {
        $new_quantity = $existing['quantity'] + $quantity;
        $sql_update = "UPDATE cartitems SET quantity = ? WHERE cart_item_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $new_quantity, $existing['cart_item_id']);
        $stmt_update->execute();
    } else {
        $sql_insert = "INSERT INTO cartitems (cart_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iii", $cart_id, $product_id, $quantity);
        $stmt_insert->execute();
    }
    
    $sql_count = "SELECT SUM(quantity) as total FROM cartitems WHERE cart_id = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $cart_id);
    $stmt_count->execute();
    $count_result = $stmt_count->get_result();
    $cart_count = $count_result->fetch_assoc()['total'] ?? 0;
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'cart_count' => $cart_count]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>