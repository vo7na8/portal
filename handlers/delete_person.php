<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_person')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();

$db = Database::getInstance();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=persons'); }

// Предупреждение: если есть активные должности
$activeJobs = $db->selectValue('SELECT COUNT(*) FROM employees WHERE person_id=? AND is_active=1', [$id]);
if ($activeJobs > 0) {
    flash('error', 'Нельзя удалить физическое лицо: есть активные должности (' . (int)$activeJobs . '). Сначала увольте сотрудника.');
    redirect('../main.php?page=persons');
}

// Удаляем: employees удалятся каскадно (ON DELETE CASCADE)
$db->delete('persons', 'id=?', [$id]);
flash('success', 'Физическое лицо удалено.');
redirect('../main.php?page=persons');
