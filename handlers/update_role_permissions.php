<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'manage_roles')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=roles'); }
$security->requireCsrf();
$role_id = (int)($_POST['role_id'] ?? 0);
if ($role_id <= 0) { flash('error', 'Неверный запрос.'); redirect('../main.php?page=roles'); }
$permissions = array_map('intval', (array)($_POST['permissions'] ?? []));
Database::getInstance()->transaction(function ($db) use ($role_id, $permissions) {
    $db->delete('role_permissions', 'role_id = ?', [$role_id]);
    foreach ($permissions as $perm_id) {
        if ($perm_id > 0) {
            $db->insert('role_permissions', ['role_id' => $role_id, 'permission_id' => $perm_id]);
        }
    }
});
clearPermissionsCache();
flash('success', 'Права роли обновлены.');
redirect('../main.php?page=roles');
