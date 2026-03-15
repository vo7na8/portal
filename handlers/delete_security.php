<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_security')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=security'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=security'); }
Database::getInstance()->delete('security_incidents', 'id = ?', [$id]);
flash('success', 'Инцидент удалён.');
redirect('../main.php?page=security');
