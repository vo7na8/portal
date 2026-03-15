<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_news')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=news'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=news'); }
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255', 'body' => 'required'])) {
    flash('error', $v->firstErrorMessage());
    redirect('../main.php?page=news');
}
$d = $v->validated();
Database::getInstance()->update('news', ['title' => $d['title'], 'body' => $d['body']], 'id = ?', [$id]);
flash('success', 'Новость обновлена.');
redirect('../main.php?page=news');
