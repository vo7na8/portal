<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_request')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=requests'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=requests'); }
$db  = Database::getInstance();
$who = $_SESSION['user_name'] ?? 'Пользователь';
$db->update('requests',
    ['status' => 'выполнена', 'updated_at' => date('Y-m-d H:i:s')],
    'id = ?', [$id]
);
$db->insert('request_comments', [
    'request_id' => $id,
    'user_id'    => (int)$_SESSION['user_id'],
    'body'       => "Заявка закрыта пользователем «{$who}»",
    'is_log'     => 1,
]);
flash('success', 'Заявка выполнена.');
redirect('../main.php?page=requests');
