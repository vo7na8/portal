<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_equipment')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
$stmt->execute([$id]);
cacheDelete('equipment_list');
header('Location: ../main.php?page=equipment');
exit;