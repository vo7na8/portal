<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_request')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=requests'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255', 'description' => 'required'])) {
    flash('error', $v->firstErrorMessage());
    redirect('../main.php?page=requests');
}
$d             = $v->validated();
$assignedTo    = isset($_POST['assigned_to']) && (int)$_POST['assigned_to'] > 0
    ? (int)$_POST['assigned_to']
    : null;
$status = $assignedTo ? 'в работе' : 'новая';
Database::getInstance()->insert('requests', [
    'title'       => $d['title'],
    'description' => $d['description'],
    'status'      => $status,
    'author_id'   => (int)$_SESSION['user_id'],
    'assigned_to' => $assignedTo,
]);
flash('success', 'Заявка создана.');
redirect('../main.php?page=requests');
