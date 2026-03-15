<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_division')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();

$db = Database::getInstance();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=structure'); }

// Защита: нельзя удалять, если есть дочерние подразделения
$hasChildren = $db->selectValue('SELECT COUNT(*) FROM divisions WHERE parent_id=?', [$id]);
if ($hasChildren > 0) {
    flash('error', 'Нельзя удалить подразделение с дочерними элементами. Сначала переместите или удалите их.');
    redirect('../main.php?page=structure');
}

// Защита: нельзя удалять, если есть отделения
$hasDepartments = $db->selectValue('SELECT COUNT(*) FROM departments WHERE division_id=?', [$id]);
if ($hasDepartments > 0) {
    flash('error', 'Нельзя удалить подразделение с отделениями (' . (int)$hasDepartments . '). Сначала удалите или переместите отделения.');
    redirect('../main.php?page=structure');
}

$db->delete('divisions', 'id=?', [$id]);
flash('success', 'Подразделение удалено.');
redirect('../main.php?page=structure');
