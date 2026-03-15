<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_division')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();

$db       = Database::getInstance();
$id       = (int)($_POST['id']        ?? 0);
$name     = trim($_POST['name']       ?? '');
$short    = trim($_POST['short_name'] ?? '');
$parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
$sortOrder = (int)($_POST['sort_order'] ?? 0);

if ($id <= 0 || $name === '') { flash('error', 'Укажите название.'); redirect('../main.php?page=structure'); }

// Защита от циклических ссылок (parent != self)
if ($parentId === $id) { flash('error', 'Подразделение не может быть родителем самого себя.'); redirect('../main.php?page=structure'); }

// Проверка дубликата названия на том же уровне
$exists = $db->selectValue(
    'SELECT COUNT(*) FROM divisions WHERE name=? AND COALESCE(parent_id,0)=? AND id!=?',
    [$name, $parentId ?? 0, $id]
);
if ($exists) {
    flash('error', 'Подразделение с таким названием уже существует на этом уровне.');
    redirect('../main.php?page=structure');
}

$db->update('divisions', [
    'name'       => $name,
    'short_name' => $short ?: null,
    'parent_id'  => $parentId,
    'sort_order' => $sortOrder,
], 'id = ?', [$id]);
flash('success', 'Подразделение обновлено.');
redirect('../main.php?page=structure');
