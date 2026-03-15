<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_security')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=security'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255'])) {
    flash('error', $v->firstErrorMessage());
    redirect('../main.php?page=security');
}
$d = $v->validated();
Database::getInstance()->insert('security_incidents', [
    'title'       => $d['title'],
    'description' => trim($_POST['description'] ?? ''),
    'severity'    => in_array($_POST['severity'] ?? '', ['низкая', 'средняя', 'высокая']) ? $_POST['severity'] : 'низкая',
    'author_id'   => (int)$_SESSION['user_id'],
]);
flash('success', 'Инцидент зафиксирован.');
redirect('../main.php?page=security');
