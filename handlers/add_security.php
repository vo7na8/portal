<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_security')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=security'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255', 'description' => 'required'])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    Database::getInstance()->insert('security', [
        'title'       => $d['title'],
        'description' => $d['description'],
        'created_at'  => date('Y-m-d H:i:s'),
    ]);
    flash('success', 'Запись добавлена.');
}
redirect('main.php?page=security');
