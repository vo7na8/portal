<?php
/**
 * Core\Session — управление сессией
 * Flash-сообщения, безопасные настройки cookie.
 */
class Session
{
    private static ?Session $instance = null;

    private function __construct()
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return; // Сессия уже запущена
        }

        $config = Config::getInstance();

        $lifetime = $config->getInt('SESSION_LIFETIME', 7200);
        $name     = $config->get('SESSION_NAME', 'PORTAL_SESSION');
        $strict   = $config->getBool('SESSION_STRICT', true);
        $secure   = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_name($name);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        if ($strict) {
            ini_set('session.use_strict_mode', '1');
        }

        ini_set('session.gc_maxlifetime', (string)$lifetime);
        ini_set('session.use_only_cookies', '1');

        session_start();

        // Регенерация ID при первом старте для защиты от session fixation
        if (!isset($_SESSION['_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['_initiated'] = true;
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // --- Базовые операции ---

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // --- Flash-сообщения ---

    /**
     * Установить flash (будет доступно только на следующей странице)
     */
    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Прочитать и удалить flash
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    /**
     * Получить все flash сразу и очистить
     */
    public function getAllFlash(): array
    {
        $all = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $all;
    }

    // --- Завершение сессии ---

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // --- Утилиты ---

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
    }

    public function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public function requireAuth(string $redirect = 'index.php'): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: ' . $redirect);
            exit;
        }
    }
}
