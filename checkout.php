<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Получаем данные пользователя
$sql_user = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

// Получаем корзину
$sql_cart = "SELECT cart_id FROM cart WHERE user_id = ?";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$cart = $stmt_cart->get_result()->fetch_assoc();

if (!$cart) {
    header('Location: cart.php');
    exit;
}

$cart_id = $cart['cart_id'];

$sql_items = "SELECT ci.*, p.name, p.price, p.stock 
              FROM cartitems ci 
              JOIN products p ON ci.product_id = p.product_id 
              WHERE ci.cart_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $cart_id);
$stmt_items->execute();
$cart_items = $stmt_items->get_result();

if ($cart_items->num_rows == 0) {
    header('Location: cart.php');
    exit;
}

// Обработка оформления заказа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $delivery_address = trim($_POST['delivery_address']);
    $delivery_method = $_POST['delivery_method'];
    $payment_method = $_POST['payment_method'];
    
    if (empty($delivery_address)) {
        $errors[] = "Укажите адрес доставки";
    }
    
    if (empty($errors)) {
        $total_amount = 0;
        $order_items = [];
        
        // Пересчитываем корзину
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $cart_id);
        $stmt_items->execute();
        $cart_items = $stmt_items->get_result();
        
        while ($item = $cart_items->fetch_assoc()) {
            $item_total = $item['price'] * $item['quantity'];
            $total_amount += $item_total;
            $order_items[] = $item;
        }
        
        // Создаем заказ
        $conn->begin_transaction();
        
        try {
            $sql_order = "INSERT INTO orders (user_id, total_amount, delivery_address, delivery_method, payment_method, status) 
                          VALUES (?, ?, ?, ?, ?, 'pending')";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->bind_param("idsss", $user_id, $total_amount, $delivery_address, $delivery_method, $payment_method);
            $stmt_order->execute();
            $order_id = $conn->insert_id;
            
            // Добавляем товары в заказ
            $sql_order_item = "INSERT INTO orderitems (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
            $stmt_order_item = $conn->prepare($sql_order_item);
            
            foreach ($order_items as $item) {
                $stmt_order_item->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt_order_item->execute();
                
                // Обновляем остатки
                $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
                $stmt_stock = $conn->prepare($sql_update_stock);
                $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt_stock->execute();
            }
            
            // Очищаем корзину
            $sql_clear = "DELETE FROM cartitems WHERE cart_id = ?";
            $stmt_clear = $conn->prepare($sql_clear);
            $stmt_clear->bind_param("i", $cart_id);
            $stmt_clear->execute();
            
            $conn->commit();
            
            $_SESSION['last_order_id'] = $order_id;
            header('Location: order_success.php');
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Ошибка при оформлении заказа";
        }
    }
}

include 'includes/header.php';
?>

<section class="checkout-section">
    <div class="container">
        <h1>Оформление заказа</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-content">
            <div class="checkout-form">
                <form method="POST">
                    <div class="form-group">
                        <label>ФИО</label>
                        <input type="text" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="text" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Адрес доставки *</label>
                        <textarea name="delivery_address" rows="3" required><?= htmlspecialchars($_POST['delivery_address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Способ доставки *</label>
                        <select name="delivery_method" required>
                            <option value="">Выберите способ</option>
                            <option value="courier">Курьером</option>
                            <option value="pickup">Самовывоз</option>
                            <option value="post">Почта России</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Способ оплаты *</label>
                        <select name="payment_method" required>
                            <option value="">Выберите способ</option>
                            <option value="card">Банковская карта онлайн</option>
                            <option value="cash">Наличными при получении</option>
                            <option value="online">Электронные деньги</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="place_order" class="btn btn-primary btn-place-order">Подтвердить заказ</button>
                </form>
            </div>
            
            <div class="checkout-summary">
                <h3>Ваш заказ</h3>
                <div class="order-items">
                    <?php 
                    $stmt_items = $conn->prepare($sql_items);
                    $stmt_items->bind_param("i", $cart_id);
                    $stmt_items->execute();
                    $checkout_items = $stmt_items->get_result();
                    $checkout_total = 0;
                    while ($item = $checkout_items->fetch_assoc()):
                        $checkout_total += $item['price'] * $item['quantity'];
                    ?>
                        <div class="order-item">
                            <span><?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?></span>
                            <span><?= number_format($item['price'] * $item['quantity'], 2) ?> ₽</span>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="order-total">
                    <strong>Итого:</strong>
                    <strong><?= number_format($checkout_total, 2) ?> ₽</strong>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>