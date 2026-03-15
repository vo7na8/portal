<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_department')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=structure'); }
$cnt = (int)Database::getInstance()->selectValue('SELECT COUNT(*) FROM employees WHERE department_id=? AND is_active=1', [$id]);
if ($cnt > 0) { flash('error', "Нельзя удалить: {$cnt} акт. сотрудников. Сначала отметьте их увольненными."); redirect('../main.php?page=structure'); }
Database::getInstance()->delete('departments', 'id = ?', [$id]);
flash('success', 'Отделение удалено.');
redirect('../main.php?page=structure');
