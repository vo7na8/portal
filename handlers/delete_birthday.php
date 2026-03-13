<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_birthday')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=birthdays'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    Database::getInstance()->delete('birthdays', 'id = ?', [$id]);
    flash('success', 'Запись удалена.');
} else {
    flash('error', 'Неверный запрос.');
}
redirect('main.php?page=birthdays');
