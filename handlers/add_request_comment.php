<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'view_requests')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=requests'); }
$security->requireCsrf();
$requestId = (int)($_POST['request_id'] ?? 0);
$body      = trim($_POST['comment'] ?? '');
if ($requestId <= 0 || $body === '') { flash('error', 'Неверные данные.'); redirect('../main.php?page=requests'); }
Database::getInstance()->insert('request_comments', [
    'request_id' => $requestId,
    'user_id'    => (int)$_SESSION['user_id'],
    'body'       => mb_substr($body, 0, 1000),
    'is_log'     => 0,
]);
flash('success', 'Комментарий добавлен.');
redirect('../main.php?page=requests');
