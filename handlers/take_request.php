<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'take_request')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=requests'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=requests'); }
Database::getInstance()->update('requests',
    ['status' => 'в работе', 'assigned_to' => (int)$_SESSION['user_id'], 'updated_at' => date('Y-m-d H:i:s')],
    'id = ?', [$id]
);
flash('success', 'Заявка взята в работу.');
redirect('../main.php?page=requests');
