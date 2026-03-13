<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'manage_roles')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=roles'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный запрос.'); redirect('main.php?page=roles'); }
$usersWithRole = Database::getInstance()->selectValue('SELECT COUNT(*) FROM users WHERE role_id = ?', [$id]);
if ($usersWithRole > 0) {
    flash('error', 'Нельзя удалить роль: есть пользователи с этой ролью.');
} else {
    Database::getInstance()->delete('role_permissions', 'role_id = ?', [$id]);
    Database::getInstance()->delete('roles', 'id = ?', [$id]);
    flash('success', 'Роль удалена.');
}
redirect('main.php?page=roles');
