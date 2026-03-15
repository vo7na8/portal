<?php
if (!hasPermission($pdo, 'view_users')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db       = Database::getInstance();
$users    = $db->select('SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id=r.id ORDER BY u.full_name');
$roles    = $db->select('SELECT * FROM roles ORDER BY name');
$canAdd    = hasPermission($pdo, 'add_user');
$canEdit   = hasPermission($pdo, 'edit_user');
$canDelete = hasPermission($pdo, 'delete_user');
$myId      = (int)($_SESSION['user_id'] ?? 0);
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
            <div class="form-group"><label>Пароль (мин. 6 симв.)</label><input type="password" name="password" required minlength="6"></div>
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
<?php else: foreach ($users as $u):
    $uid = (int)$u['id'];
?>
<div class="card mb-1">
    <div class="item-row">
        <div class="flex" style="gap:.8rem;align-items:center">
            <div class="user-avatar" style="width:36px;height:36px;font-size:.95rem;flex-shrink:0">
                <?= mb_strtoupper(mb_substr($u['full_name'], 0, 1, 'UTF-8')) ?>
            </div>
            <div>
                <div style="font-weight:500"><?= e($u['full_name']) ?></div>
                <div class="text-muted" style="font-size:.82rem">@<?= e($u['username']) ?></div>
            </div>
        </div>
        <div class="item-actions">
            <span class="user-role-badge"><?= e($u['role_name'] ?? 'Без роли') ?></span>
            <?php if ($canEdit): ?>
            <button class="btn btn-secondary btn-sm" data-toggle-comments="uedit-<?= $uid ?>">
                <i class="fas fa-pen"></i>
            </button>
            <?php endif; ?>
            <?php if ($canDelete && $uid !== $myId): ?>
            <form method="post" action="handlers/delete_user.php" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $uid ?>">
                <button class="btn btn-danger btn-sm" data-confirm="Удалить пользователя <?= e($u['full_name']) ?>?">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canEdit): ?>
    <div id="uedit-<?= $uid ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border);background:var(--bg-content)">
        <div class="card-title mb-1" style="font-size:.9rem">Редактировать</div>
        <form method="post" action="handlers/edit_user.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $uid ?>">
            <div class="form-row">
                <div class="form-group"><label>Полное имя</label><input type="text" name="full_name" value="<?= e($u['full_name']) ?>" required maxlength="255"></div>
                <div class="form-group"><label>Логин</label><input type="text" name="username" value="<?= e($u['username']) ?>" required maxlength="50"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Роль</label>
                    <select name="role_id" required>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$u['role_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                            <?= e($r['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Новый пароль <span class="text-muted" style="font-weight:400;font-size:.8rem">(оставьте пустым, чтобы не менять)</span></label>
                    <input type="password" name="new_password" minlength="6" placeholder="Не изменять">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Сохранить</button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; endif; ?>
</div>
