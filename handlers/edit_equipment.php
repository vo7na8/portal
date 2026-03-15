<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_equipment')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=equipment'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=equipment'); }

$db  = Database::getInstance();
$old = $db->selectOne('SELECT e.*, u.full_name AS responsible_name FROM equipment e LEFT JOIN users u ON e.responsible_id=u.id WHERE e.id=?', [$id]);
if (!$old) { flash('error', 'Запись не найдена'); redirect('../main.php?page=equipment'); }

$name             = trim($_POST['name']             ?? '');
$type             = trim($_POST['type']             ?? '');
$location         = trim($_POST['location']         ?? '');
$inventory_number = trim($_POST['inventory_number'] ?? '');
$status           = trim($_POST['status']           ?? '');
$responsibleId    = isset($_POST['responsible_id']) && (int)$_POST['responsible_id'] > 0 ? (int)$_POST['responsible_id'] : null;

if ($name === '') { flash('error', 'Название обязательно'); redirect('../main.php?page=equipment'); }

// --- Строим лог изменений ---
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
$oldRid = $old['responsible_id'] ?? null;
if ((string)$oldRid !== (string)$responsibleId) {
    $logs[] = "Ответственный: «{$old['responsible_name']}» → «{$newResponsibleName}»";
}

$db->update('equipment', [
    'name'             => $name,
    'type'             => $type,
    'location'         => $location,
    'inventory_number' => $inventory_number,
    'status'           => $status,
    'responsible_id'   => $responsibleId,
], 'id = ?', [$id]);
cacheDelete('equipment_list');

if (!empty($logs)) {
    $userId = (int)$_SESSION['user_id'];
    $db->insert('equipment_comments', [
        'equipment_id' => $id,
        'user_id'      => $userId,
        'body'         => implode("\n", $logs),
        'is_log'       => 1,
    ]);
}
flash('success', 'Изменения сохранены.');
redirect('../main.php?page=equipment');
