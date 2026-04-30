<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $slug = strtolower(trim(str_replace(' ', '-', $name)));
    
    if (empty($name)) {
        $error = 'Введите название категории';
    } else {
        $sql = "INSERT INTO categories (name, slug) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $name, $slug);
        
        if ($stmt->execute()) {
            $success = 'Категория добавлена';
        } else {
            $error = 'Категория уже существует';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM categories WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: categories.php');
    exit;
}

$sql = "SELECT * FROM categories ORDER BY name";
$categories = $conn->query($sql);

include 'includes/admin_header.php';
?>

<div class="admin-categories">
    <h1>Управление категориями</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="categories-form">
        <h2>Добавить категорию</h2>
        <form method="POST">
            <div class="form-row">
                <input type="text" name="name" placeholder="Название категории" required>
                <button type="submit" name="add_category" class="btn btn-primary">Добавить</button>
            </div>
        </form>
    </div>
    
    <div class="categories-list">
        <h2>Существующие категории</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Slug</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><?= $cat['category_id'] ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars($cat['slug']) ?></td>
                        <td>
                            <a href="?delete=<?= $cat['category_id'] ?>" class="btn-delete" onclick="return confirm('Удалить категорию?')">Удалить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>