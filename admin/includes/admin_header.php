<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Подключаем основной CSS сайта
$css_path = '../../assets/css/style.css';
if (!file_exists($css_path)) {
    $css_path = '../assets/css/style.css';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - shopBYmax</title>
    <link rel="stylesheet" href="<?= $css_path ?>">
    <style>
        /* Дополнительные стили для админки */
        body {
            background: #f0f2f5;
        }
        
        /* Админ-шапка */
        .admin-top-bar {
            background: #16213e;
            color: white;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-top-bar .logo {
            font-size: 20px;
            font-weight: 700;
        }
        
        .admin-top-bar .logo a {
            color: white;
            text-decoration: none;
        }
        
        .admin-top-bar .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-top-bar .user-info span {
            color: #e94560;
            font-weight: 600;
        }
        
        .admin-top-bar .user-info a {
            color: #a0aec0;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .admin-top-bar .user-info a:hover {
            color: white;
        }
        
        /* Боковое меню */
        .admin-layout {
            display: flex;
            min-height: calc(100vh - 56px);
        }
        
        .admin-sidebar {
            width: 260px;
            background: white;
            box-shadow: 2px 0 12px rgba(0,0,0,0.05);
            padding: 24px 0;
        }
        
        .admin-sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .admin-sidebar a:hover {
            background: #f7fafc;
            color: #e94560;
        }
        
        .admin-sidebar a.active {
            background: #f7fafc;
            color: #e94560;
            border-left-color: #e94560;
        }
        
        .admin-sidebar .sidebar-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 16px 24px;
        }
        
        /* Основной контент */
        .admin-main {
            flex: 1;
            padding: 32px;
        }
        
        /* Карточки статистики */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-icon {
            font-size: 48px;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #16213e;
        }
        
        .stat-label {
            color: #718096;
            font-size: 14px;
        }
        
        /* Таблицы */
        .admin-table-wrapper {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th {
            background: #f7fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .admin-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }
        
        .admin-table tr:hover {
            background: #f7fafc;
        }
        
        /* Кнопки действий */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-edit {
            background: #e94560;
            color: white;
        }
        
        .btn-edit:hover {
            background: #c62a47;
        }
        
        .btn-delete {
            background: #e53e3e;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c53030;
        }
        
        .btn-approve {
            background: #38a169;
            color: white;
        }
        
        .btn-approve:hover {
            background: #2f855a;
        }
        
        .btn-reject {
            background: #ed8936;
            color: white;
        }
        
        .btn-reject:hover {
            background: #dd6b20;
        }
        
        .btn-view {
            background: #4299e1;
            color: white;
        }
        
        .btn-view:hover {
            background: #3182ce;
        }
        
        /* Статусы */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-processing {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
        }
        
        /* Формы */
        .admin-form {
            max-width: 800px;
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .form-section {
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-section h3 {
            margin-bottom: 16px;
            color: #16213e;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .current-photo {
            margin-top: 8px;
        }
        
        .current-photo img {
            max-width: 150px;
            border-radius: 12px;
        }
        
        /* Фильтры */
        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .filter-btn {
            padding: 6px 16px;
            background: #f0f2f5;
            color: #4a5568;
            text-decoration: none;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #e94560;
            color: white;
        }
        
        .search-form {
            display: flex;
            gap: 12px;
            margin-left: auto;
        }
        
        .search-form input {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            width: 250px;
        }
        
        /* Заголовок страницы */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #16213e;
        }
        
        /* Адаптив */
        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }
            .admin-sidebar {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                padding: 12px;
                gap: 4px;
            }
            .admin-sidebar a {
                padding: 8px 16px;
            }
            .admin-main {
                padding: 20px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Верхняя панель -->
<div class="admin-top-bar">
    <div class="logo">
        <a href="dashboard.php">🎧 shopBYmax | Админ-панель</a>
    </div>
    <div class="user-info">
        <span><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
        <a href="../index.php">🌐 На сайт</a>
        <a href="../logout.php">🚪 Выход</a>
    </div>
</div>

<div class="admin-layout">
    <!-- Боковое меню -->
    <div class="admin-sidebar">
        <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            📊 Панель
        </a>
        <a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' || basename($_SERVER['PHP_SELF']) == 'add_product.php' || basename($_SERVER['PHP_SELF']) == 'edit_product.php' ? 'active' : '' ?>">
            📦 Товары
        </a>
        <a href="categories.php" class="<?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
            🏷️ Категории
        </a>
        <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' || basename($_SERVER['PHP_SELF']) == 'order_details.php' ? 'active' : '' ?>">
            🛒 Заказы
        </a>
        <a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : '' ?>">
            ⭐ Отзывы
        </a>
        <a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
            👥 Пользователи
        </a>
        <div class="sidebar-divider"></div>
        <a href="../index.php">🏠 На сайт</a>
    </div>
    
    <div class="admin-main">