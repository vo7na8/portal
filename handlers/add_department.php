<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_department')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();
$name       = trim($_POST['name']        ?? '');
$divisionId = (int)($_POST['division_id'] ?? 0);
if ($name === '' || $divisionId <= 0) { flash('error', 'Заполните все поля'); redirect('../main.php?page=structure'); }
Database::getInstance()->insert('departments', [
    'name'        => $name,
    'short_name'  => trim($_POST['short_name'] ?? '') ?: null,
    'division_id' => $divisionId,
]);
flash('success', 'Отделение добавлено.');
redirect('../main.php?page=structure');
