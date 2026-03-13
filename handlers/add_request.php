<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_request')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=requests'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255', 'description' => 'required'])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    Database::getInstance()->insert('requests', [
        'title'       => $d['title'],
        'description' => $d['description'],
        'status'      => 'новая',
        'author_id'   => $_SESSION['user_id'],
        'created_at'  => date('Y-m-d H:i:s'),
    ]);
    flash('success', 'Заявка создана.');
}
redirect('main.php?page=requests');
