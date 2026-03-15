<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_department')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();

$db         = Database::getInstance();
$divisionId = (int)($_POST['division_id'] ?? 0);
$name       = trim($_POST['name']         ?? '');
$short      = trim($_POST['short_name']   ?? '');

if ($divisionId <= 0 || $name === '') {
    flash('error', 'Укажите название отделения.');
    redirect('../main.php?page=structure');
}

// Проверка дубликата внутри подразделения
$exists = $db->selectValue(
    'SELECT COUNT(*) FROM departments WHERE name=? AND division_id=?',
    [$name, $divisionId]
);
if ($exists) {
    flash('error', 'Отделение с таким названием уже существует в этом подразделении.');
    redirect('../main.php?page=structure');
}

$db->insert('departments', [
    'name'        => $name,
    'short_name'  => $short ?: null,
    'division_id' => $divisionId,
    'sort_order'  => 0,
]);
flash('success', 'Отделение добавлено.');
redirect('../main.php?page=structure');
