<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_link')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=links'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255', 'url' => 'required|url|max:500'])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    Database::getInstance()->insert('links', [
        'title'      => $d['title'],
        'url'        => $d['url'],
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    cacheDelete('links_list');
    flash('success', 'Ссылка добавлена.');
}
redirect('main.php?page=links');
