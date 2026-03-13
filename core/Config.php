<?php
/**
 * Core\Config — централизованная конфигурация из .env
 * Singleton. Читает .env один раз при первом обращении.
 */
class Config
{
    private static ?Config $instance = null;
    private array $data = [];

    private function __construct()
    {
        $this->load();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load(): void
    {
        $envFile = dirname(__DIR__) . '/.env';

        // Дефолтные значения
        $defaults = [
            'APP_ENV'                      => 'production',
            'APP_DEBUG'                    => 'false',
            'APP_NAME'                     => 'Корпоративный Портал',
            'APP_TIMEZONE'                 => 'Europe/Moscow',
            'DB_PATH'                      => 'data/portal.db',
            'SESSION_LIFETIME'             => '7200',
            'SESSION_NAME'                 => 'PORTAL_SESSION',
            'SESSION_STRICT'               => 'true',
            'SECURITY_CSRF_ENABLED'        => 'true',
            'SECURITY_MAX_UPLOAD_SIZE'     => '10',
            'SECURITY_ALLOWED_EXTENSIONS'  => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,csv,txt',
            'CACHE_ENABLED'                => 'true',
            'CACHE_DEFAULT_TTL'            => '300',
            'CACHE_PATH'                   => 'cache',
            'LOG_LEVEL'                    => 'error',
            'LOG_PATH'                     => 'logs',
            'UPLOAD_PATH'                  => 'uploads',
        ];

        $this->data = $defaults;

        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"\'`");
            if ($key !== '') {
                $this->data[$key] = $value;
            }
        }

        // Устанавливаем временную зону
        date_default_timezone_set($this->get('APP_TIMEZONE', 'Europe/Moscow'));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $val = strtolower($this->get($key, $default ? 'true' : 'false'));
        return in_array($val, ['true', '1', 'yes', 'on'], true);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int)($this->data[$key] ?? $default);
    }

    public function isDebug(): bool
    {
        return $this->getBool('APP_DEBUG');
    }

    public function isDevelopment(): bool
    {
        return $this->get('APP_ENV') === 'development';
    }

    public function isProduction(): bool
    {
        return $this->get('APP_ENV') === 'production';
    }

    public function getAppName(): string
    {
        return $this->get('APP_NAME', 'Портал');
    }
}
