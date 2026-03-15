<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_news')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=news'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255', 'body' => 'required'])) {
    flash('error', $v->firstErrorMessage());
    redirect('../main.php?page=news');
}
$d = $v->validated();
Database::getInstance()->insert('news', [
    'title'     => $d['title'],
    'body'      => $d['body'],
    'author_id' => (int)$_SESSION['user_id'],
]);
flash('success', 'Новость добавлена.');
redirect('../main.php?page=news');
