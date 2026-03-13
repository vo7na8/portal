<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'manage_roles')) die('Недостаточно прав');
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
if ($name === '') die('Введите название роли');
$stmt = $pdo->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
$stmt->execute([$name, $description]);
header('Location: ../main.php?page=roles');
exit;