<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_news')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=news'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255', 'content' => 'required'])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    Database::getInstance()->insert('news', [
        'title'      => $d['title'],
        'content'    => $d['content'],
        'author_id'  => $_SESSION['user_id'],
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    flash('success', 'Новость добавлена.');
}
redirect('main.php?page=news');
