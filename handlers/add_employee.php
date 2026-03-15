<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_employee')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();

$db       = Database::getInstance();
$personId = (int)($_POST['person_id'] ?? 0);
$position = trim($_POST['position']   ?? '');

if ($personId <= 0 || $position === '') {
    flash('error', 'Укажите должность.');
    redirect('../main.php?page=persons');
}

$deptId   = (int)($_POST['department_id'] ?? 0) ?: null;
$hireDate = $_POST['hire_date'] ?: null;
$fireDate = $_POST['fire_date'] ?: null;

// Валидация дат
if ($hireDate && $fireDate && $fireDate < $hireDate) {
    flash('error', 'Дата увольнения не может быть раньше даты приёма.');
    redirect('../main.php?page=persons');
}

// Проверка дубликата активной должности
$exists = $db->selectValue(
    'SELECT COUNT(*) FROM employees WHERE person_id=? AND position=? AND COALESCE(department_id,0)=? AND is_active=1',
    [$personId, $position, $deptId ?? 0]
);
if ($exists) {
    flash('error', 'Такая должность уже назначена этому сотруднику.');
    redirect('../main.php?page=persons');
}

$db->insert('employees', [
    'person_id'       => $personId,
    'department_id'   => $deptId,
    'position'        => $position,
    'contract_number' => trim($_POST['contract_number'] ?? '') ?: null,
    'hire_date'       => $hireDate,
    'fire_date'       => $fireDate,
    'is_active'       => 1,
]);
flash('success', 'Должность добавлена.');
redirect('../main.php?page=persons');
