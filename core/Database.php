<?php
/**
 * Core\Database — PDO обёртка для SQLite
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config  = Config::getInstance();
        $rawPath = $config->get('DB_PATH', 'data/portal.db');
        $dbPath  = (str_starts_with($rawPath, '/') || str_starts_with($rawPath, '\\'))
            ? $rawPath
            : dirname(__DIR__) . '/' . ltrim($rawPath, '/');
        $dbDir   = dirname($dbPath);
        if (!is_dir($dbDir)) mkdir($dbDir, 0755, true);

        $isNew = !file_exists($dbPath) || filesize($dbPath) === 0;
        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
            $this->pdo->exec('PRAGMA synchronous  = NORMAL;');
            $this->pdo->exec('PRAGMA cache_size   = -8000;');
            $this->pdo->exec('PRAGMA temp_store   = MEMORY;');
            if ($isNew) {
                $this->initSchema();
            } else {
                $this->migrate();
            }
        } catch (PDOException $e) {
            Logger::getInstance()->error('Database connection failed', ['error' => $e->getMessage()]);
            die('Ошибка подключения к БД. Проверьте logs/');
        }
    }

    // ===================== SCHEMA =====================
    private function initSchema(): void
    {
        $this->pdo->exec("BEGIN;

        CREATE TABLE IF NOT EXISTS roles (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS permissions (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        );
        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id       INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
            permission_id INTEGER NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
            PRIMARY KEY (role_id, permission_id)
        );
        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name     TEXT NOT NULL,
            role_id       INTEGER REFERENCES roles(id),
            email         TEXT,
            phone         TEXT,
            birth_date    TEXT,
            avatar        TEXT,
            created_at    TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS news (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            title      TEXT NOT NULL,
            body       TEXT,
            author_id  INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS requests (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT NOT NULL,
            description  TEXT,
            status       TEXT NOT NULL DEFAULT 'новая',
            priority     TEXT NOT NULL DEFAULT 'средний',
            author_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
            assigned_to  INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at   TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS request_comments (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            request_id INTEGER NOT NULL REFERENCES requests(id) ON DELETE CASCADE,
            user_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
            body       TEXT NOT NULL,
            is_log     INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS equipment (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            name             TEXT NOT NULL,
            type             TEXT,
            location         TEXT,
            inventory_number TEXT,
            responsible_id   INTEGER REFERENCES users(id) ON DELETE SET NULL,
            status           TEXT NOT NULL DEFAULT 'рабочее',
            created_at       TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS equipment_comments (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            equipment_id INTEGER NOT NULL REFERENCES equipment(id) ON DELETE CASCADE,
            user_id      INTEGER REFERENCES users(id) ON DELETE SET NULL,
            body         TEXT NOT NULL,
            is_log       INTEGER NOT NULL DEFAULT 0,
            created_at   TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS vacations (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            start_date TEXT NOT NULL,
            end_date   TEXT NOT NULL,
            note       TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS birthdays (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name  TEXT NOT NULL,
            birth_date TEXT NOT NULL,
            note       TEXT
        );
        CREATE TABLE IF NOT EXISTS links (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            title      TEXT NOT NULL,
            url        TEXT NOT NULL,
            category   TEXT,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS security_incidents (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT NOT NULL,
            description TEXT,
            severity    TEXT NOT NULL DEFAULT 'низкая',
            author_id   INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at  TEXT NOT NULL DEFAULT (datetime('now'))
        );

        COMMIT;");
        $this->seedData();
    }

    // ===================== MIGRATION (existing DB) =====================
    private function migrate(): void
    {
        // request_comments
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS request_comments (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            request_id INTEGER NOT NULL REFERENCES requests(id) ON DELETE CASCADE,
            user_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
            body       TEXT NOT NULL,
            is_log     INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        // equipment_comments.is_log
        try { $this->pdo->exec('ALTER TABLE equipment_comments ADD COLUMN is_log INTEGER NOT NULL DEFAULT 0'); } catch (PDOException) {}
        // equipment_comments.body (если вдруг старая схема)
        try { $this->pdo->exec('ALTER TABLE equipment_comments ADD COLUMN body TEXT NOT NULL DEFAULT ""'); } catch (PDOException) {}
    }

    // ===================== SEED =====================
    private function seedData(): void
    {
        $roles = ['Администратор', 'Менеджер', 'Сотрудник'];
        foreach ($roles as $r) {
            $this->pdo->exec("INSERT OR IGNORE INTO roles (name) VALUES (" . $this->pdo->quote($r) . ")");
        }
        $adminRoleId = (int)$this->pdo->query("SELECT id FROM roles WHERE name='Администратор'")->fetchColumn();

        $perms = [
            'view_dashboard','view_news','view_requests','view_equipment',
            'view_users','view_links','view_vacations','view_security',
            'view_birthdays','manage_roles',
            'add_news','edit_news','delete_news',
            'add_request','edit_request','delete_request','take_request','reassign_request','complete_request',
            'add_equipment','edit_equipment','delete_equipment',
            'add_user','edit_user','delete_user',
            'add_link','edit_link','delete_link',
            'add_vacation','edit_vacation','delete_vacation',
            'add_security','edit_security','delete_security',
        ];
        foreach ($perms as $p) {
            $this->pdo->exec("INSERT OR IGNORE INTO permissions (name) VALUES (" . $this->pdo->quote($p) . ")");
        }
        $allPermIds = $this->pdo->query('SELECT id FROM permissions')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($allPermIds as $pid) {
            $this->pdo->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES ({$adminRoleId}, {$pid})");
        }
        $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $this->pdo->exec("INSERT OR IGNORE INTO users (username, password_hash, full_name, role_id)
            VALUES ('admin', " . $this->pdo->quote($hash) . ", 'Администратор', {$adminRoleId})");
        Logger::getInstance()->info('Database schema initialized and seeded.');
    }

    // ===================== Singleton =====================
    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    public function getPdo(): PDO { return $this->pdo; }

    // ===================== QUERY METHODS =====================
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    public function selectValue(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    public function insert(string $table, array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $ph   = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$ph})");
        $stmt->execute(array_values($data));
        return (int)$this->pdo->lastInsertId();
    }
    public function update(string $table, array $data, string $where, array $wp = []): int
    {
        $set  = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data)));
        $stmt = $this->pdo->prepare("UPDATE {$table} SET {$set} WHERE {$where}");
        $stmt->execute([...array_values($data), ...$wp]);
        return $stmt->rowCount();
    }
    public function delete(string $table, string $where, array $wp = []): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE {$where}");
        $stmt->execute($wp);
        return $stmt->rowCount();
    }
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    public function beginTransaction(): void  { $this->pdo->beginTransaction(); }
    public function commit(): void            { $this->pdo->commit(); }
    public function rollback(): void          { $this->pdo->rollBack(); }
    public function transaction(callable $cb): mixed
    {
        $this->beginTransaction();
        try { $r = $cb($this); $this->commit(); return $r; }
        catch (Throwable $e) { $this->rollback(); throw $e; }
    }
}
