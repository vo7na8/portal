<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_user')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=users'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate([
    'username'  => 'required|min:3|max:50',
    'full_name' => 'required|max:255',
    'password'  => 'required|min:6',
    'role_id'   => 'required|integer',
])) {
    flash('error', $v->firstErrorMessage());
    redirect('../main.php?page=users');
}
$d = $v->validated();
$exists = Database::getInstance()->selectValue('SELECT COUNT(*) FROM users WHERE username = ?', [$d['username']]);
if ($exists) {
    flash('error', 'Пользователь с таким логином уже существует.');
    redirect('../main.php?page=users');
}
Database::getInstance()->insert('users', [
    'username'      => $d['username'],
    'full_name'     => $d['full_name'],
    'password_hash' => $security->hashPassword($_POST['password']),
    'role_id'       => (int)$d['role_id'],
]);
flash('success', 'Пользователь ' . $d['full_name'] . ' создан.');
redirect('../main.php?page=users');
