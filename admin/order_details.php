<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT o.*, u.username, u.email, u.full_name, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE o.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$sql_items = "SELECT oi.*, p.name, p.photo 
              FROM orderitems oi 
              JOIN products p ON oi.product_id = p.product_id 
              WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

include 'includes/admin_header.php';
?>

<div class="order-details">
    <div class="admin-header">
        <h1>Заказ #<?= $order_id ?></h1>
        <a href="orders.php" class="btn btn-secondary">← Назад к заказам</a>
    </div>
    
    <div class="order-info-grid">
        <div class="order-info-card">
            <h3>Информация о заказе</h3>
            <div class="info-row">
                <span>Дата:</span>
                <span><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span>Статус:</span>
                <span>
                    <form method="POST" action="change_order_status.php" style="display: inline;">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <select name="status" onchange="this.form.submit()">
                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Ожидает</option>
                            <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Выполнен</option>
                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                        </select>
                    </form>
                </span>
            </div>
            <div class="info-row">
                <span>Способ доставки:</span>
                <span><?= htmlspecialchars($order['delivery_method'] ?? 'Не указан') ?></span>
            </div>
            <div class="info-row">
                <span>Способ оплаты:</span>
                <span><?= htmlspecialchars($order['payment_method'] ?? 'Не указан') ?></span>
            </div>
            <div class="info-row">
                <span>Адрес доставки:</span>
                <span><?= nl2br(htmlspecialchars($order['delivery_address'] ?? 'Не указан')) ?></span>
            </div>
        </div>
        
        <div class="order-info-card">
            <h3>Информация о покупателе</h3>
            <div class="info-row">
                <span>Логин:</span>
                <span><?= htmlspecialchars($order['username']) ?></span>
            </div>
            <div class="info-row">
                <span>ФИО:</span>
                <span><?= htmlspecialchars($order['full_name'] ?? 'Не указано') ?></span>
            </div>
            <div class="info-row">
                <span>Email:</span>
                <span><?= htmlspecialchars($order['email']) ?></span>
            </div>
            <div class="info-row">
                <span>Телефон:</span>
                <span><?= htmlspecialchars($order['phone'] ?? 'Не указан') ?></span>
            </div>
        </div>
    </div>
    
    <div class="order-items">
        <h3>Товары в заказе</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Фото</th>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img src="../assets/uploads/<?= htmlspecialchars($item['photo']) ?>" 
                                 style="width: 50px; height: 50px; object-fit: cover;">
                        </td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= number_format($item['price_at_purchase'], 2) ?> ₽</td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?> ₽</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" style="text-align: right;">Итого:</th>
                    <th><?= number_format($order['total_amount'], 2) ?> ₽</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>