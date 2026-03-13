<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_link')) die('Недостаточно прав');
$title = trim($_POST['title'] ?? '');
$url = trim($_POST['url'] ?? '');
if ($title === '' || $url === '') die('Заполните все поля');
$stmt = $pdo->prepare("INSERT INTO links (title, url) VALUES (?, ?)");
$stmt->execute([$title, $url]);
cacheDelete('links_list');
header('Location: ../main.php?page=links');
exit;