<?php
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Заполните обязательные поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Неверный формат email';
    } elseif (strlen($username) < 3) {
        $error = 'Логин должен быть не менее 3 символов';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        // Сначала проверяем существование пользователя
        $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Пользователь с таким логином или email уже существует';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, email, password, full_name, phone, user_role) VALUES (?, ?, ?, ?, ?, 'user')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $phone);
            
            if ($stmt->execute()) {
                $success = 'Регистрация успешна! Теперь вы можете войти';
            } else {
                $error = 'Ошибка регистрации. Попробуйте позже.';
            }
        }
    }
}

include 'includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-form">
            <h1>Регистрация</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <div class="auth-links">
                    <p><a href="login.php">Перейти к входу</a></p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Логин *</label>
                        <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Пароль *</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Подтверждение пароля *</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>ФИО</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
                </form>
                
                <div class="auth-links">
                    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>