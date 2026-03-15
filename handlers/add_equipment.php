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
$userId        = (int)$_SESSION['user_id'];

$db = Database::getInstance();
$db->transaction(function($db) use ($d, $responsibleId, $departmentId, $userId) {
    $eqId = $db->insert('equipment', [
        'name'             => $d['name'],
        'type'             => $d['type']             ?? '',
        'location'         => $d['location']         ?? '',
        'inventory_number' => $d['inventory_number'] ?? '',
        'status'           => $d['status'],
        'responsible_id'   => $responsibleId,
        'department_id'    => $departmentId,
    ]);

    $db->insert('equipment_comments', [
        'equipment_id' => $eqId,
        'user_id'      => $userId,
        'body'         => 'Добавлено новое оборудование: «' . $d['name'] . '», статус: ' . $d['status'],
        'is_log'       => 1,
    ]);

    $parts = [];
    if (!empty($d['type']))             $parts[] = 'тип: '          . $d['type'];
    if (!empty($d['location']))         $parts[] = 'расположение: ' . $d['location'];
    if (!empty($d['inventory_number'])) $parts[] = 'инв. номер: ' . $d['inventory_number'];
    $body = 'Статус: ' . $d['status'];
    if (!empty($parts)) $body .= ' · ' . implode(' · ', $parts);

    $db->insert('news', [
        'title'     => 'Новая техника: «' . $d['name'] . '»',
        'body'      => $body,
        'author_id' => $userId,
    ]);
});
cacheDelete('equipment_list');
flash('success', 'Техника добавлена.');
redirect('../main.php?page=equipment');
