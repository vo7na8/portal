<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_employee')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();
$personId  = (int)($_POST['person_id']  ?? 0);
$position  = trim($_POST['position']    ?? '');
if ($personId <= 0 || $position === '') { flash('error', 'Укажите должность'); redirect('../main.php?page=persons'); }
$deptId = (int)($_POST['department_id'] ?? 0) ?: null;
Database::getInstance()->insert('employees', [
    'person_id'       => $personId,
    'department_id'   => $deptId,
    'position'        => $position,
    'contract_number' => trim($_POST['contract_number'] ?? '') ?: null,
    'hire_date'       => $_POST['hire_date'] ?: null,
    'fire_date'       => $_POST['fire_date'] ?: null,
    'is_active'       => 1,
]);
flash('success', 'Должность добавлена.');
redirect('../main.php?page=persons');
