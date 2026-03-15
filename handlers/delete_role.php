<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'manage_roles')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=roles'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=roles'); }
$userCount = (int)Database::getInstance()->selectValue('SELECT COUNT(*) FROM users WHERE role_id = ?', [$id]);
if ($userCount > 0) { flash('error', 'Нельзя удалить роль с пользователями.'); redirect('../main.php?page=roles'); }
Database::getInstance()->delete('roles', 'id = ?', [$id]);
flash('success', 'Роль удалена.');
redirect('../main.php?page=roles');
