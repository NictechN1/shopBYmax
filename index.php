<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

// Получаем последние 8 товаров
$sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT 8";
$result = $conn->query($sql);
$new_products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $new_products[] = $row;
    }
}

// Получаем все категории из таблицы categories
$sql_cats = "SELECT * FROM categories ORDER BY name";
$cats_result = $conn->query($sql_cats);
$categories = [];
if ($cats_result && $cats_result->num_rows > 0) {
    while ($row = $cats_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<div class="hero">
    <div class="container">
        <h1>🎧 shopBYmax</h1>
        <p>Аудиотехника для настоящих ценителей звука</p>
        <a href="products.php" class="btn btn-primary">В каталог →</a>
    </div>
</div>

<div class="container">
    <!-- Категории -->
    <section>
        <h2>Категории</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <a href="products.php?category=<?= urlencode($category['name']) ?>" class="category-card">
                    <h3><?= htmlspecialchars($category['name']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Новинки -->
    <section>
        <h2> Новинки</h2>
        <div class="products-grid">
            <?php foreach ($new_products as $product): ?>
                <div class="product-card">
                    <a href="product.php?id=<?= $product['product_id'] ?>" class="product-link">
                        <div class="product-image-wrapper">
                            <?php if (filter_var($product['photo'], FILTER_VALIDATE_URL)): ?>
                                <img src="<?= htmlspecialchars($product['photo']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <img src="assets/uploads/<?= htmlspecialchars($product['photo']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     onerror="this.src='assets/uploads/default.jpg'"
                                     loading="lazy">
                            <?php endif; ?>
                        </div>
                        <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                        <div class="product-price"><?= formatPrice($product['price']) ?></div>
                        <div class="product-stock <?= $product['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                            <?= $product['stock'] > 0 ? '✓ В наличии' : '✗ Нет в наличии' ?>
                        </div>
                    </a>
                    <?php if ($product['stock'] > 0): ?>
                        <button class="btn-add-cart" data-product-id="<?= $product['product_id'] ?>">
                            🛒 В корзину
                        </button>
                    <?php else: ?>
                        <button class="btn-add-cart disabled" disabled>
                            ❌ Нет в наличии
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Преимущества -->
    <section>
        <div class="advantages-grid">
            <div class="advantage-card">
                <div class="advantage-icon">🚚</div>
                <h3>Быстрая доставка</h3>
                <p>По всей России за 3-5 дней</p>
            </div>
            <div class="advantage-card">
                <div class="advantage-icon">🛡️</div>
                <h3>Гарантия качества</h3>
                <p>Оригинальная продукция</p>
            </div>
            <div class="advantage-card">
                <div class="advantage-icon">🔄</div>
                <h3>Возврат 14 дней</h3>
                <p>Если не подошло</p>
            </div>
            <div class="advantage-card">
                <div class="advantage-icon">💳</div>
                <h3>Удобная оплата</h3>
                <p>Карта, наличные, СБП</p>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>