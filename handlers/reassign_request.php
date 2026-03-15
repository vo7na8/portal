<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'reassign_request')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=requests'); }
$security->requireCsrf();
$id          = (int)($_POST['request_id'] ?? 0);
$assignedTo  = (int)($_POST['assigned_to'] ?? 0);
if ($id <= 0 || $assignedTo <= 0) { flash('error', 'Неверные данные.'); redirect('../main.php?page=requests'); }
Database::getInstance()->update('requests',
    ['assigned_to' => $assignedTo, 'updated_at' => date('Y-m-d H:i:s')],
    'id = ?', [$id]
);
flash('success', 'Заявка переназначена.');
redirect('../main.php?page=requests');
