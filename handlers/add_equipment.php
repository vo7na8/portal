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
])) {
    flash('error', $v->firstErrorMessage());
    redirect('../main.php?page=equipment');
}

$d             = $v->validated();
$responsibleId = isset($d['responsible_id']) && (int)$d['responsible_id'] > 0
    ? (int)$d['responsible_id']
    : null;

Database::getInstance()->insert('equipment', [
    'name'             => $d['name'],
    'type'             => $d['type']             ?? '',
    'location'         => $d['location']         ?? '',
    'inventory_number' => $d['inventory_number'] ?? '',
    'status'           => $d['status'],
    'responsible_id'   => $responsibleId,
]);
cacheDelete('equipment_list');
flash('success', 'Техника добавлена.');
redirect('../main.php?page=equipment');
