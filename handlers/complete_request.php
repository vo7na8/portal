<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'complete_request')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("UPDATE requests SET status = 'выполнена' WHERE id = ?");
$stmt->execute([$id]);
header('Location: ../main.php?page=requests');
exit;