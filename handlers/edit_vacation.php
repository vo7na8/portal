<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_vacation')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=vacations'); }
$security->requireCsrf();
$id         = (int)($_POST['id']         ?? 0);
$userId     = (int)($_POST['user_id']    ?? 0);
$startDate  = trim($_POST['start_date'] ?? '');
$endDate    = trim($_POST['end_date']   ?? '');
$note       = trim($_POST['note']       ?? '');
if ($id <= 0 || $userId <= 0 || $startDate === '' || $endDate === '') {
    flash('error', 'Заполните все обязательные поля.');
    redirect('../main.php?page=vacations');
}
if ($startDate > $endDate) {
    flash('error', 'Дата начала не может быть позже даты окончания.');
    redirect('../main.php?page=vacations');
}
Database::getInstance()->update('vacations', [
    'user_id'    => $userId,
    'start_date' => $startDate,
    'end_date'   => $endDate,
    'note'       => $note,
], 'id = ?', [$id]);
flash('success', 'Отпуск обновлён.');
redirect('../main.php?page=vacations');
