<?php
/**
 * Core\Logger - Система логирования
 */

namespace Core;

class Logger {
    private static $instance = null;
    private $logDir;
    private $enabled;
    private $level;
    
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    private $levels = [
        self::DEBUG => 0,
        self::INFO => 1,
        self::NOTICE => 2,
        self::WARNING => 3,
        self::ERROR => 4,
        self::CRITICAL => 5,
        self::ALERT => 6,
        self::EMERGENCY => 7,
    ];

    private function __construct() {
        $config = Config::getInstance();
        $this->enabled = $config->get('LOG_ENABLED') === 'true';
        $this->level = $config->get('LOG_LEVEL', 'error');
        $this->logDir = $config->getPath('logs');
        
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function shouldLog(string $level): bool {
        if (!$this->enabled) {
            return false;
        }
        
        $currentLevel = $this->levels[$this->level] ?? 4;
        $messageLevel = $this->levels[$level] ?? 0;
        
        return $messageLevel >= $currentLevel;
    }

    private function log(string $level, string $message, array $context = []): void {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $user = $_SESSION['user_id'] ?? 'guest';
        
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[$timestamp] [$level] [IP:$ip] [User:$user] $message$contextStr" . PHP_EOL;
        
        $filename = $this->logDir . '/' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public function emergency(string $message, array $context = []): void {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void {
        $this->log(self::ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->log(self::ERROR, $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log(self::INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void {
        $this->log(self::DEBUG, $message, $context);
    }
}
