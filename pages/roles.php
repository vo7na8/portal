<?php
if (!hasPermission($pdo, 'manage_roles')) {
    echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>';
    return;
}
$db = Database::getInstance();
$roles = $db->select('
    SELECT r.*, (SELECT COUNT(*) FROM users WHERE role_id=r.id) user_count
    FROM roles r ORDER BY r.id
');
$permissions = $db->select('SELECT * FROM permissions ORDER BY name');
$rolePerms   = [];
foreach ($roles as $r) {
    $rolePerms[$r['id']] = array_column(
        $db->select('SELECT permission_id FROM role_permissions WHERE role_id=?', [$r['id']]),
        'permission_id'
    );
}
$permGroups = [];
foreach ($permissions as $p) {
    $prefix = explode('_', $p['name'])[0];
    $permGroups[$prefix][] = $p;
}
$groupLabels = [
    'view'     => 'Просмотр',
    'add'      => 'Добавление',
    'edit'     => 'Редактирование',
    'delete'   => 'Удаление',
    'take'     => 'Взять в работу',
    'complete' => 'Завершение',
    'reassign' => 'Переназначение',
    'upload'   => 'Загрузка',
    'manage'   => 'Управление',
];
?>
<h2 class="section-title">Роли и права</h2>

<div class="form-container">
    <div class="card-title mb-2">Создать роль</div>
    <form method="post" action="handlers/add_role.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Название</label><input type="text" name="name" required maxlength="100"></div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Создать</button>
    </form>
</div>

<?php foreach ($roles as $role):
    $rid = (int)$role['id'];
?>
<div class="card mt-2">
    <div class="card-header">
        <div>
            <span class="card-title"><?= e($role['name']) ?></span>
            <span class="badge badge-new" style="margin-left:.6rem">
                <i class="fas fa-users" style="font-size:.7rem"></i> <?= (int)$role['user_count'] ?>
            </span>
        </div>
        <?php if (strtolower($role['name']) !== 'администратор'): ?>
        <form method="post" action="handlers/delete_role.php" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $rid ?>">
            <button class="btn btn-danger btn-sm" data-confirm="Удалить роль?"
                    <?= $role['user_count'] > 0 ? 'disabled title="Есть пользователи"' : '' ?>>
                <i class="fas fa-trash"></i>
            </button>
        </form>
        <?php endif; ?>
    </div>

    <form method="post" action="handlers/update_role_permissions.php">
        <?= csrf_field() ?>
        <input type="hidden" name="role_id" value="<?= $rid ?>">
        <?php foreach ($permGroups as $prefix => $perms): ?>
        <div style="margin-bottom:.8rem">
            <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">
                <?= e($groupLabels[$prefix] ?? $prefix) ?>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
            <?php foreach ($perms as $p):
                $checked = in_array($p['id'], $rolePerms[$rid] ?? [], false);
                $short   = preg_replace('/^' . preg_quote($prefix, '/') . '_/', '', $p['name']);
            ?>
                <label style="display:inline-flex;align-items:center;gap:.35rem;background:var(--bg-content);border:1px solid var(--border);border-radius:var(--radius-md);padding:.25rem .7rem;font-size:.82rem;cursor:pointer;transition:border-color .15s"
                    onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                    <input type="checkbox" name="permissions[]" value="<?= (int)$p['id'] ?>" <?= $checked ? 'checked' : '' ?>
                        style="accent-color:var(--accent);width:14px;height:14px">
                    <?= e($short) ?>
                </label>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-secondary btn-sm mt-1">
            <i class="fas fa-floppy-disk"></i> Сохранить права
        </button>
    </form>
</div>
<?php endforeach; ?>
