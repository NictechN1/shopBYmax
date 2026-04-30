<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM products WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM products WHERE 1=1";
$params = [];
$types = "";

if ($category_filter) {
    $sql .= " AND category = ?";
    $count_sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $count_sql .= " AND (name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$products = $stmt->get_result();

$sql_cats = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category";
$cats_result = $conn->query($sql_cats);
$categories = [];
if ($cats_result && $cats_result->num_rows > 0) {
    while ($row = $cats_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
?>

<section class="catalog-section">
    <div class="container">
        <h1 class="page-title">Каталог аудиотехники</h1>
        
        <!-- Блок поиска и фильтров -->
        <div class="catalog-header">
            <div class="search-box">
                <form method="GET" class="search-form">
                    <input type="text" name="search" class="search-input" 
                           placeholder="Поиск по названию или описанию..." 
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">🔍 Найти</button>
                </form>
            </div>
            
            <div class="categories-filter">
                <span class="filter-label">Категории:</span>
                <div class="filter-buttons">
                    <a href="products.php" class="filter-tag <?= !$category_filter ? 'active' : '' ?>">Все товары</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="?category=<?= urlencode($cat) ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="filter-tag <?= $category_filter == $cat ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if ($search || $category_filter): ?>
                <div class="active-filters">
                    <span>Активные фильтры:</span>
                    <?php if ($search): ?>
                        <span class="filter-badge">Поиск: "<?= htmlspecialchars($search) ?>" 
                            <a href="?<?= $category_filter ? 'category=' . urlencode($category_filter) : '' ?>" class="filter-remove">✕</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($category_filter): ?>
                        <span class="filter-badge">Категория: <?= htmlspecialchars($category_filter) ?>
                            <a href="?<?= $search ? 'search=' . urlencode($search) : '' ?>" class="filter-remove">✕</a>
                        </span>
                    <?php endif; ?>
                    <a href="products.php" class="reset-filters">Сбросить все</a>
                </div>
            <?php endif; ?>
            
            <div class="catalog-stats">
                Найдено товаров: <strong><?= $total_rows ?></strong>
            </div>
        </div>
        
        <?php if ($products && $products->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <a href="product.php?id=<?= $product['product_id'] ?>" class="product-link">
                            <div class="product-image-wrapper">
                                <?php if (filter_var($product['photo'], FILTER_VALIDATE_URL)): ?>
                                    <img src="<?= htmlspecialchars($product['photo']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <img src="assets/uploads/<?= htmlspecialchars($product['photo']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                         onerror="this.src='assets/uploads/default.jpg'">
                                <?php endif; ?>
                            </div>
                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price"><?= number_format($product['price'], 2) ?> ₽</div>
                            <?php if ($product['stock'] > 0): ?>
                                <div class="product-stock in-stock">✓ В наличии (<?= $product['stock'] ?> шт.)</div>
                            <?php else: ?>
                                <div class="product-stock out-of-stock">✗ Нет в наличии</div>
                            <?php endif; ?>
                        </a>
                        <?php if ($product['stock'] > 0): ?>
                            <button class="btn-add-cart" data-product-id="<?= $product['product_id'] ?>">🛒 В корзину</button>
                        <?php else: ?>
                            <button class="btn-add-cart disabled" disabled>Нет в наличии</button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">← Назад</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                           class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">Вперед →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-catalog">
                <div class="empty-icon">🔍</div>
                <h3>Ничего не найдено</h3>
                <p>Попробуйте изменить параметры поиска или выберите другую категорию</p>
                <a href="products.php" class="btn btn-primary">Сбросить фильтры</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.page-title {
    font-size: 36px;
    margin: 20px 0 30px;
    color: #16213e;
    text-align: center;
}

.catalog-header {
    background: white;
    padding: 24px;
    border-radius: 20px;
    margin-bottom: 32px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.search-form {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.search-input {
    flex: 1;
    padding: 14px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 50px;
    font-size: 16px;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #e94560;
}

.search-btn {
    padding: 14px 32px;
    background: #e94560;
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.search-btn:hover {
    background: #c62a47;
    transform: translateY(-1px);
}

.categories-filter {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.filter-label {
    font-weight: 600;
    color: #4a5568;
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.filter-tag {
    padding: 8px 20px;
    background: #f7fafc;
    color: #4a5568;
    text-decoration: none;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
}

.filter-tag:hover {
    background: #e94560;
    color: white;
    border-color: #e94560;
}

.filter-tag.active {
    background: #e94560;
    color: white;
    border-color: #e94560;
}

.active-filters {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 0;
    border-top: 1px solid #e2e8f0;
    margin-top: 16px;
}

.active-filters span:first-child {
    color: #4a5568;
    font-size: 14px;
}

.filter-badge {
    background: #f0f2f5;
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.filter-remove {
    color: #e53e3e;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
}

.reset-filters {
    color: #e94560;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.catalog-stats {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    color: #4a5568;
    font-size: 14px;
}

.product-link {
    text-decoration: none;
    display: block;
}

.product-image-wrapper {
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.product-image-wrapper img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}

.product-title {
    font-size: 16px;
    font-weight: 600;
    color: #16213e;
    margin-bottom: 8px;
}

.product-price {
    font-size: 22px;
    font-weight: 700;
    color: #e94560;
    margin: 8px 0;
}

.product-stock {
    font-size: 13px;
    margin: 8px 0;
}

.in-stock {
    color: #38a169;
}

.out-of-stock {
    color: #e53e3e;
}

.btn-add-cart {
    width: 100%;
    margin-top: 16px;
    padding: 12px;
    background: #16213e;
    color: white;
    border: none;
    border-radius: 40px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-add-cart:hover:not(.disabled) {
    background: #e94560;
    transform: translateY(-2px);
}

.btn-add-cart.disabled {
    background: #cbd5e0;
    cursor: not-allowed;
}

.empty-catalog {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-catalog h3 {
    font-size: 24px;
    margin-bottom: 8px;
    color: #16213e;
}

.empty-catalog p {
    color: #4a5568;
    margin-bottom: 24px;
}
</style>

<?php include 'includes/footer.php'; ?>