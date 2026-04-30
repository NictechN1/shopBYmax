<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT * FROM products WHERE product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit;
}

$sql_cats = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL UNION SELECT name FROM categories";
$cats_result = $conn->query($sql_cats);
$categories = [];
if ($cats_result) {
    while ($row = $cats_result->fetch_assoc()) {
        $cat = $row['category'] ?? $row['name'];
        if ($cat) $categories[] = $cat;
    }
}
$categories = array_unique($categories);
sort($categories);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category = trim($_POST['category']);
    $photo_url = trim($_POST['photo_url']);
    
    if (empty($name) || $price <= 0) {
        $error = 'Заполните обязательные поля';
    } else {
        $photo = $product['photo']; // по умолчанию оставляем старое
        
        // ПРИОРИТЕТ 1: Загруженный файл
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0 && $_FILES['photo']['size'] > 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_dir = '../assets/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = time() . '_' . uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                    // Удаляем старое фото, если оно загружалось (не ссылка)
                    if ($product['photo'] != 'default.jpg' && !filter_var($product['photo'], FILTER_VALIDATE_URL)) {
                        $old_path = $upload_dir . $product['photo'];
                        if (file_exists($old_path)) {
                            unlink($old_path);
                        }
                    }
                    $photo = $new_filename;
                } else {
                    $error = 'Ошибка загрузки фото';
                }
            } else {
                $error = 'Неверный формат фото. Разрешены: JPG, JPEG, PNG, GIF, WEBP';
            }
        } 
        // ПРИОРИТЕТ 2: Ссылка на фото (если файл не загружен)
        elseif (!empty($photo_url)) {
            if (filter_var($photo_url, FILTER_VALIDATE_URL)) {
                // Если старое фото было загружено (не ссылка) - удаляем
                if ($product['photo'] != 'default.jpg' && !filter_var($product['photo'], FILTER_VALIDATE_URL)) {
                    $old_path = '../assets/uploads/' . $product['photo'];
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                $photo = $photo_url;
            } else {
                $error = 'Неверный формат ссылки на фото';
            }
        }
        
        if (empty($error)) {
            $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, photo = ? WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $category, $photo, $product_id);
            
            if ($stmt->execute()) {
                $success = 'Товар успешно обновлен!';
                // Обновляем данные для отображения
                $product['name'] = $name;
                $product['description'] = $description;
                $product['price'] = $price;
                $product['stock'] = $stock;
                $product['category'] = $category;
                $product['photo'] = $photo;
            } else {
                $error = 'Ошибка обновления: ' . $conn->error;
            }
        }
    }
}

include 'includes/admin_header.php';
?>

<div class="admin-form">
    <h1>✏️ Редактирование товара</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Название товара *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($product['name']) ?>">
        </div>
        
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="5"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Цена *</label>
                <input type="number" name="price" step="0.01" required value="<?= $product['price'] ?>">
            </div>
            
            <div class="form-group">
                <label>Количество на складе</label>
                <input type="number" name="stock" value="<?= $product['stock'] ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Категория</label>
            <input type="text" name="category" list="categories" value="<?= htmlspecialchars($product['category'] ?? '') ?>">
            <datalist id="categories">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        
        <div class="form-section">
            <h3>📸 Фото товара</h3>
            
            <!-- Текущее фото -->
            <div class="form-group">
                <label>🖼️ Текущее фото</label>
                <div class="current-photo">
                    <?php if (filter_var($product['photo'], FILTER_VALIDATE_URL)): ?>
                        <img src="<?= htmlspecialchars($product['photo']) ?>" style="max-width: 150px; border-radius: 12px;">
                        <p><small>🔗 Внешняя ссылка</small></p>
                    <?php else: ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($product['photo']) ?>" style="max-width: 150px; border-radius: 12px;">
                        <p><small>📁 Локальный файл</small></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group" style="text-align: center; color: #718096;">
                <span>▼ Выберите способ замены фото ▼</span>
            </div>
            
            <!-- Способ 1: Загрузка файла -->
            <div class="form-group">
                <label>📁 Способ 1: Загрузить новый файл</label>
                <input type="file" name="photo" id="file-input" accept="image/*">
                <small class="text-muted">Оставьте пустым, чтобы не менять фото</small>
            </div>
            
            <div class="form-group" style="text-align: center; color: #718096;">
                <span>— или —</span>
            </div>
            
            <!-- Способ 2: Ссылка на фото -->
            <div class="form-group">
                <label>🔗 Способ 2: Вставить новую ссылку на фото (URL)</label>
                <input type="text" name="photo_url" id="url-input" placeholder="https://example.com/image.jpg">
                <small class="text-muted">Введите ссылку, чтобы заменить текущее фото на внешнее</small>
            </div>
            
            <!-- Блок предпросмотра -->
            <div class="form-group" id="preview-block" style="display: none;">
                <label>👁️ Предпросмотр нового фото:</label>
                <div style="margin-top: 8px;">
                    <img id="preview-image" src="#" alt="Предпросмотр" style="max-width: 200px; max-height: 200px; border-radius: 12px; border: 1px solid #e2e8f0; padding: 8px;">
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn btn-primary">💾 Сохранить изменения</button>
            <a href="products.php" class="btn btn-secondary">❌ Отмена</a>
        </div>
    </form>
</div>

<script>
// Функция для показа предпросмотра
function showPreview(imageUrl) {
    const previewBlock = document.getElementById('preview-block');
    const previewImage = document.getElementById('preview-image');
    
    if (imageUrl) {
        previewImage.src = imageUrl;
        previewBlock.style.display = 'block';
    } else {
        previewBlock.style.display = 'none';
    }
}

// Предпросмотр при вводе URL
const urlInput = document.getElementById('url-input');
urlInput.addEventListener('input', function() {
    const url = this.value.trim();
    if (url && (url.startsWith('http://') || url.startsWith('https://'))) {
        showPreview(url);
        document.getElementById('file-input').value = '';
    } else if (!url) {
        showPreview('');
    }
});

// Предпросмотр при выборе файла
const fileInput = document.getElementById('file-input');
fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            showPreview(e.target.result);
            urlInput.value = '';
        }
        reader.readAsDataURL(file);
    } else {
        showPreview('');
    }
});
</script>

<style>
.form-section {
    background: #f8fafc;
    padding: 24px;
    border-radius: 20px;
    margin: 24px 0;
    border: 1px solid #e2e8f0;
}
.text-muted {
    color: #718096;
    font-size: 12px;
    display: block;
    margin-top: 4px;
}
.current-photo {
    background: white;
    padding: 12px;
    border-radius: 12px;
    display: inline-block;
    text-align: center;
}
</style>

<?php include 'includes/admin_footer.php'; ?>