<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_division')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();

$db       = Database::getInstance();
$name     = trim($_POST['name']       ?? '');
$short    = trim($_POST['short_name'] ?? '');
$parentId = (int)($_POST['parent_id'] ?? 0) ?: null;

if ($name === '') { flash('error', 'Укажите название.'); redirect('../main.php?page=structure'); }

// Проверка дубликата
$exists = $db->selectValue(
    'SELECT COUNT(*) FROM divisions WHERE name=? AND COALESCE(parent_id,0)=?',
    [$name, $parentId ?? 0]
);
if ($exists) {
    flash('error', 'Подразделение с таким названием уже существует на этом уровне.');
    redirect('../main.php?page=structure');
}

$db->insert('divisions', [
    'name'       => $name,
    'short_name' => $short ?: null,
    'parent_id'  => $parentId,
    'sort_order' => 0,
]);
flash('success', 'Подразделение добавлено.');
redirect('../main.php?page=structure');
