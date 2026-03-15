<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_employee')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();

$db       = Database::getInstance();
$id       = (int)($_POST['id'] ?? 0);
$position = trim($_POST['position'] ?? '');

if ($id <= 0 || $position === '') {
    flash('error', 'Укажите должность');
    redirect('../main.php?page=persons');
}

$hireDate = $_POST['hire_date'] ?: null;
$fireDate = $_POST['fire_date'] ?: null;

// Валидация дат
if ($hireDate && $fireDate && $fireDate < $hireDate) {
    flash('error', 'Дата увольнения не может быть раньше даты приёма.');
    redirect('../main.php?page=persons');
}

$deptId = (int)($_POST['department_id'] ?? 0) ?: null;

$db->transaction(function($db) use ($id, $deptId, $position, $hireDate, $fireDate) {
    $db->update('employees', [
        'department_id'   => $deptId,
        'position'        => $position,
        'contract_number' => trim($_POST['contract_number'] ?? '') ?: null,
        'hire_date'       => $hireDate,
        'fire_date'       => $fireDate,
        'updated_at'      => date('Y-m-d H:i:s'),
    ], 'id = ?', [$id]);
});

flash('success', 'Должность обновлена.');
redirect('../main.php?page=persons');
