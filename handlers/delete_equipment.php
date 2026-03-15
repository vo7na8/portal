<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_equipment')) {
    flash('error', 'Недостаточно прав');
    redirect('../main.php?page=equipment');
}
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=equipment'); }
Database::getInstance()->delete('equipment', 'id = ?', [$id]);
cacheDelete('equipment_list');
flash('success', 'Оборудование удалено.');
redirect('../main.php?page=equipment');
