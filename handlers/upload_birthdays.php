<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'upload_birthdays')) { flash('error', 'Недостаточно прав'); redirect('main.php?page=birthdays'); }
$security->requireCsrf();
if (!isset($_FILES['csv_file'])) { flash('error', 'Файл не передан.'); redirect('main.php?page=birthdays'); }
$err = $security->validateUpload($_FILES['csv_file']);
if ($err) { flash('error', $err); redirect('main.php?page=birthdays'); }
$ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') { flash('error', 'Допустимы только CSV-файлы.'); redirect('main.php?page=birthdays'); }
$uploadDir = __DIR__ . '/../uploads/temp/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$tmpPath = $uploadDir . $security->safeFilename($_FILES['csv_file']['name']);
if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $tmpPath)) {
    flash('error', 'Ошибка сохранения файла.');
    redirect('main.php?page=birthdays');
}
$count = 0;
$errors = 0;
$db = Database::getInstance();
if (($handle = fopen($tmpPath, 'r')) !== false) {
    $firstLine = true;
    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        if ($firstLine) { $firstLine = false; continue; }
        if (count($row) < 2) { $errors++; continue; }
        $name = trim($row[0]);
        $date = trim($row[1]);
        if (!$name || !strtotime($date)) { $errors++; continue; }
        $db->execute('INSERT OR REPLACE INTO birthdays (full_name, birth_date) VALUES (?, ?)', [$name, date('Y-m-d', strtotime($date))]);
        $count++;
    }
    fclose($handle);
}
@unlink($tmpPath);
flash('success', "Загружено записей: {$count}" . ($errors ? ". Пропущено строк: {$errors}." : '.'));
redirect('main.php?page=birthdays');
