<?php
require_once __DIR__ . '/../config.php';
if (!hasPermission($pdo, 'manage_roles')) {
    echo "<p>Доступ запрещён.</p>";
    return;
}

// Получение списка ролей с количеством пользователей
$roles = $pdo->query("
    SELECT r.*, (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count
    FROM roles r
    ORDER BY r.id
")->fetchAll();

// Получение всех разрешений
$permissions = $pdo->query("SELECT * FROM permissions ORDER BY id")->fetchAll();

// Для каждой роли получим её разрешения
$role_perms = [];
foreach ($roles as $r) {
    $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$r['id']]);
    $role_perms[$r['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<h2 class="section-title">Управление ролями и правами</h2>

<div class="form-container">
    <h3>Создать новую роль</h3>
    <form action="handlers/add_role.php" method="post">
        <div class="form-group">
            <label>Название роли</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="2"></textarea>
        </div>
        <button type="submit" class="btn-primary">Создать</button>
    </form>
</div>

<div class="item-list">
    <?php foreach ($roles as $role): ?>
    <div class="item-row" style="flex-direction: column; align-items: start;">
        <div style="display: flex; justify-content: space-between; width: 100%;">
            <div>
                <strong><?= htmlspecialchars($role['name']) ?></strong><br>
                <small><?= htmlspecialchars($role['description']) ?></small><br>
                <small>Пользователей: <?= $role['user_count'] ?></small>
            </div>
            <?php if ($role['name'] !== 'admin'): // защита от удаления базовых ролей ?>
                <a href="handlers/delete_role.php?id=<?= $role['id'] ?>" class="btn-delete delete-confirm" onclick="return confirm('Удалить роль? Пользователи этой роли останутся без роли!')"><i class="fas fa-trash"></i></a>
            <?php endif; ?>
        </div>
        <div style="margin-top: 10px; width: 100%;">
            <h4>Разрешения:</h4>
            <form action="handlers/update_role_permissions.php" method="post">
                <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 5px;">
                    <?php foreach ($permissions as $perm): ?>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="perms[]" value="<?= $perm['id'] ?>" 
                                <?= in_array($perm['id'], $role_perms[$role['id']] ?? []) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($perm['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 10px;">Сохранить права</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>