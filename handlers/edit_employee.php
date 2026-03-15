<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_employee')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();
$id       = (int)($_POST['id'] ?? 0);
$position = trim($_POST['position'] ?? '');
if ($id <= 0 || $position === '') {
    flash('error', 'Укажите должность');
    redirect('../main.php?page=persons');
}
$deptId = (int)($_POST['department_id'] ?? 0) ?: null;
Database::getInstance()->update('employees', [
    'department_id'   => $deptId,
    'position'        => $position,
    'contract_number' => trim($_POST['contract_number'] ?? '') ?: null,
    'hire_date'       => $_POST['hire_date'] ?: null,
    'fire_date'       => $_POST['fire_date'] ?: null,
], 'id = ?', [$id]);
flash('success', 'Должность обновлена.');
redirect('../main.php?page=persons');
