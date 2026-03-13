<?php
/**
 * Core\Security - Система безопасности
 * CSRF, XSS, SQL Injection защита
 */

namespace Core;

class Security {
    private static $instance = null;
    
    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Генерация CSRF токена
     */
    public function generateCsrfToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Проверка CSRF токена
     */
    public function validateCsrfToken(string $token): bool {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Получение CSRF токена из запроса
     */
    public function getCsrfTokenFromRequest(): ?string {
        return $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    /**
     * Проверка CSRF для POST запросов
     */
    public function checkCsrf(): bool {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $this->getCsrfTokenFromRequest();
            
            if (!$token || !$this->validateCsrfToken($token)) {
                Logger::getInstance()->warning('CSRF token validation failed', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
                return false;
            }
        }
        return true;
    }

    /**
     * Требовать CSRF проверку (с остановкой скрипта)
     */
    public function requireCsrf(): void {
        if (!$this->checkCsrf()) {
            http_response_code(403);
            die('Недействительный CSRF токен');
        }
    }

    /**
     * HTML скрытое поле с CSRF токеном
     */
    public function csrfField(): string {
        $token = $this->generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * XSS фильтрация (экранирование HTML)
     */
    public function escape($value, $doubleEncode = true) {
        if (is_array($value)) {
            return array_map([$this, 'escape'], $value);
        }
        
        if ($value === null || is_bool($value)) {
            return $value;
        }
        
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8', $doubleEncode);
    }

    /**
     * Безопасный вывод HTML
     */
    public function e($value) {
        return $this->escape($value);
    }

    /**
     * Очистка строки от HTML тегов
     */
    public function stripTags(string $value, string $allowedTags = ''): string {
        return strip_tags($value, $allowedTags);
    }

    /**
     * Валидация email
     */
    public function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Валидация URL
     */
    public function validateUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Валидация IP
     */
    public function validateIp(string $ip): bool {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Санитайзация имени файла
     */
    public function sanitizeFilename(string $filename): string {
        // Удаляем опасные символы
        $filename = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $filename);
        
        // Предотвращаем directory traversal
        $filename = str_replace(['../', '..\\'], '', $filename);
        
        return $filename;
    }

    /**
     * Генерация безопасного случайного токена
     */
    public function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }

    /**
     * Хеширование пароля
     */
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Проверка пароля
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Проверка сложности пароля
     */
    public function validatePasswordStrength(string $password): array {
        $errors = [];
        $minLength = (int) config('PASSWORD_MIN_LENGTH', 8);
        
        if (strlen($password) < $minLength) {
            $errors[] = "Пароль должен быть не менее $minLength символов";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Пароль должен содержать строчные буквы';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Пароль должен содержать заглавные буквы';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Пароль должен содержать цифры';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Ограничение попыток входа (Rate Limiting)
     */
    public function checkRateLimit(string $identifier, int $maxAttempts = 5, int $timeWindow = 300): array {
        $cacheKey = "rate_limit_$identifier";
        $attempts = Session::getInstance()->get($cacheKey, []);
        
        // Очищаем старые попытки
        $cutoff = time() - $timeWindow;
        $attempts = array_filter($attempts, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
        
        $attemptsCount = count($attempts);
        $allowed = $attemptsCount < $maxAttempts;
        
        if (!$allowed) {
            $oldestAttempt = min($attempts);
            $waitTime = ($oldestAttempt + $timeWindow) - time();
        } else {
            $waitTime = 0;
        }
        
        return [
            'allowed' => $allowed,
            'attempts' => $attemptsCount,
            'max_attempts' => $maxAttempts,
            'wait_time' => max(0, $waitTime),
            'reset_at' => $allowed ? null : date('Y-m-d H:i:s', time() + $waitTime)
        ];
    }

    /**
     * Регистрация попытки входа
     */
    public function recordLoginAttempt(string $identifier): void {
        $cacheKey = "rate_limit_$identifier";
        $attempts = Session::getInstance()->get($cacheKey, []);
        $attempts[] = time();
        Session::getInstance()->set($cacheKey, $attempts);
    }

    /**
     * Сброс попыток входа
     */
    public function resetLoginAttempts(string $identifier): void {
        $cacheKey = "rate_limit_$identifier";
        Session::getInstance()->remove($cacheKey);
    }

    /**
     * Получение IP адреса пользователя
     */
    public function getClientIp(): string {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Если несколько IP, берем первый
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if ($this->validateIp($ip)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }

    /**
     * Проверка User-Agent
     */
    public function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}

/**
 * Глобальные хелперы
 */
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

if (!function_exists('e')) {
    function e($value) {
        return Security::getInstance()->escape($value);
    }
}
