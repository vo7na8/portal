<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_news')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=news'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=news'); }
Database::getInstance()->delete('news', 'id = ?', [$id]);
flash('success', 'Новость удалена.');
redirect('../main.php?page=news');
