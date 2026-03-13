<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_security')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=security'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    Database::getInstance()->delete('security', 'id = ?', [$id]);
    flash('success', 'Запись удалена.');
} else {
    flash('error', 'Неверный запрос.');
}
redirect('main.php?page=security');
