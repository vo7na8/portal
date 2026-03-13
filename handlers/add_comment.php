<?php
require_once __DIR__ . '/../auth.php';
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['equipment_id' => 'required|integer', 'comment' => 'required|max:1000'])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    Database::getInstance()->insert('comments', [
        'equipment_id' => (int)$d['equipment_id'],
        'user_id'      => $_SESSION['user_id'],
        'comment'      => $d['comment'],
        'created_at'   => date('Y-m-d H:i:s'),
    ]);
    flash('success', 'Комментарий добавлен.');
}
redirect('main.php?page=equipment');
