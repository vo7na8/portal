<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_equipment')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=equipment'); }
$security->requireCsrf();
$equipmentId = (int)($_POST['equipment_id'] ?? 0);
$body        = trim($_POST['comment'] ?? '');
if ($equipmentId <= 0 || $body === '') { flash('error', 'Неверные данные.'); redirect('../main.php?page=equipment'); }
Database::getInstance()->insert('equipment_comments', [
    'equipment_id' => $equipmentId,
    'user_id'      => (int)$_SESSION['user_id'],
    'body'         => mb_substr($body, 0, 1000),
    'is_log'       => 0,
]);
flash('success', 'Комментарий добавлен.');
redirect('../main.php?page=equipment');
