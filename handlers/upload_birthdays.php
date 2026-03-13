<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_birthday')) die('Недостаточно прав');
if ($_FILES['birthday_file']['error'] !== UPLOAD_ERR_OK) die('Ошибка загрузки файла');
$file_tmp = $_FILES['birthday_file']['tmp_name'];
$handle = fopen($file_tmp, 'r');
if (!$handle) die('Не удалось открыть файл');
fgetcsv($handle); // пропуск заголовка
$stmt = $pdo->prepare("INSERT INTO birthdays (full_name, birth_date) VALUES (?, ?)");
while (($data = fgetcsv($handle)) !== FALSE) {
    if (count($data) >= 2) {
        $full_name = trim($data[0]);
        $birth_date = trim($data[1]);
        $d = DateTime::createFromFormat('d.m.Y', $birth_date);
        if ($d) {
            $stmt->execute([$full_name, $d->format('Y-m-d')]);
        }
    }
}
fclose($handle);
header('Location: ../main.php?page=birthdays');
exit;