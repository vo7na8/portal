<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_equipment')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=equipment'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=equipment'); }

$db  = Database::getInstance();
$old = $db->selectOne(
    'SELECT e.*, u.full_name AS responsible_name, d.name AS dept_name
     FROM equipment e
     LEFT JOIN users u       ON e.responsible_id = u.id
     LEFT JOIN departments d ON e.department_id  = d.id
     WHERE e.id=?',
    [$id]
);
if (!$old) { flash('error', 'Запись не найдена'); redirect('../main.php?page=equipment'); }

$name             = trim($_POST['name']             ?? '');
$type             = trim($_POST['type']             ?? '');
$location         = trim($_POST['location']         ?? '');
$inventory_number = trim($_POST['inventory_number'] ?? '');
$status           = trim($_POST['status']           ?? '');
$responsibleId    = isset($_POST['responsible_id']) && (int)$_POST['responsible_id'] > 0 ? (int)$_POST['responsible_id'] : null;
$departmentId     = isset($_POST['department_id'])  && (int)$_POST['department_id']  > 0 ? (int)$_POST['department_id']  : null;

if ($name === '') { flash('error', 'Название обязательно'); redirect('../main.php?page=equipment'); }

// Лог изменений
$logs = [];
if ($old['name']             !== $name)             $logs[] = "Название: «{$old['name']}» → «{$name}»";
if (($old['type']     ?? '') !== $type)              $logs[] = "Тип: «{$old['type']}» → «{$type}»";
if (($old['location'] ?? '') !== $location)          $logs[] = "Расположение: «{$old['location']}» → «{$location}»";
if (($old['inventory_number'] ?? '') !== $inventory_number) $logs[] = "Инв.номер: «{$old['inventory_number']}» → «{$inventory_number}»";
if (($old['status']   ?? '') !== $status)            $logs[] = "Статус: «{$old['status']}» → «{$status}»";

$newResponsibleName = '';
if ($responsibleId) {
    $nu = $db->selectOne('SELECT full_name FROM users WHERE id=?', [$responsibleId]);
    $newResponsibleName = $nu['full_name'] ?? '';
}
if ((string)($old['responsible_id'] ?? '') !== (string)$responsibleId) {
    $logs[] = "Ответственный: «{$old['responsible_name']}» → «{$newResponsibleName}»";
}
$newDeptName = '';
if ($departmentId) {
    $nd = $db->selectOne('SELECT name FROM departments WHERE id=?', [$departmentId]);
    $newDeptName = $nd['name'] ?? '';
}
if ((string)($old['department_id'] ?? '') !== (string)$departmentId) {
    $logs[] = "Отделение: «{$old['dept_name']}» → «{$newDeptName}»";
}

$db->transaction(function($db) use ($id, $name, $type, $location, $inventory_number, $status, $responsibleId, $departmentId, $logs) {
    $db->update('equipment', [
        'name'             => $name,
        'type'             => $type,
        'location'         => $location,
        'inventory_number' => $inventory_number,
        'status'           => $status,
        'responsible_id'   => $responsibleId,
        'department_id'    => $departmentId,
    ], 'id = ?', [$id]);
    if (!empty($logs)) {
        $db->insert('equipment_comments', [
            'equipment_id' => $id,
            'user_id'      => (int)$_SESSION['user_id'],
            'body'         => implode("\n", $logs),
            'is_log'       => 1,
        ]);
    }
});
cacheDelete('equipment_list');
flash('success', 'Изменения сохранены.');
redirect('../main.php?page=equipment');
