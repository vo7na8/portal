<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_link')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=links'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255', 'url' => 'required|max:2048'])) {
    flash('error', $v->firstErrorMessage());
    redirect('../main.php?page=links');
}
$d = $v->validated();
Database::getInstance()->insert('links', [
    'title'    => $d['title'],
    'url'      => $d['url'],
    'category' => trim($_POST['category'] ?? ''),
]);
flash('success', 'Ссылка добавлена.');
redirect('../main.php?page=links');
