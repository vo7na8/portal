<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'reassign_request')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=requests'); }
$security->requireCsrf();
$id         = (int)($_POST['request_id'] ?? 0);
$assignedTo = isset($_POST['assigned_to']) && (int)$_POST['assigned_to'] > 0
    ? (int)$_POST['assigned_to']
    : null;
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=requests'); }
$newStatus = $assignedTo ? 'в работе' : 'новая';
Database::getInstance()->update('requests',
    ['assigned_to' => $assignedTo, 'status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')],
    'id = ?', [$id]
);
flash('success', $assignedTo ? 'Заявка переназначена.' : 'Ответственный снят.');
redirect('../main.php?page=requests');
