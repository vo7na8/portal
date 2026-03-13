<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'reassign_request')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=requests'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['id' => 'required|integer', 'user_id' => 'required|integer'])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    Database::getInstance()->update('requests', [
        'assigned_to' => (int)$d['user_id'],
        'status'      => 'в работе',
    ], 'id = ?', [(int)$d['id']]);
    flash('success', 'Заявка переназначена.');
}
redirect('main.php?page=requests');
