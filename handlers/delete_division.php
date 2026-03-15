<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_division')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=structure'); }
$cnt = (int)Database::getInstance()->selectValue('SELECT COUNT(*) FROM departments WHERE division_id=?', [$id]);
if ($cnt > 0) { flash('error', 'Нельзя удалить: есть отделения. Сначала удалите их.'); redirect('../main.php?page=structure'); }
Database::getInstance()->delete('divisions', 'id = ?', [$id]);
flash('success', 'Подразделение удалено.');
redirect('../main.php?page=structure');
