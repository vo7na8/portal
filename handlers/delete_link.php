<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_link')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM links WHERE id = ?");
$stmt->execute([$id]);
cacheDelete('links_list');
header('Location: ../main.php?page=links');
exit;