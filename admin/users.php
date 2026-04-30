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

$sql = "SELECT * FROM users";
$count_sql = "SELECT COUNT(*) as total FROM users";

if ($search) {
    $sql .= " WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?";
    $count_sql .= " WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?";
    $search_param = "%$search%";
}

$sql .= " ORDER BY user_id DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($count_sql);
if ($search) {
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_users = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

$stmt = $conn->prepare($sql);
if ($search) {
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$users = $stmt->get_result();

if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    if ($user_id != $_SESSION['user_id']) {
        $sql_delete = "DELETE FROM users WHERE user_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $user_id);
        $stmt_delete->execute();
    }
    header('Location: users.php');
    exit;
}

if (isset($_GET['role']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $role = $_GET['role'];
    if (in_array($role, ['admin', 'manager', 'user'])) {
        $sql_update = "UPDATE users SET user_role = ? WHERE user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $role, $user_id);
        $stmt_update->execute();
    }
    header('Location: users.php');
    exit;
}

include 'includes/admin_header.php';
?>

<div class="admin-users">
    <h1>Управление пользователями</h1>
    
    <form method="GET" class="admin-search">
        <input type="text" name="search" placeholder="Поиск по логину, email, ФИО..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-secondary">Найти</button>
        <?php if ($search): ?>
            <a href="users.php" class="btn-small">Сбросить</a>
        <?php endif; ?>
    </form>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Логин</th>
                <th>Email</th>
                <th>ФИО</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['user_id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['full_name'] ?? '-') ?></td>
                    <td>
                        <form method="GET" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $user['user_id'] ?>">
                            <select name="role" onchange="this.form.submit()" <?= $user['user_id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <option value="user" <?= $user['user_role'] == 'user' ? 'selected' : '' ?>>Пользователь</option>
                                <option value="manager" <?= $user['user_role'] == 'manager' ? 'selected' : '' ?>>Менеджер</option>
                                <option value="admin" <?= $user['user_role'] == 'admin' ? 'selected' : '' ?>>Админ</option>
                            </select>
                        </form>
                    </td>
                    <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                            <a href="?delete=<?= $user['user_id'] ?>" class="btn-delete" onclick="return confirm('Удалить пользователя?')">Удалить</a>
                        <?php else: ?>
                            <span class="text-muted">Текущий</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
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