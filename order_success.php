<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_SESSION['last_order_id'];
$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM orders WHERE order_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit;
}

unset($_SESSION['last_order_id']);

include 'includes/header.php';
?>

<section class="success-section">
    <div class="container">
        <div class="success-card">
            <div class="success-icon">✓</div>
            <h1>Заказ оформлен!</h1>
            <p>Номер заказа: <strong>#<?= $order_id ?></strong></p>
            <p>Сумма заказа: <strong><?= number_format($order['total_amount'], 2) ?> ₽</strong></p>
            <p>Статус: <strong><?= $order['status'] ?></strong></p>
            <p>Дата заказа: <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
            
            <div class="success-info">
                <p>Наш менеджер свяжется с вами для подтверждения заказа.</p>
                <p>Следите за статусом заказа в <a href="profile.php">личном кабинете</a>.</p>
            </div>
            
            <div class="success-actions">
                <a href="profile.php" class="btn btn-primary">В личный кабинет</a>
                <a href="products.php" class="btn btn-secondary">Продолжить покупки</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>