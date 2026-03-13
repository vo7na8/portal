<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_equipment')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=equipment'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate([
    'name'         => 'required|max:255',
    'type'         => 'required|max:100',
    'location'     => 'required|max:255',
    'inventory_no' => 'nullable|max:100',
    'status'       => 'required',
    'responsible'  => 'nullable|max:255',
])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    Database::getInstance()->insert('equipment', [
        'name'         => $d['name'],
        'type'         => $d['type'],
        'location'     => $d['location'],
        'inventory_no' => $d['inventory_no'] ?? '',
        'status'       => $d['status'],
        'responsible'  => $d['responsible'] ?? '',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);
    cacheDelete('equipment_list');
    flash('success', 'Техника добавлена.');
}
redirect('main.php?page=equipment');
