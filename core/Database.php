<?php
/**
 * Core\Database — PDO обёртка для SQLite
 * Singleton. Поддерживает транзакции, удобные методы select/insert/update/delete.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config  = Config::getInstance();
        $dbPath  = dirname(__DIR__) . '/' . $config->get('DB_PATH', 'data/portal.db');
        $dbDir   = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);

            // Оптимизация SQLite
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
            $this->pdo->exec('PRAGMA synchronous = NORMAL;');
            $this->pdo->exec('PRAGMA cache_size = -8000;'); // 8MB кэш
            $this->pdo->exec('PRAGMA temp_store = MEMORY;');
        } catch (PDOException $e) {
            Logger::getInstance()->error('Database connection failed', ['error' => $e->getMessage()]);
            die('Ошибка подключения к базе данных. Проверьте logs/.');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Выполнить запрос и вернуть все строки
     */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Выполнить запрос и вернуть одну строку
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Выполнить запрос и вернуть скалярное значение
     */
    public function selectValue(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * INSERT: вернуть lastInsertId
     */
    public function insert(string $table, array $data): int
    {
        $cols        = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql         = "INSERT INTO {$table} ({$cols}) VALUES ({$placeholders})";
        $stmt        = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * UPDATE по условию WHERE id = ?
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set  = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql  = "UPDATE {$table} SET {$set} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...array_values($data), ...$whereParams]);
        return $stmt->rowCount();
    }

    /**
     * DELETE
     */
    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql  = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($whereParams);
        return $stmt->rowCount();
    }

    /**
     * Выполнить произвольный SQL
     */
    public function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // --- Транзакции ---

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Транзакция через callback
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
}
