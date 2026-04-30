<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $update_fields = [];
    $params = [];
    $types = "";
    
    if ($email != $user['email']) {
        $update_fields[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }
    
    $update_fields[] = "full_name = ?";
    $params[] = $full_name;
    $types .= "s";
    
    $update_fields[] = "phone = ?";
    $params[] = $phone;
    $types .= "s";
    
    $update_fields[] = "address = ?";
    $params[] = $address;
    $types .= "s";
    
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $error = "Введите текущий пароль";
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = "Неверный текущий пароль";
        } elseif (strlen($new_password) < 6) {
            $error = "Новый пароль должен быть не менее 6 символов";
        } elseif ($new_password !== $confirm_password) {
            $error = "Пароли не совпадают";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password = ?";
            $params[] = $hashed_password;
            $types .= "s";
        }
    }
    
    if (empty($error)) {
        $params[] = $user_id;
        $types .= "i";
        $sql_update = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param($types, ...$params);
        
        if ($stmt_update->execute()) {
            $success = "Профиль обновлен";
            $sql = "SELECT * FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Ошибка обновления";
        }
    }
}

include 'includes/header.php';
?>

<section class="profile-section">
    <div class="container">
        <h1>Редактирование профиля</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="edit-profile-form">
            <form method="POST">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>ФИО</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Адрес</label>
                    <textarea name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <hr>
                <h3>Смена пароля</h3>
                
                <div class="form-group">
                    <label>Текущий пароль</label>
                    <input type="password" name="current_password">
                </div>
                
                <div class="form-group">
                    <label>Новый пароль</label>
                    <input type="password" name="new_password">
                </div>
                
                <div class="form-group">
                    <label>Подтверждение пароля</label>
                    <input type="password" name="confirm_password">
                </div>
                
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="profile.php" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>