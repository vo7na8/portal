<?php
/**
 * Core\Database - Улучшенный класс для работы с БД
 * Поддерживает SQLite с защитой от SQL Injection
 */

namespace Core;

use PDO;
use PDOException;
use PDOStatement;

class Database {
    private static $instance = null;
    private $pdo;
    private $lastQuery;
    private $queryCount = 0;
    
    private function __construct() {
        $this->connect();
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
     * Подключение к базе данных
     */
    private function connect(): void {
        try {
            $config = Config::getInstance();
            $dbPath = $config->getDatabasePath();
            
            // Проверяем существование директории
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Включаем внешние ключи
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            
            // Оптимизация для SQLite
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
            $this->pdo->exec('PRAGMA synchronous = NORMAL;');
            $this->pdo->exec('PRAGMA cache_size = -64000;'); // 64MB cache
            $this->pdo->exec('PRAGMA temp_store = MEMORY;');
            
        } catch (PDOException $e) {
            Logger::getInstance()->error('Database connection failed: ' . $e->getMessage());
            die('Ошибка подключения к базе данных');
        }
    }

    /**
     * Получение PDO объекта
     */
    public function getPdo(): PDO {
        return $this->pdo;
    }

    /**
     * Выполнение запроса с параметрами
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $this->lastQuery = $sql;
            $this->queryCount++;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt;
        } catch (PDOException $e) {
            Logger::getInstance()->error('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Выборка всех строк
     */
    public function select(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Выборка одной строки
     */
    public function selectOne(string $sql, array $params = []) {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Выборка одного значения
     */
    public function selectValue(string $sql, array $params = []) {
        $result = $this->query($sql, $params)->fetchColumn();
        return $result !== false ? $result : null;
    }

    /**
     * Вставка данных
     */
    public function insert(string $table, array $data): int {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        $this->query($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Обновление данных
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = [];
        foreach (array_keys($data) as $field) {
            $setParts[] = "$field = ?";
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setParts),
            $where
        );
        
        $params = array_merge(array_values($data), $whereParams);
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Удаление данных
     */
    public function delete(string $table, string $where, array $whereParams = []): int {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $whereParams)->rowCount();
    }

    /**
     * Начало транзакции
     */
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    /**
     * Фиксация транзакции
     */
    public function commit(): bool {
        return $this->pdo->commit();
    }

    /**
     * Откат транзакции
     */
    public function rollback(): bool {
        return $this->pdo->rollBack();
    }

    /**
     * Проверка существования записи
     */
    public function exists(string $table, string $where, array $params = []): bool {
        $sql = "SELECT EXISTS(SELECT 1 FROM $table WHERE $where)";
        return (bool) $this->selectValue($sql, $params);
    }

    /**
     * Подсчет записей
     */
    public function count(string $table, string $where = '1', array $params = []): int {
        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        return (int) $this->selectValue($sql, $params);
    }

    /**
     * Получение последнего ID
     */
    public function lastInsertId(): int {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Получение последнего запроса
     */
    public function getLastQuery(): ?string {
        return $this->lastQuery;
    }

    /**
     * Получение количества запросов
     */
    public function getQueryCount(): int {
        return $this->queryCount;
    }

    /**
     * Оптимизация БД
     */
    public function optimize(): void {
        $this->pdo->exec('VACUUM;');
        $this->pdo->exec('ANALYZE;');
        $this->pdo->exec('REINDEX;');
        Logger::getInstance()->info('Database optimized');
    }

    /**
     * Резервное копирование БД
     */
    public function backup(string $destination): bool {
        try {
            $config = Config::getInstance();
            $source = $config->getDatabasePath();
            
            // Создаем директорию для резервных копий
            $backupDir = dirname($destination);
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0777, true);
            }
            
            // Копируем файл
            $result = copy($source, $destination);
            
            if ($result) {
                Logger::getInstance()->info("Database backed up to: $destination");
            }
            
            return $result;
        } catch (\Exception $e) {
            Logger::getInstance()->error('Backup failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверка существования таблицы
     */
    public function tableExists(string $table): bool {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
        return $this->selectValue($sql, [$table]) !== null;
    }
}

/**
 * Глобальный хелпер
 */
if (!function_exists('db')) {
    function db(): Database {
        return Database::getInstance();
    }
}
