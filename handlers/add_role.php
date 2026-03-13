<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'manage_roles')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=roles'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['name' => 'required|max:100', 'description' => 'nullable|max:255'])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    Database::getInstance()->insert('roles', [
        'name'        => $d['name'],
        'description' => $d['description'] ?? '',
    ]);
    flash('success', 'Роль создана.');
}
redirect('main.php?page=roles');
