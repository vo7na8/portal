<?php
require_once __DIR__ . '/../config.php';
if (!hasPermission($pdo, 'view_users')) {
    echo "<p>Доступ запрещён.</p>";
    return;
}
$users = $pdo->query("SELECT id, username, full_name, role_id FROM users")->fetchAll();
?>
<h2 class="section-title">Пользователи</h2>
<?php if (hasPermission($pdo, 'add_user')): ?>
<div class="form-container">
    <h3>Добавить пользователя</h3>
    <form action="handlers/add_user.php" method="post">
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Полное имя</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Роль</label>
            <select name="role_id">
                <?php
                $roles = $pdo->query("SELECT id, name FROM roles")->fetchAll();
                foreach ($roles as $r) {
                    echo "<option value=\"{$r['id']}\">{$r['name']}</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn-primary">Добавить</button>
    </form>
</div>
<?php endif; ?>
<div class="item-list">
    <?php foreach ($users as $u): ?>
    <div class="item-row">
        <div><?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['username']) ?>) — роль: <?= $u['role_id'] ?></div>
    </div>
    <?php endforeach; ?>
</div>