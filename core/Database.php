<?php
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
        $dbDir = dirname($dbPath);
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
            die('Ошибка подключения к БД.');
        }
    }

    // ─────────────────────────────────────────────
    //  SCHEMA (fresh install)
    // ─────────────────────────────────────────────
    private function initSchema(): void
    {
        $this->pdo->exec($this->baseSchemaSql());
        $this->pdo->exec($this->orgSchemaSql());
        $this->seedData();
    }

    private function baseSchemaSql(): string
    {
        return "BEGIN;
        CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        );
        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
            permission_id INTEGER NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
            PRIMARY KEY (role_id, permission_id)
        );
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role_id INTEGER REFERENCES roles(id),
            person_id INTEGER REFERENCES persons(id) ON DELETE SET NULL,
            email TEXT, phone TEXT, birth_date TEXT, avatar TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS news (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL, body TEXT,
            author_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL, description TEXT,
            status TEXT NOT NULL DEFAULT 'новая',
            priority TEXT NOT NULL DEFAULT 'средний',
            author_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS request_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            request_id INTEGER NOT NULL REFERENCES requests(id) ON DELETE CASCADE,
            user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            body TEXT NOT NULL, is_log INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS equipment (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL, type TEXT, location TEXT,
            inventory_number TEXT,
            department_id INTEGER REFERENCES departments(id) ON DELETE SET NULL,
            responsible_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            status TEXT NOT NULL DEFAULT 'рабочее',
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS equipment_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            equipment_id INTEGER NOT NULL REFERENCES equipment(id) ON DELETE CASCADE,
            user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            body TEXT NOT NULL, is_log INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS vacations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            start_date TEXT NOT NULL, end_date TEXT NOT NULL, note TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS birthdays (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL, birth_date TEXT NOT NULL, note TEXT
        );
        CREATE TABLE IF NOT EXISTS links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL, url TEXT NOT NULL, category TEXT,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS security_incidents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL, description TEXT,
            severity TEXT NOT NULL DEFAULT 'низкая',
            author_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        COMMIT;";
    }

    private function orgSchemaSql(): string
    {
        return "BEGIN;
        -- Физические лица
        CREATE TABLE IF NOT EXISTS persons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            last_name TEXT NOT NULL,
            first_name TEXT NOT NULL,
            middle_name TEXT,
            birth_date TEXT,
            has_eds INTEGER NOT NULL DEFAULT 0,
            eds_cert_number TEXT,
            eds_valid_until TEXT,
            note TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        -- Подразделения (верхний уровень, с самоссылкой)
        CREATE TABLE IF NOT EXISTS divisions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            short_name TEXT,
            parent_id INTEGER REFERENCES divisions(id) ON DELETE SET NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        -- Отделения (входят в подразделение)
        CREATE TABLE IF NOT EXISTS departments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            short_name TEXT,
            division_id INTEGER REFERENCES divisions(id) ON DELETE SET NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        -- Должности сотрудников (один человек = несколько должностей)
        CREATE TABLE IF NOT EXISTS employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            person_id INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
            department_id INTEGER REFERENCES departments(id) ON DELETE SET NULL,
            position TEXT NOT NULL,
            contract_number TEXT,
            hire_date TEXT,
            fire_date TEXT,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        -- EAV: расширяемые атрибуты без ALTER TABLE
        CREATE TABLE IF NOT EXISTS entity_attributes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entity_type TEXT NOT NULL,
            entity_id INTEGER NOT NULL,
            attr_key TEXT NOT NULL,
            attr_value TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            UNIQUE(entity_type, entity_id, attr_key)
        );
        CREATE INDEX IF NOT EXISTS idx_ea_lookup ON entity_attributes(entity_type, entity_id);
        CREATE INDEX IF NOT EXISTS idx_employees_person ON employees(person_id);
        CREATE INDEX IF NOT EXISTS idx_employees_dept ON employees(department_id);
        CREATE INDEX IF NOT EXISTS idx_departments_div ON departments(division_id);
        COMMIT;";
    }

    // ─────────────────────────────────────────────
    //  MIGRATION (existing DB — only additive!)
    // ─────────────────────────────────────────────
    private function migrate(): void
    {
        // Базовые миграции
        $this->safeExec("CREATE TABLE IF NOT EXISTS request_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            request_id INTEGER NOT NULL REFERENCES requests(id) ON DELETE CASCADE,
            user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            body TEXT NOT NULL, is_log INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $this->safeAlter('equipment_comments', 'is_log', 'INTEGER NOT NULL DEFAULT 0');
        $this->safeAlter('equipment_comments', 'body',   'TEXT NOT NULL DEFAULT ""');

        // Оргструктура
        $this->safeExec("CREATE TABLE IF NOT EXISTS persons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            last_name TEXT NOT NULL, first_name TEXT NOT NULL, middle_name TEXT,
            birth_date TEXT,
            has_eds INTEGER NOT NULL DEFAULT 0,
            eds_cert_number TEXT, eds_valid_until TEXT,
            note TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $this->safeExec("CREATE TABLE IF NOT EXISTS divisions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL, short_name TEXT,
            parent_id INTEGER REFERENCES divisions(id) ON DELETE SET NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $this->safeExec("CREATE TABLE IF NOT EXISTS departments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL, short_name TEXT,
            division_id INTEGER REFERENCES divisions(id) ON DELETE SET NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $this->safeExec("CREATE TABLE IF NOT EXISTS employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            person_id INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
            department_id INTEGER REFERENCES departments(id) ON DELETE SET NULL,
            position TEXT NOT NULL,
            contract_number TEXT, hire_date TEXT, fire_date TEXT,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");
        $this->safeExec("CREATE TABLE IF NOT EXISTS entity_attributes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entity_type TEXT NOT NULL, entity_id INTEGER NOT NULL,
            attr_key TEXT NOT NULL, attr_value TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            UNIQUE(entity_type, entity_id, attr_key)
        )");
        $this->safeExec('CREATE INDEX IF NOT EXISTS idx_ea_lookup ON entity_attributes(entity_type, entity_id)');
        $this->safeExec('CREATE INDEX IF NOT EXISTS idx_employees_person ON employees(person_id)');
        $this->safeExec('CREATE INDEX IF NOT EXISTS idx_employees_dept ON employees(department_id)');
        $this->safeExec('CREATE INDEX IF NOT EXISTS idx_departments_div ON departments(division_id)');
        // Связь users -> persons
        $this->safeAlter('users', 'person_id', 'INTEGER REFERENCES persons(id) ON DELETE SET NULL');
        // Связь equipment -> departments
        $this->safeAlter('equipment', 'department_id', 'INTEGER REFERENCES departments(id) ON DELETE SET NULL');
        // Новые права
        $newPerms = [
            'view_persons','add_person','edit_person','delete_person',
            'view_structure','add_division','edit_division','delete_division',
            'add_department','edit_department','delete_department',
            'add_employee','edit_employee','delete_employee',
        ];
        foreach ($newPerms as $p) {
            $this->safeExec("INSERT OR IGNORE INTO permissions (name) VALUES (" . $this->pdo->quote($p) . ")");
        }
        // Администратор получает все новые права
        $adminRoleId = (int)$this->pdo->query("SELECT id FROM roles WHERE name='Администратор'")->fetchColumn();
        if ($adminRoleId) {
            $ids = $this->pdo->query('SELECT id FROM permissions')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($ids as $pid) {
                $this->safeExec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES ({$adminRoleId}, {$pid})");
            }
        }
    }

    private function safeExec(string $sql): void
    {
        try { $this->pdo->exec($sql); } catch (PDOException) {}
    }
    private function safeAlter(string $table, string $col, string $def): void
    {
        try { $this->pdo->exec("ALTER TABLE {$table} ADD COLUMN {$col} {$def}"); } catch (PDOException) {}
    }

    // ─────────────────────────────────────────────
    //  SEED
    // ─────────────────────────────────────────────
    private function seedData(): void
    {
        foreach (['Администратор', 'Менеджер', 'Сотрудник'] as $r) {
            $this->pdo->exec("INSERT OR IGNORE INTO roles (name) VALUES (" . $this->pdo->quote($r) . ")");
        }
        $adminId = (int)$this->pdo->query("SELECT id FROM roles WHERE name='Администратор'")->fetchColumn();
        $perms = [
            'view_dashboard','view_news','view_requests','view_equipment',
            'view_users','view_links','view_vacations','view_security','view_birthdays','manage_roles',
            'add_news','edit_news','delete_news',
            'add_request','edit_request','delete_request','take_request','reassign_request','complete_request',
            'add_equipment','edit_equipment','delete_equipment',
            'add_user','edit_user','delete_user',
            'add_link','edit_link','delete_link',
            'add_vacation','edit_vacation','delete_vacation',
            'add_security','edit_security','delete_security',
            'view_persons','add_person','edit_person','delete_person',
            'view_structure','add_division','edit_division','delete_division',
            'add_department','edit_department','delete_department',
            'add_employee','edit_employee','delete_employee',
        ];
        foreach ($perms as $p) {
            $this->pdo->exec("INSERT OR IGNORE INTO permissions (name) VALUES (" . $this->pdo->quote($p) . ")");
        }
        foreach ($this->pdo->query('SELECT id FROM permissions')->fetchAll(PDO::FETCH_COLUMN) as $pid) {
            $this->pdo->exec("INSERT OR IGNORE INTO role_permissions (role_id,permission_id) VALUES ({$adminId},{$pid})");
        }
        $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $this->pdo->exec("INSERT OR IGNORE INTO users (username,password_hash,full_name,role_id)
            VALUES ('admin'," . $this->pdo->quote($hash) . ",'Администратор',{$adminId})");
        Logger::getInstance()->info('Database initialized.');
    }

    // ─────────────────────────────────────────────
    //  EAV helpers
    // ─────────────────────────────────────────────
    public function getAttributes(string $type, int $id): array
    {
        $rows = $this->select(
            'SELECT attr_key, attr_value FROM entity_attributes WHERE entity_type=? AND entity_id=? ORDER BY attr_key',
            [$type, $id]
        );
        $out = [];
        foreach ($rows as $r) $out[$r['attr_key']] = $r['attr_value'];
        return $out;
    }
    public function setAttributes(string $type, int $id, array $attrs): void
    {
        foreach ($attrs as $key => $val) {
            $key = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($key)));
            if ($key === '') continue;
            if ($val === null || $val === '') {
                $this->delete('entity_attributes', 'entity_type=? AND entity_id=? AND attr_key=?', [$type, $id, $key]);
            } else {
                $this->execute(
                    'INSERT INTO entity_attributes (entity_type,entity_id,attr_key,attr_value) VALUES (?,?,?,?)
                     ON CONFLICT(entity_type,entity_id,attr_key) DO UPDATE SET attr_value=excluded.attr_value',
                    [$type, $id, $key, (string)$val]
                );
            }
        }
    }

    // ─────────────────────────────────────────────
    //  Singleton + PDO
    // ─────────────────────────────────────────────
    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    public function getPdo(): PDO { return $this->pdo; }

    // ─────────────────────────────────────────────
    //  Query helpers
    // ─────────────────────────────────────────────
    public function select(string $sql, array $p = []): array
    {
        $s = $this->pdo->prepare($sql); $s->execute($p); return $s->fetchAll();
    }
    public function selectOne(string $sql, array $p = []): ?array
    {
        $s = $this->pdo->prepare($sql); $s->execute($p); $r = $s->fetch(); return $r ?: null;
    }
    public function selectValue(string $sql, array $p = []): mixed
    {
        $s = $this->pdo->prepare($sql); $s->execute($p); return $s->fetchColumn();
    }
    public function insert(string $table, array $data): int
    {
        $cols = implode(',', array_keys($data));
        $ph   = implode(',', array_fill(0, count($data), '?'));
        $s    = $this->pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$ph})");
        $s->execute(array_values($data));
        return (int)$this->pdo->lastInsertId();
    }
    public function update(string $table, array $data, string $where, array $wp = []): int
    {
        $set = implode(',', array_map(fn($c) => "{$c}=?", array_keys($data)));
        $s   = $this->pdo->prepare("UPDATE {$table} SET {$set} WHERE {$where}");
        $s->execute([...array_values($data), ...$wp]);
        return $s->rowCount();
    }
    public function delete(string $table, string $where, array $wp = []): int
    {
        $s = $this->pdo->prepare("DELETE FROM {$table} WHERE {$where}");
        $s->execute($wp); return $s->rowCount();
    }
    public function execute(string $sql, array $p = []): PDOStatement
    {
        $s = $this->pdo->prepare($sql); $s->execute($p); return $s;
    }
    public function transaction(callable $cb): mixed
    {
        $this->pdo->beginTransaction();
        try { $r = $cb($this); $this->pdo->commit(); return $r; }
        catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
    }
}
