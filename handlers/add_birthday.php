<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_birthday')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=birthdays'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate(['full_name' => 'required|max:255', 'birth_date' => 'required|date'])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    Database::getInstance()->insert('birthdays', [
        'full_name'  => $d['full_name'],
        'birth_date' => $d['birth_date'],
    ]);
    flash('success', 'День рождения добавлен.');
}
redirect('main.php?page=birthdays');
