<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_person')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=persons'); }
Database::getInstance()->delete('persons', 'id = ?', [$id]);
flash('success', 'Человек удалён.');
redirect('../main.php?page=persons');
