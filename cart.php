<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Получаем корзину пользователя
$cart_items = [];
$total = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Получаем корзину пользователя
    $sql_cart = "SELECT cart_id FROM cart WHERE user_id = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();
    $cart = $result_cart->fetch_assoc();
    
    if ($cart) {
        $cart_id = $cart['cart_id'];
        
        // Получаем товары в корзине
        $sql_items = "SELECT ci.*, p.name, p.price, p.photo, p.stock 
                      FROM cartitems ci 
                      JOIN products p ON ci.product_id = p.product_id 
                      WHERE ci.cart_id = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $cart_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        
        while ($item = $result_items->fetch_assoc()) {
            $item_total = $item['price'] * $item['quantity'];
            $cart_items[] = $item;
            $total += $item_total;
        }
    }
}

include 'includes/header.php';
?>

<section class="cart-section">
    <div class="container">
        <h1>Корзина</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <p>Ваша корзина пуста</p>
                <a href="products.php" class="btn btn-primary">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Цена</th>
                                <th>Количество</th>
                                <th>Сумма</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr data-cart-item-id="<?= $item['cart_item_id'] ?>">
                                    <td class="cart-product">
                                        <img src="assets/uploads/<?= htmlspecialchars($item['photo']) ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             onerror="this.src='assets/uploads/default.jpg'">
                                        <a href="product.php?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                                    </td>
                                    <td class="cart-price"><?= number_format($item['price'], 2) ?> ₽</td>
                                    <td class="cart-quantity">
                                        <input type="number" 
                                               class="cart-quantity-input" 
                                               data-item-id="<?= $item['cart_item_id'] ?>"
                                               value="<?= $item['quantity'] ?>" 
                                               min="1" 
                                               max="<?= $item['stock'] ?>">
                                    </td>
                                    <td class="cart-subtotal"><?= number_format($item['price'] * $item['quantity'], 2) ?> ₽</td>
                                    <td class="cart-remove">
                                        <button class="btn-remove" data-item-id="<?= $item['cart_item_id'] ?>">Удалить</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="cart-summary">
                    <h3>Итого</h3>
                    <div class="summary-row">
                        <span>Общая сумма:</span>
                        <span class="total-amount"><?= number_format($total, 2) ?> ₽</span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary btn-checkout">Оформить заказ</a>
                    <a href="products.php" class="btn btn-secondary">Продолжить покупки</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>