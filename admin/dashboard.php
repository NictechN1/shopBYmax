<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$sql_products = "SELECT COUNT(*) as total FROM products";
$result_products = $conn->query($sql_products);
$total_products = $result_products->fetch_assoc()['total'];

$sql_users = "SELECT COUNT(*) as total FROM users";
$result_users = $conn->query($sql_users);
$total_users = $result_users->fetch_assoc()['total'];

$sql_orders = "SELECT COUNT(*) as total, SUM(total_amount) as total_sum FROM orders";
$result_orders = $conn->query($sql_orders);
$orders_data = $result_orders->fetch_assoc();
$total_orders = $orders_data['total'];
$total_revenue = $orders_data['total_sum'] ?? 0;

$sql_pending_reviews = "SELECT COUNT(*) as total FROM reviews WHERE status = 'pending'";
$result_reviews = $conn->query($sql_pending_reviews);
$pending_reviews = $result_reviews->fetch_assoc()['total'];

$sql_recent_orders = "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.created_at DESC LIMIT 5";
$recent_orders = $conn->query($sql_recent_orders);

include 'includes/admin_header.php';
?>

<div class="admin-dashboard">
    <h1>Панель управления</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_products ?></div>
                <div class="stat-label">Товаров</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_users ?></div>
                <div class="stat-label">Пользователей</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_orders ?></div>
                <div class="stat-label">Заказов</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format($total_revenue, 0) ?> ₽</div>
                <div class="stat-label">Выручка</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-info">
                <div class="stat-value"><?= $pending_reviews ?></div>
                <div class="stat-label">Отзывов на модерации</div>
            </div>
        </div>
    </div>
    
    <div class="recent-orders">
        <h2>Последние заказы</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID заказа</th>
                    <th>Пользователь</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $order['order_id'] ?></td>
                        <td><?= htmlspecialchars($order['username']) ?></td>
                        <td><?= number_format($order['total_amount'], 2) ?> ₽</td>
                        <td>
                            <span class="status-<?= $order['status'] ?>">
                                <?= $order['status'] ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                        <td>
                            <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn-small">Просмотр</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>