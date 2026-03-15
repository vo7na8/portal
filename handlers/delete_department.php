<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_department')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();

$db = Database::getInstance();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=structure'); }

// Защита: нельзя удалять, если есть активные сотрудники
$activeEmployees = $db->selectValue(
    'SELECT COUNT(*) FROM employees WHERE department_id=? AND is_active=1',
    [$id]
);
if ($activeEmployees > 0) {
    flash('error', 'Нельзя удалить отделение: в нём есть активные сотрудники (' . (int)$activeEmployees . '). Сначала увольте или переведите их.');
    redirect('../main.php?page=structure');
}

$db->delete('departments', 'id=?', [$id]);
flash('success', 'Отделение удалено.');
redirect('../main.php?page=structure');
