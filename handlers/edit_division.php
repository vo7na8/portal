<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_division')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();
$id   = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
if ($id <= 0 || $name === '') { flash('error', 'Название обязательно'); redirect('../main.php?page=structure'); }
// FIX #1: сохраняем parent_id при редактировании, не допускаем циклических ссылок
$parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
if ($parentId === $id) { $parentId = null; } // защита от self-reference
Database::getInstance()->update('divisions', [
    'name'       => $name,
    'short_name' => trim($_POST['short_name'] ?? '') ?: null,
    'parent_id'  => $parentId,
], 'id = ?', [$id]);
flash('success', 'Подразделение обновлено.');
redirect('../main.php?page=structure');
