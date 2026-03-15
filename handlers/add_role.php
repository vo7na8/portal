<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'manage_roles')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=roles'); }
$security->requireCsrf();
$name = trim($_POST['name'] ?? '');
if ($name === '') { flash('error', 'Название роли обязательно.'); redirect('../main.php?page=roles'); }
$exists = Database::getInstance()->selectValue('SELECT COUNT(*) FROM roles WHERE name = ?', [$name]);
if ($exists) { flash('error', 'Роль с таким названием уже существует.'); redirect('../main.php?page=roles'); }
Database::getInstance()->insert('roles', ['name' => $name]);
flash('success', 'Роль создана.');
redirect('../main.php?page=roles');
