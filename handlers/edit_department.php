<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_department')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();

$db    = Database::getInstance();
$id    = (int)($_POST['id']        ?? 0);
$name  = trim($_POST['name']       ?? '');
$short = trim($_POST['short_name'] ?? '');
$sortOrder = (int)($_POST['sort_order'] ?? 0);

if ($id <= 0 || $name === '') { flash('error', 'Укажите название.'); redirect('../main.php?page=structure'); }

// Проверка дубликата внутри того же подразделения
$curDivId = $db->selectValue('SELECT division_id FROM departments WHERE id=?', [$id]);
$exists = $db->selectValue(
    'SELECT COUNT(*) FROM departments WHERE name=? AND division_id=? AND id!=?',
    [$name, $curDivId, $id]
);
if ($exists) {
    flash('error', 'Отделение с таким названием уже существует в этом подразделении.');
    redirect('../main.php?page=structure');
}

$db->update('departments', [
    'name'       => $name,
    'short_name' => $short ?: null,
    'sort_order' => $sortOrder,
], 'id = ?', [$id]);
flash('success', 'Отделение обновлено.');
redirect('../main.php?page=structure');
