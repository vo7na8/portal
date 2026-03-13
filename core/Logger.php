<?php
/**
 * Core\Logger — файловое логирование
 * Пишет в logs/YYYY-MM-DD.log
 * Уровни: debug < info < warning < error
 */
class Logger
{
    private static ?Logger $instance = null;

    private string $logDir;
    private string $minLevel;
    private int    $maxSize;  // bytes
    private int    $maxFiles;

    private const LEVELS = [
        'debug'   => 0,
        'info'    => 1,
        'warning' => 2,
        'error'   => 3,
    ];

    private function __construct()
    {
        $config         = Config::getInstance();
        $this->logDir   = dirname(__DIR__) . '/' . $config->get('LOG_PATH', 'logs');
        $this->minLevel = strtolower($config->get('LOG_LEVEL', 'error'));
        $this->maxSize  = $config->getInt('LOG_MAX_SIZE', 10) * 1024 * 1024;
        $this->maxFiles = $config->getInt('LOG_MAX_FILES', 30);

        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $file = $this->logDir . '/' . date('Y-m-d') . '.log';

        // Ротация если файл слишком большой
        if (file_exists($file) && filesize($file) > $this->maxSize) {
            rename($file, $file . '.' . time() . '.bak');
            $this->cleanOldLogs();
        }

        $ip      = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $userId  = $_SESSION['user_id'] ?? '-';
        $ctx     = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $line    = sprintf(
            "[%s] [%s] [ip:%s] [uid:%s] %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $ip,
            $userId,
            $message,
            $ctx
        );

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private function shouldLog(string $level): bool
    {
        $minVal   = self::LEVELS[$this->minLevel] ?? 3;
        $levelVal = self::LEVELS[$level]           ?? 0;
        return $levelVal >= $minVal;
    }

    private function cleanOldLogs(): void
    {
        $files = glob($this->logDir . '/*.log');
        if (!$files || count($files) <= $this->maxFiles) {
            return;
        }
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));
        $toDelete = array_slice($files, 0, count($files) - $this->maxFiles);
        foreach ($toDelete as $f) {
            @unlink($f);
        }
    }
}
