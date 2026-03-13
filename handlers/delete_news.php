<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_news')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
$stmt->execute([$id]);
header('Location: ../main.php?page=news');
exit;