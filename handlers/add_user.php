<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_user')) die('Недостаточно прав');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$full_name = trim($_POST['full_name'] ?? '');
$role_id = $_POST['role_id'] ?? 0;
if ($username === '' || $password === '' || $full_name === '' || !$role_id) die('Заполните все поля');
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role_id) VALUES (?, ?, ?, ?)");
$stmt->execute([$username, $hash, $full_name, $role_id]);
header('Location: ../main.php?page=users');
exit;