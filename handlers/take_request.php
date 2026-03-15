<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'take_request')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=requests'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=requests'); }
$db  = Database::getInstance();
$who = $_SESSION['user_name'] ?? 'Пользователь';
$db->update('requests',
    ['status' => 'в работе', 'assigned_to' => (int)$_SESSION['user_id'], 'updated_at' => date('Y-m-d H:i:s')],
    'id = ?', [$id]
);
$db->insert('request_comments', [
    'request_id' => $id,
    'user_id'    => (int)$_SESSION['user_id'],
    'body'       => "Заявка взята в работу пользователем «{$who}»",
    'is_log'     => 1,
]);
flash('success', 'Заявка взята в работу.');
redirect('../main.php?page=requests');
