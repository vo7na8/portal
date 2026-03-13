<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'take_request')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=requests'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    Database::getInstance()->update('requests', [
        'status'      => 'в работе',
        'assigned_to' => $_SESSION['user_id'],
    ], 'id = ?', [$id]);
    flash('success', 'Заявка взята в работу.');
} else {
    flash('error', 'Неверный запрос.');
}
redirect('main.php?page=requests');
