<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_request')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=requests'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=requests'); }
Database::getInstance()->update('requests',
    ['status' => 'выполнена', 'updated_at' => date('Y-m-d H:i:s')],
    'id = ?', [$id]
);
flash('success', 'Заявка выполнена.');
redirect('../main.php?page=requests');
