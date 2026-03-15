<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_user')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=users'); }
$security->requireCsrf();
$id   = (int)($_POST['id'] ?? 0);
$myId = (int)($_SESSION['user_id'] ?? 0);
if ($id <= 0)       { flash('error', 'Неверный ID.');           redirect('../main.php?page=users'); }
if ($id === $myId)  { flash('error', 'Нельзя удалить себя.'); redirect('../main.php?page=users'); }
Database::getInstance()->delete('users', 'id = ?', [$id]);
flash('success', 'Пользователь удалён.');
redirect('../main.php?page=users');
