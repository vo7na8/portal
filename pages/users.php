<?php
if (!hasPermission($pdo, 'view_users')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db    = Database::getInstance();
$users = $db->select('SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id=r.id ORDER BY u.full_name');
$roles = $db->select('SELECT * FROM roles ORDER BY name');
$canAdd = hasPermission($pdo, 'add_user');
?>
<h2 class="section-title">Пользователи</h2>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-title mb-2">Новый пользователь</div>
    <form method="post" action="handlers/add_user.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Логин</label><input type="text" name="username" required maxlength="50"></div>
            <div class="form-group"><label>Полное имя</label><input type="text" name="full_name" required maxlength="255"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Пароль</label><input type="password" name="password" required minlength="8"></div>
            <div class="form-group">
                <label>Роль</label>
                <select name="role_id" required>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Создать</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
<?php if (empty($users)): ?>
    <div class="empty-state"><i class="fas fa-users"></i><p>Пользователей нет</p></div>
<?php else: foreach ($users as $u): ?>
    <div class="item-row">
        <div class="flex">
            <div class="user-avatar" style="width:32px;height:32px;font-size:.9rem"><?= mb_strtoupper(mb_substr(e($u['full_name']),0,1)) ?></div>
            <div>
                <div style="font-weight:500"><?= e($u['full_name']) ?></div>
                <div class="text-muted" style="font-size:.82rem">@<?= e($u['username']) ?></div>
            </div>
        </div>
        <span class="user-role-badge"><?= e($u['role_name'] ?? 'Без роли') ?></span>
    </div>
<?php endforeach; endif; ?>
</div>
