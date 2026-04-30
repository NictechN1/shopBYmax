<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT * FROM products";
$count_sql = "SELECT COUNT(*) as total FROM products";

if ($search) {
    $sql .= " WHERE name LIKE ? OR description LIKE ?";
    $count_sql .= " WHERE name LIKE ? OR description LIKE ?";
    $search_param = "%$search%";
}

$sql .= " ORDER BY product_id DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($count_sql);
if ($search) {
    $stmt->bind_param("ss", $search_param, $search_param);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_products = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

$stmt = $conn->prepare($sql);
if ($search) {
    $stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$products = $stmt->get_result();

include 'includes/admin_header.php';
?>

<div class="admin-products">
    <div class="admin-header">
        <h1>Управление товарами</h1>
        <a href="add_product.php" class="btn btn-primary">+ Добавить товар</a>
    </div>
    
    <form method="GET" class="admin-search">
        <input type="text" name="search" placeholder="Поиск товаров..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-secondary">Найти</button>
        <?php if ($search): ?>
            <a href="products.php" class="btn-small">Сбросить</a>
        <?php endif; ?>
    </form>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Фото</th>
                <th>Название</th>
                <th>Категория</th>
                <th>Цена</th>
                <th>Остаток</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($products && $products->num_rows > 0): ?>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?= $product['product_id'] ?></td>
                        <td>
                            <img src="../assets/uploads/<?= htmlspecialchars($product['photo']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="width: 50px; height: 50px; object-fit: cover;">
                        </td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['category'] ?? '-') ?></td>
                        <td><?= number_format($product['price'], 2) ?> ₽</td>
                        <td><?= $product['stock'] ?></td>
                        <td class="actions">
                            <a href="edit_product.php?id=<?= $product['product_id'] ?>" class="btn-edit">Редакт</a>
                            <a href="delete_product.php?id=<?= $product['product_id'] ?>" class="btn-delete" onclick="return confirm('Удалить товар?')">Удалить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">Товары не найдены</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                   class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>