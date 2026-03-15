<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_vacation')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=vacations'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=vacations'); }
Database::getInstance()->delete('vacations', 'id = ?', [$id]);
flash('success', 'Отпуск удалён.');
redirect('../main.php?page=vacations');
