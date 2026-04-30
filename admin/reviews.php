<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$sql = "SELECT r.*, u.username, p.name as product_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.user_id 
        JOIN products p ON r.product_id = p.product_id";
$count_sql = "SELECT COUNT(*) as total FROM reviews";

if ($status_filter) {
    $sql .= " WHERE r.status = ?";
    $count_sql .= " WHERE status = ?";
}

$sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($count_sql);
if ($status_filter) {
    $stmt->bind_param("s", $status_filter);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_reviews = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_reviews / $limit);

$stmt = $conn->prepare($sql);
if ($status_filter) {
    $stmt->bind_param("sii", $status_filter, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$reviews = $stmt->get_result();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        $sql_update = "UPDATE reviews SET status = 'approved', updated_at = NOW() WHERE review_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $review_id);
        $stmt_update->execute();
    } elseif ($action == 'reject') {
        $sql_update = "UPDATE reviews SET status = 'rejected', updated_at = NOW() WHERE review_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $review_id);
        $stmt_update->execute();
    } elseif ($action == 'delete') {
        $sql_photos = "SELECT filename FROM review_photos WHERE review_id = ?";
        $stmt_photos = $conn->prepare($sql_photos);
        $stmt_photos->bind_param("i", $review_id);
        $stmt_photos->execute();
        $photos = $stmt_photos->get_result();
        
        while ($photo = $photos->fetch_assoc()) {
            $photo_path = '../assets/uploads/reviews/' . $photo['filename'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
        
        $sql_delete_photos = "DELETE FROM review_photos WHERE review_id = ?";
        $stmt_delete_photos = $conn->prepare($sql_delete_photos);
        $stmt_delete_photos->bind_param("i", $review_id);
        $stmt_delete_photos->execute();
        
        $sql_delete = "DELETE FROM reviews WHERE review_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $review_id);
        $stmt_delete->execute();
    }
    
    header('Location: reviews.php?status=' . $status_filter);
    exit;
}

include 'includes/admin_header.php';
?>

<div class="admin-reviews">
    <h1>Управление отзывами</h1>
    
    <div class="filters">
        <a href="?status=pending" class="btn-small <?= $status_filter == 'pending' ? 'active' : '' ?>">На модерации</a>
        <a href="?status=approved" class="btn-small <?= $status_filter == 'approved' ? 'active' : '' ?>">Одобренные</a>
        <a href="?status=rejected" class="btn-small <?= $status_filter == 'rejected' ? 'active' : '' ?>">Отклоненные</a>
        <a href="?status=all" class="btn-small <?= $status_filter == 'all' ? 'active' : '' ?>">Все</a>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Товар</th>
                <th>Пользователь</th>
                <th>Оценка</th>
                <th>Отзыв</th>
                <th>Статус</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($review = $reviews->fetch_assoc()): ?>
                <tr>
                    <td><?= $review['review_id'] ?></td>
                    <td><?= htmlspecialchars($review['product_name']) ?></td>
                    <td><?= htmlspecialchars($review['username']) ?></td>
                    <td>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= $i <= $review['rating'] ? '★' : '☆' ?>
                        <?php endfor; ?>
                    </td>
                    <td class="review-comment"><?= htmlspecialchars(mb_substr($review['comment'], 0, 50)) ?>...</td>
                    <td>
                        <span class="status-<?= $review['status'] ?>">
                            <?= $review['status'] ?>
                        </span>
                    </td>
                    <td><?= date('d.m.Y', strtotime($review['created_at'])) ?></td>
                    <td class="actions">
                        <?php if ($review['status'] == 'pending'): ?>
                            <a href="?action=approve&id=<?= $review['review_id'] ?>&status=<?= $status_filter ?>" class="btn-approve">Одобрить</a>
                            <a href="?action=reject&id=<?= $review['review_id'] ?>&status=<?= $status_filter ?>" class="btn-reject">Отклонить</a>
                        <?php endif; ?>
                        <a href="?action=delete&id=<?= $review['review_id'] ?>&status=<?= $status_filter ?>" class="btn-delete" onclick="return confirm('Удалить отзыв?')">Удалить</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&status=<?= $status_filter ?>" 
                   class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>