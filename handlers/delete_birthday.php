<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'view_birthdays')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=birthdays'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=birthdays'); }
Database::getInstance()->delete('birthdays', 'id = ?', [$id]);
flash('success', 'Запись удалена.');
redirect('../main.php?page=birthdays');
