<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_link')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=links'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    Database::getInstance()->delete('links', 'id = ?', [$id]);
    cacheDelete('links_list');
    flash('success', 'Ссылка удалена.');
} else {
    flash('error', 'Неверный запрос.');
}
redirect('main.php?page=links');
