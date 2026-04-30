<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$sql_orders = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$orders = $stmt_orders->get_result();

include 'includes/header.php';
?>

<section class="profile-section">
    <div class="container">
        <h1>Личный кабинет</h1>
        
        <div class="profile-content">
            <div class="profile-sidebar">
                <div class="profile-user">
                    <div class="avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                    <h3><?= htmlspecialchars($user['username']) ?></h3>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <a href="edit_profile.php" class="btn btn-secondary">Редактировать профиль</a>
                    <a href="logout.php" class="btn btn-danger">Выйти</a>
                </div>
            </div>
            
            <div class="profile-main">
                <div class="profile-info">
                    <h2>Информация</h2>
                    <div class="info-row">
                        <span>ФИО:</span>
                        <span><?= htmlspecialchars($user['full_name'] ?? 'Не указано') ?></span>
                    </div>
                    <div class="info-row">
                        <span>Телефон:</span>
                        <span><?= htmlspecialchars($user['phone'] ?? 'Не указан') ?></span>
                    </div>
                    <div class="info-row">
                        <span>Адрес:</span>
                        <span><?= htmlspecialchars($user['address'] ?? 'Не указан') ?></span>
                    </div>
                    <div class="info-row">
                        <span>Дата регистрации:</span>
                        <span><?= date('d.m.Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
                
                <div class="profile-orders">
                    <h2>Мои заказы</h2>
                    
                    <?php if ($orders && $orders->num_rows > 0): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>№ заказа</th>
                                    <th>Дата</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= $order['order_id'] ?></td>
                                        <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                                        <td><?= number_format($order['total_amount'], 2) ?> ₽</td>
                                        <td>
                                            <span class="status-<?= $order['status'] ?>">
                                                <?php
                                                $statuses = [
                                                    'pending' => 'Ожидает',
                                                    'processing' => 'В обработке',
                                                    'completed' => 'Выполнен',
                                                    'cancelled' => 'Отменен'
                                                ];
                                                echo $statuses[$order['status']] ?? $order['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-view-order" data-order-id="<?= $order['order_id'] ?>">Детали</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>У вас пока нет заказов</p>
                        <a href="products.php" class="btn btn-primary">Перейти в каталог</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>