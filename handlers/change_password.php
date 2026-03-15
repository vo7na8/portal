<?php
require_once __DIR__ . '/../auth.php';
$security->requireCsrf();
$current  = $_POST['current_password'] ?? '';
$new      = $_POST['new_password']     ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
if ($new === '' || strlen($new) < 6) {
    flash('error', 'Новый пароль должен быть не менее 6 символов.');
    redirect('../main.php?page=profile');
}
if ($new !== $confirm) {
    flash('error', 'Пароли не совпадают.');
    redirect('../main.php?page=profile');
}
$user = Database::getInstance()->selectOne('SELECT * FROM users WHERE id = ?', [(int)$_SESSION['user_id']]);
if (!$user || !$security->verifyPassword($current, $user['password_hash'])) {
    flash('error', 'Неверный текущий пароль.');
    redirect('../main.php?page=profile');
}
Database::getInstance()->update('users',
    ['password_hash' => $security->hashPassword($new)],
    'id = ?', [(int)$_SESSION['user_id']]
);
flash('success', 'Пароль успешно изменён.');
redirect('../main.php?page=profile');
