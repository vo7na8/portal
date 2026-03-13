<?php
/**
 * Центральный конфигурационный файл
 * Подключается в начале каждой страницы и обработчика.
 * Обратно совместим со старым кодом.
 */

// Загружаем ядро системы (порядок важен: Logger самый первый, т.к. Database/Session его используют)
require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/Validator.php';

// Инициализация
$config   = Config::getInstance();
$session  = Session::getInstance();   // запускает сессию
$security = Security::getInstance();
$logger   = Logger::getInstance();

// Настройка отображения ошибок
if ($config->isDevelopment() && $config->isDebug()) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger) {
        if (!($errno & error_reporting())) return false;
        $logger->error("PHP Error [{$errno}]: {$errstr}", [
            'file' => $errfile,
            'line' => $errline,
        ]);
        return true;
    });
    set_exception_handler(function (\Throwable $e) use ($logger) {
        $logger->error('Uncaught exception: ' . $e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
        http_response_code(500);
        die('Произошла ошибка. Подробности в логах.');
    });
}

// Получаем $pdo для обратной совместимости (старый код использует $pdo напрямую)
$pdo = Database::getInstance()->getPdo();

// =======================================================================
// СТАРЫЕ ФУНКЦИИ (обратная совместимость со всеми pages/ и handlers/)
// =======================================================================

function requireAuth(): void {
    Session::getInstance()->requireAuth('index.php');
}

function getUserName(PDO $pdo, int $user_id): string {
    $stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['full_name'] : 'Неизвестно';
}

function hasPermission(PDO $pdo, string $permission_name): bool {
    if (!isset($_SESSION['user_id'])) return false;
    $userId = $_SESSION['user_id'];
    $sql = 'SELECT COUNT(*) FROM users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = :user_id AND p.name = :perm';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId, 'perm' => $permission_name]);
    return $stmt->fetchColumn() > 0;
}

function cacheGet(string $key, int $ttl = 300): mixed {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $file = $cacheDir . md5($key) . '.cache';
    if (file_exists($file) && (time() - filemtime($file) < $ttl)) {
        return unserialize(file_get_contents($file));
    }
    return null;
}

function cacheSet(string $key, mixed $data): void {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    file_put_contents($cacheDir . md5($key) . '.cache', serialize($data));
}

function cacheDelete(string $key): void {
    $file = __DIR__ . '/cache/' . md5($key) . '.cache';
    if (file_exists($file)) @unlink($file);
}

// =======================================================================
// НОВЫЕ ГЛОБАЛЬНЫЕ ХЕЛПЕРЫ
// =======================================================================

if (!function_exists('e')) {
    function e(mixed $value): string {
        return Security::getInstance()->escape($value);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return Security::getInstance()->csrfField();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return Security::getInstance()->generateCsrfToken();
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value = null): mixed {
        $s = Session::getInstance();
        if ($value !== null) {
            $s->flash($key, $value);
            return null;
        }
        return $s->getFlash($key);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $code = 302): never {
        header('Location: ' . $url, true, $code);
        exit;
    }
}

if (!function_exists('format_date')) {
    function format_date(string $date, string $format = 'd.m.Y'): string {
        $ts = strtotime($date);
        return $ts ? date($format, $ts) : $date;
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(string $datetime, string $format = 'd.m.Y H:i'): string {
        $ts = strtotime($datetime);
        return $ts ? date($format, $ts) : $datetime;
    }
}
