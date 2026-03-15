<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_employee')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=persons'); }
$db  = Database::getInstance();
$cur = $db->selectValue('SELECT is_active FROM employees WHERE id=?', [$id]);
if ($cur === false) { flash('error', 'Не найдено'); redirect('../main.php?page=persons'); }
$db->update('employees', ['is_active' => $cur ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
flash('success', $cur ? 'Отмечен как уволенный.' : 'Восстановлен.');
redirect('../main.php?page=persons');
