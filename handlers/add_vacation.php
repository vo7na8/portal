<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_vacation')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=vacations'); }
$security->requireCsrf();
$v = Validator::make($_POST);
if (!$v->validate([
    'user_id'    => 'required|integer',
    'start_date' => 'required|date',
    'end_date'   => 'required|date',
])) {
    flash('error', $v->firstErrorMessage());
} else {
    $d = $v->validated();
    if ($d['start_date'] > $d['end_date']) {
        flash('error', 'Дата начала не может быть позже даты окончания.');
    } else {
        Database::getInstance()->insert('vacations', [
            'user_id'    => (int)$d['user_id'],
            'start_date' => $d['start_date'],
            'end_date'   => $d['end_date'],
        ]);
        flash('success', 'Отпуск добавлен.');
    }
}
redirect('main.php?page=vacations');
