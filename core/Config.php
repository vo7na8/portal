<?php
/**
 * Core\Config - Централизованное управление конфигурацией
 * Загружает .env файл и предоставляет доступ к параметрам
 */

namespace Core;

class Config {
    private static $instance = null;
    private $config = [];
    private $envLoaded = false;

    private function __construct() {
        $this->loadEnv();
        $this->setDefaults();
    }

    /**
     * Singleton pattern
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Загрузка .env файла
     */
    private function loadEnv(): void {
        $envFile = dirname(__DIR__) . '/.env';
        
        if (!file_exists($envFile)) {
            // Если .env не найден, используем значения по умолчанию
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Пропускаем комментарии
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Парсим KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Удаляем кавычки
                $value = trim($value, '"\'");
                
                // Сохраняем в $_ENV и внутренний массив
                $_ENV[$key] = $value;
                putenv("$key=$value");
                $this->config[$key] = $value;
            }
        }
        
        $this->envLoaded = true;
    }

    /**
     * Установка значений по умолчанию
     */
    private function setDefaults(): void {
        $defaults = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_NAME' => 'Корпоративный Портал',
            'APP_URL' => 'http://localhost',
            
            'DB_TYPE' => 'sqlite',
            'DB_PATH' => 'data/portal.db',
            
            'SESSION_LIFETIME' => '7200',
            'CSRF_TOKEN_NAME' => 'csrf_token',
            'PASSWORD_MIN_LENGTH' => '8',
            
            'CACHE_ENABLED' => 'true',
            'CACHE_TTL' => '300',
            'CACHE_DIR' => 'cache',
            
            'UPLOAD_MAX_SIZE' => '5242880', // 5MB
            'UPLOAD_ALLOWED_TYPES' => 'csv,txt,pdf,doc,docx,xls,xlsx',
            'UPLOAD_DIR' => 'uploads',
            
            'LOG_ENABLED' => 'true',
            'LOG_LEVEL' => 'error',
            'LOG_DIR' => 'logs',
            
            'MAIL_ENABLED' => 'false',
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($this->config[$key])) {
                $this->config[$key] = $value;
            }
        }
    }

    /**
     * Получение значения параметра
     * 
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    /**
     * Установка значения параметра
     */
    public function set(string $key, $value): void {
        $this->config[$key] = $value;
    }

    /**
     * Проверка наличия параметра
     */
    public function has(string $key): bool {
        return isset($this->config[$key]);
    }

    /**
     * Получение всех параметров
     */
    public function all(): array {
        return $this->config;
    }

    /**
     * Проверка режима разработки
     */
    public function isDevelopment(): bool {
        return $this->get('APP_ENV') === 'development';
    }

    /**
     * Проверка режима production
     */
    public function isProduction(): bool {
        return $this->get('APP_ENV') === 'production';
    }

    /**
     * Проверка режима отладки
     */
    public function isDebug(): bool {
        return $this->get('APP_DEBUG') === 'true';
    }

    /**
     * Получение пути к БД
     */
    public function getDatabasePath(): string {
        $path = $this->get('DB_PATH');
        if (!str_starts_with($path, '/')) {
            $path = dirname(__DIR__) . '/' . $path;
        }
        return $path;
    }

    /**
     * Получение пути к директории
     */
    public function getPath(string $type): string {
        $paths = [
            'root' => dirname(__DIR__),
            'cache' => dirname(__DIR__) . '/' . $this->get('CACHE_DIR'),
            'logs' => dirname(__DIR__) . '/' . $this->get('LOG_DIR'),
            'uploads' => dirname(__DIR__) . '/' . $this->get('UPLOAD_DIR'),
            'data' => dirname(__DIR__) . '/data',
        ];
        
        return $paths[$type] ?? dirname(__DIR__);
    }
}

/**
 * Глобальные хелперы
 */

if (!function_exists('config')) {
    /**
     * Быстрый доступ к конфигурации
     */
    function config(string $key = null, $default = null) {
        $config = \Core\Config::getInstance();
        
        if ($key === null) {
            return $config;
        }
        
        return $config->get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * Получение переменной окружения
     */
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
