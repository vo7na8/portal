<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'view_birthdays')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=birthdays'); }
$security->requireCsrf();
if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    flash('error', 'Файл не загружен.');
    redirect('../main.php?page=birthdays');
}
$uploadError = $security->validateUpload($_FILES['csv_file']);
if ($uploadError) { flash('error', $uploadError); redirect('../main.php?page=birthdays'); }
$handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
if (!$handle) { flash('error', 'Не удалось открыть файл.'); redirect('../main.php?page=birthdays'); }
$db      = Database::getInstance();
$count   = 0;
$skipped = 0;
fgets($handle); // пропускаем заголовок
while (($row = fgetcsv($handle, 500, ';')) !== false) {
    if (count($row) < 2) { $skipped++; continue; }
    $name = trim($row[0]);
    $date = trim($row[1]);
    if (!$name || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $skipped++; continue; }
    $db->insert('birthdays', ['full_name' => $name, 'birth_date' => $date, 'note' => trim($row[2] ?? '')]);
    $count++;
}
fclose($handle);
flash('success', "Загружено: {$count}, пропущено: {$skipped}.");
redirect('../main.php?page=birthdays');
