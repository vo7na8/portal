<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'complete_request')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=requests'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    Database::getInstance()->update('requests', [
        'status'       => 'выполнена',
        'completed_at' => date('Y-m-d H:i:s'),
    ], 'id = ?', [$id]);
    flash('success', 'Заявка закрыта.');
} else {
    flash('error', 'Неверный запрос.');
}
redirect('main.php?page=requests');
