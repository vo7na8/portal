<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_equipment')) {
    flash('error', 'Недостаточно прав');
    redirect('../main.php?page=equipment');
}
$security->requireCsrf();

$v = Validator::make($_POST);
if (!$v->validate([
    'name'             => 'required|max:255',
    'type'             => 'nullable|max:100',
    'location'         => 'nullable|max:255',
    'inventory_number' => 'nullable|max:100',
    'status'           => 'required',
    'responsible_id'   => 'nullable',
    'department_id'    => 'nullable',
])) {
    flash('error', $v->firstErrorMessage());
    redirect('../main.php?page=equipment');
}

$d             = $v->validated();
$responsibleId = isset($d['responsible_id']) && (int)$d['responsible_id'] > 0 ? (int)$d['responsible_id'] : null;
$departmentId  = isset($d['department_id'])  && (int)$d['department_id']  > 0 ? (int)$d['department_id']  : null;

$db = Database::getInstance();
$db->transaction(function($db) use ($d, $responsibleId, $departmentId) {
    $eqId = $db->insert('equipment', [
        'name'             => $d['name'],
        'type'             => $d['type']             ?? '',
        'location'         => $d['location']         ?? '',
        'inventory_number' => $d['inventory_number'] ?? '',
        'status'           => $d['status'],
        'responsible_id'   => $responsibleId,
        'department_id'    => $departmentId,
    ]);
    // Событие в ленту активности
    $db->insert('equipment_comments', [
        'equipment_id' => $eqId,
        'user_id'      => (int)$_SESSION['user_id'],
        'body'         => 'Добавлено новое оборудование: «' . $d['name'] . '», статус: ' . $d['status'],
        'is_log'       => 1,
    ]);
});
cacheDelete('equipment_list');
flash('success', 'Техника добавлена.');
redirect('../main.php?page=equipment');
