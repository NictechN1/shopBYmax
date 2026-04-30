<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>shopBYmax - Аудиотехника</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/cart.js" defer></script>
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="index.php">🎧 shopBYmax</a>
            </div>
            <nav>
                <a href="index.php">Главная</a>
                <a href="products.php">Каталог</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php">👤 Личный кабинет</a>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                        <a href="admin/dashboard.php" class="admin-link"> Управление</a>
                    <?php endif; ?>
                    <a href="logout.php"> Выйти</a>
                <?php else: ?>
                    <a href="login.php"> Вход</a>
                    <a href="register.php"> Регистрация</a>
                <?php endif; ?>
                <a href="cart.php" class="cart-link">🛒 Корзина</a>
            </nav>
        </div>
    </div>
</header>
<main>