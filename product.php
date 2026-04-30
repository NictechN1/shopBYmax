<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
include 'includes/header.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    redirect('products.php');
}

$product = getProductById($conn, $product_id);
if (!$product) {
    redirect('products.php');
}

$rating_data = getProductRating($conn, $product_id);
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$reviews_count = $rating_data['total'] ?? 0;
$reviews = getProductReviews($conn, $product_id);

$can_review = false;
$has_reviewed = false;
if (isLoggedIn()) {
    $has_reviewed = userHasReviewed($conn, $_SESSION['user_id'], $product_id);
    $can_review = !$has_reviewed && $product['stock'] > 0;
}

$review_success = $_SESSION['review_success'] ?? null;
$review_error = $_SESSION['review_error'] ?? null;
unset($_SESSION['review_success'], $_SESSION['review_error']);
?>

<div class="container">
    <div class="product-detail">
        <div class="product-image">
            <?php if (filter_var($product['photo'], FILTER_VALIDATE_URL)): ?>
                <img src="<?= htmlspecialchars($product['photo']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <img src="assets/uploads/<?= htmlspecialchars($product['photo']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     onerror="this.src='assets/uploads/default.jpg'">
            <?php endif; ?>
        </div>
        <div class="product-info">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            
            <div class="product-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= $i <= $avg_rating ? 'filled' : '' ?>">★</span>
                <?php endfor; ?>
                <span>(<?= $reviews_count ?> отзывов)</span>
            </div>
            
            <div class="product-price"><?= formatPrice($product['price']) ?></div>
            
            <div class="product-stock <?= $product['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                <?= $product['stock'] > 0 ? '✓ В наличии (' . $product['stock'] . ' шт.)' : '✗ Нет в наличии' ?>
            </div>
            
            <div class="product-description">
                <h3>Описание</h3>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>
            
            <?php if ($product['stock'] > 0): ?>
                <div class="product-actions">
                    <div class="quantity-selector">
                        <label>Количество:</label>
                        <input type="number" id="product-quantity" value="1" min="1" max="<?= $product['stock'] ?>">
                    </div>
                    <button class="btn-add-cart-detail btn-add-to-cart" 
                            data-product-id="<?= $product['product_id'] ?>"
                            data-quantity-id="product-quantity">
                        🛒 Добавить в корзину
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Отзывы -->
    <div class="reviews-section">
        <h2>Отзывы покупателей</h2>
        
        <?php if ($review_success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($review_success) ?></div>
        <?php endif; ?>
        
        <?php if ($review_error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($review_error) ?></div>
        <?php endif; ?>
        
        <?php if (isLoggedIn() && $can_review): ?>
            <div class="review-form-container">
                <h3>Оставить отзыв</h3>
                <form method="POST" action="review_form.php" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                    <div class="form-group">
                        <label>Оценка</label>
                        <select name="rating" required>
                            <option value="5">5 - Отлично</option>
                            <option value="4">4 - Хорошо</option>
                            <option value="3">3 - Средне</option>
                            <option value="2">2 - Плохо</option>
                            <option value="1">1 - Ужасно</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Комментарий</label>
                        <textarea name="comment" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Фото (необязательно)</label>
                        <input type="file" name="review_photo" accept="image/*">
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-primary">Отправить отзыв</button>
                </form>
            </div>
        <?php elseif (isLoggedIn() && $has_reviewed): ?>
            <div class="alert alert-info">Вы уже оставили отзыв на этот товар</div>
        <?php elseif (!isLoggedIn()): ?>
            <div class="alert alert-info">
                <a href="login.php?redirect=product.php?id=<?= $product['product_id'] ?>">Войдите</a>, чтобы оставить отзыв
            </div>
        <?php endif; ?>
        
        <?php if ($reviews->num_rows > 0): ?>
            <div class="reviews-list">
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <strong><?= htmlspecialchars($review['username']) ?></strong>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="review-date"><?= formatDate($review['created_at']) ?></span>
                        </div>
                        <div class="review-comment">
                            <?= nl2br(htmlspecialchars($review['comment'])) ?>
                        </div>
                        <?php
                        $sql_photos = "SELECT filename FROM review_photos WHERE review_id = ?";
                        $stmt_photos = $conn->prepare($sql_photos);
                        $stmt_photos->bind_param("i", $review['review_id']);
                        $stmt_photos->execute();
                        $photos = $stmt_photos->get_result();
                        if ($photos->num_rows > 0):
                        ?>
                            <div class="review-photos">
                                <?php while ($photo = $photos->fetch_assoc()): ?>
                                    <img src="assets/uploads/reviews/<?= htmlspecialchars($photo['filename']) ?>" 
                                         class="review-photo" alt="Фото к отзыву">
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Пока нет отзывов. Будьте первым!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>