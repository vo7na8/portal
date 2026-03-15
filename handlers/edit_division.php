<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_division')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=structure'); }
$security->requireCsrf();
$id   = (int)($_POST['id']   ?? 0);
$name = trim($_POST['name'] ?? '');
if ($id <= 0 || $name === '') { flash('error', 'Неверные данные'); redirect('../main.php?page=structure'); }
Database::getInstance()->update('divisions', [
    'name'       => $name,
    'short_name' => trim($_POST['short_name'] ?? '') ?: null,
], 'id = ?', [$id]);
flash('success', 'Подразделение обновлено.');
redirect('../main.php?page=structure');
