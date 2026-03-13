<?php
require_once __DIR__ . '/../auth.php';
$security->requireCsrf();
$uid = (int)$_SESSION['user_id'];
$v   = Validator::make($_POST);
if (!$v->validate([
    'current_password'      => 'required',
    'new_password'          => 'required|password',
    'new_password_confirmation' => 'required',
])) {
    $session->flash('pwd_error', $v->firstErrorMessage());
    redirect('main.php?page=profile');
}
$d    = $v->validated();
$user = Database::getInstance()->selectOne('SELECT password_hash FROM users WHERE id=?', [$uid]);
if (!$user || !$security->verifyPassword($_POST['current_password'], $user['password_hash'])) {
    $session->flash('pwd_error', 'Текущий пароль неверен.');
    redirect('main.php?page=profile');
}
if ($_POST['new_password'] !== $_POST['new_password_confirmation']) {
    $session->flash('pwd_error', 'Пароли не совпадают.');
    redirect('main.php?page=profile');
}
Database::getInstance()->update('users',
    ['password_hash' => $security->hashPassword($_POST['new_password'])],
    'id = ?', [$uid]
);
$logger->info('Password changed', ['user_id' => $uid]);
$session->flash('pwd_success', 'Пароль успешно изменён.');
redirect('main.php?page=profile');
