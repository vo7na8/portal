<?php
/**
 * Core\Session - Управление сессиями
 */

namespace Core;

class Session {
    private static $instance = null;
    private $started = false;

    private function __construct() {
        $this->start();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function start(): void {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        $config = Config::getInstance();
        
        // Безопасные настройки сессии
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
        
        session_name('PORTAL_SESSION');
        session_start();
        
        $this->started = true;
        
        // Проверка IP и User-Agent
        $this->validateSession();
    }

    private function validateSession(): void {
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (!isset($_SESSION['_ip'])) {
            $_SESSION['_ip'] = $currentIp;
            $_SESSION['_agent'] = $currentAgent;
        } else {
            // Проверяем IP и User-Agent
            if ($_SESSION['_ip'] !== $currentIp || $_SESSION['_agent'] !== $currentAgent) {
                $this->destroy();
                Logger::getInstance()->warning('Session hijacking attempt detected', [
                    'expected_ip' => $_SESSION['_ip'],
                    'actual_ip' => $currentIp
                ]);
            }
        }
    }

    public function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    public function destroy(): void {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        $this->started = false;
    }

    public function regenerate(): void {
        session_regenerate_id(true);
        $_SESSION['_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function flash(string $key, $value): void {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, $default = null) {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
