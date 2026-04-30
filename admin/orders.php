<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$sql = "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.user_id";
$count_sql = "SELECT COUNT(*) as total FROM orders o";

if ($status_filter) {
    $sql .= " WHERE o.status = ?";
    $count_sql .= " WHERE status = ?";
}

$sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($count_sql);
if ($status_filter) {
    $stmt->bind_param("s", $status_filter);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_orders = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

$stmt = $conn->prepare($sql);
if ($status_filter) {
    $stmt->bind_param("sii", $status_filter, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$orders = $stmt->get_result();

include 'includes/admin_header.php';
?>

<div class="admin-orders">
    <h1>Управление заказами</h1>
    
    <div class="filters">
        <a href="orders.php" class="btn-small <?= !$status_filter ? 'active' : '' ?>">Все</a>
        <a href="?status=pending" class="btn-small <?= $status_filter == 'pending' ? 'active' : '' ?>">Ожидают</a>
        <a href="?status=processing" class="btn-small <?= $status_filter == 'processing' ? 'active' : '' ?>">В обработке</a>
        <a href="?status=completed" class="btn-small <?= $status_filter == 'completed' ? 'active' : '' ?>">Выполнены</a>
        <a href="?status=cancelled" class="btn-small <?= $status_filter == 'cancelled' ? 'active' : '' ?>">Отменены</a>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Дата</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $orders->fetch_assoc()): ?>
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
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?>" 
                   class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>