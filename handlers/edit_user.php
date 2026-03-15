<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_user')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=users'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=users'); }

$db        = Database::getInstance();
$fullName  = trim($_POST['full_name']   ?? '');
$username  = trim($_POST['username']    ?? '');
$roleId    = (int)($_POST['role_id']    ?? 0);
$newPass   = trim($_POST['new_password'] ?? '');

if ($fullName === '' || $username === '' || $roleId <= 0) {
    flash('error', 'Заполните все обязательные поля.');
    redirect('../main.php?page=users');
}
// Проверка уникальности username
$exists = $db->selectValue('SELECT COUNT(*) FROM users WHERE username = ? AND id != ?', [$username, $id]);
if ($exists) { flash('error', 'Пользователь с таким логином уже существует.'); redirect('../main.php?page=users'); }

$data = [
    'full_name' => $fullName,
    'username'  => $username,
    'role_id'   => $roleId,
];
if ($newPass !== '') {
    if (strlen($newPass) < 6) { flash('error', 'Пароль должен быть не менее 6 символов.'); redirect('../main.php?page=users'); }
    $data['password_hash'] = $security->hashPassword($newPass);
}
$db->update('users', $data, 'id = ?', [$id]);
flash('success', 'Пользователь обновлён.');
redirect('../main.php?page=users');
