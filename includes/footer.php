</main>
<footer>
    <div class="container">
        <p>© <?= date('Y') ?> shopBYmax. Все права защищены.</p>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
            <div class="admin-footer-link">
                <a href="admin/dashboard.php">⚙️ Управление</a>
            </div>
        <?php endif; ?>
    </div>
</footer>
</body>
</html>