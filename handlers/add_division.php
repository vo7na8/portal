<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_division')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();
$name = trim($_POST['name'] ?? '');
if ($name === '') { flash('error', 'Название обязательно'); redirect('../main.php?page=structure'); }
$parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
Database::getInstance()->insert('divisions', [
    'name'       => $name,
    'short_name' => trim($_POST['short_name'] ?? '') ?: null,
    'parent_id'  => $parentId,
]);
flash('success', 'Подразделение добавлено.');
redirect('../main.php?page=structure');
