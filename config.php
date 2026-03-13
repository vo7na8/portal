<?php
// Включаем отображение ошибок (только для отладки, потом закомментировать)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Запускаем сессию, только если она ещё не активна
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Путь к файлу базы данных SQLite
$db_path = __DIR__ . '/data/portal.db';

try {
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Проверка авторизации
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

// Получение имени пользователя
function getUserName($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    return $row ? $row['full_name'] : 'Неизвестно';
}

// Проверка наличия разрешения
function hasPermission($pdo, $permission_name) {
    if (!isset($_SESSION['user_id'])) return false;
    $userId = $_SESSION['user_id'];
    $sql = "SELECT COUNT(*) FROM users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = :user_id AND p.name = :perm";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId, 'perm' => $permission_name]);
    return $stmt->fetchColumn() > 0;
}

// Кэширование
function cacheGet($key, $ttl = 300) {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
    $file = $cacheDir . md5($key) . '.cache';
    if (file_exists($file) && (time() - filemtime($file) < $ttl)) {
        return unserialize(file_get_contents($file));
    }
    return null;
}
function cacheSet($key, $data) {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
    file_put_contents($cacheDir . md5($key) . '.cache', serialize($data));
}
function cacheDelete($key) {
    $file = __DIR__ . '/cache/' . md5($key) . '.cache';
    if (file_exists($file)) unlink($file);
}