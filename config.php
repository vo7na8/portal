<?php
/**
 * Главный конфигурационный файл
 * Обновленная версия с модульной архитектурой
 * Обратно совместим со старым кодом
 */

// Автозагрузчик классов
spl_autoload_register(function ($class) {
    $prefix = '';
    $base_dir = __DIR__ . '/';
    
    // Проверяем префикс namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Нет совпадения, пробуем загрузить
        $relative_class = str_replace('\\', '/', $class);
        $file = $base_dir . $relative_class . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Загружаем ядро системы
require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/Validator.php';

use Core\Config;
use Core\Database;
use Core\Security;
use Core\Session;
use Core\Logger;

// Инициализация компонентов
$config = Config::getInstance();
$session = Session::getInstance();
$security = Security::getInstance();
$logger = Logger::getInstance();

// Настройка отображения ошибок в зависимости от окружения
if ($config->isDevelopment() && $config->isDebug()) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    
    // Логируем ошибки в production
    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logger) {
        $logger->error("PHP Error: $errstr", [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ]);
        return true;
    });
}

// Получаем PDO объект для обратной совместимости
$pdo = Database::getInstance()->getPdo();

/**
 * ===========================================
 * ОБРАТНАЯ СОВМЕСТИМОСТЬ - Старые функции
 * ===========================================
 */

/**
 * Проверка авторизации
 * @deprecated Используйте Auth::requireAuth()
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Получение имени пользователя
 * @deprecated Используйте User модель
 */
function getUserName($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    return $row ? $row['full_name'] : 'Неизвестно';
}

/**
 * Проверка наличия разрешения
 * @deprecated Используйте Auth::hasPermission()
 */
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

/**
 * Получение данных из кэша
 * @deprecated Используйте Cache класс
 */
function cacheGet($key, $ttl = 300) {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
    
    $file = $cacheDir . md5($key) . '.cache';
    
    if (file_exists($file) && (time() - filemtime($file) < $ttl)) {
        return unserialize(file_get_contents($file));
    }
    
    return null;
}

/**
 * Сохранение данных в кэш
 * @deprecated Используйте Cache класс
 */
function cacheSet($key, $data) {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
    
    file_put_contents($cacheDir . md5($key) . '.cache', serialize($data));
}

/**
 * Удаление данных из кэша
 * @deprecated Используйте Cache класс
 */
function cacheDelete($key) {
    $file = __DIR__ . '/cache/' . md5($key) . '.cache';
    if (file_exists($file)) unlink($file);
}

/**
 * ===========================================
 * НОВЫЕ ГЛОБАЛЬНЫЕ ХЕЛПЕРЫ
 * ===========================================
 */

/**
 * Быстрый доступ к базе данных
 */
if (!function_exists('db')) {
    function db(): Database {
        return Database::getInstance();
    }
}

/**
 * Быстрый доступ к конфигурации
 */
if (!function_exists('config')) {
    function config(string $key = null, $default = null) {
        $config = Config::getInstance();
        return $key === null ? $config : $config->get($key, $default);
    }
}

/**
 * Безопасный вывод (XSS защита)
 */
if (!function_exists('e')) {
    function e($value) {
        return Security::getInstance()->escape($value);
    }
}

/**
 * Генерация CSRF токена
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return Security::getInstance()->generateCsrfToken();
    }
}

/**
 * HTML поле с CSRF токеном
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return Security::getInstance()->csrfField();
    }
}

/**
 * Редирект
 */
if (!function_exists('redirect')) {
    function redirect(string $url, int $code = 302): void {
        header("Location: $url", true, $code);
        exit;
    }
}

/**
 * Редирект назад
 */
if (!function_exists('back')) {
    function back(): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        redirect($referer);
    }
}

/**
 * Получение значения из $_POST с защитой
 */
if (!function_exists('post')) {
    function post(string $key, $default = null) {
        return $_POST[$key] ?? $default;
    }
}

/**
 * Получение значения из $_GET с защитой
 */
if (!function_exists('get')) {
    function get(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }
}

/**
 * Flash сообщение
 */
if (!function_exists('flash')) {
    function flash(string $key, $value = null) {
        $session = Session::getInstance();
        
        if ($value === null) {
            return $session->getFlash($key);
        }
        
        $session->flash($key, $value);
    }
}

/**
 * Логирование
 */
if (!function_exists('logger')) {
    function logger(): Logger {
        return Logger::getInstance();
    }
}

/**
 * Проверка окружения
 */
if (!function_exists('is_development')) {
    function is_development(): bool {
        return Config::getInstance()->isDevelopment();
    }
}

/**
 * Проверка production окружения
 */
if (!function_exists('is_production')) {
    function is_production(): bool {
        return Config::getInstance()->isProduction();
    }
}

/**
 * Форматирование даты
 */
if (!function_exists('format_date')) {
    function format_date(string $date, string $format = 'd.m.Y'): string {
        return date($format, strtotime($date));
    }
}

/**
 * Форматирование даты и времени
 */
if (!function_exists('format_datetime')) {
    function format_datetime(string $datetime, string $format = 'd.m.Y H:i'): string {
        return date($format, strtotime($datetime));
    }
}
