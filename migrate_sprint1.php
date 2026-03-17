<?php
/**
 * Миграция Sprint 1 — Система шаблонов заявок и НСИ
 * Запустите один раз в браузере: /portal/migrate_sprint1.php
 * После успешного выполнения УДАЛИТЕ этот файл.
 */
require_once __DIR__ . '/config.php';

// Только для администраторов
if (!hasPermission($pdo, 'manage_roles')) {
    die('Доступ запрещён. Требуется роль администратора.');
}

$db = Database::getInstance();
$errors = [];
$done   = [];

try {
    $pdo->exec('PRAGMA foreign_keys = ON');

    // ----------------------------------------------------------------
    // 1. Справочники НСИ
    // ----------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS nsi_dictionaries (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        code        VARCHAR(50)  NOT NULL UNIQUE,
        name        VARCHAR(255) NOT NULL,
        description TEXT,
        is_active   INTEGER NOT NULL DEFAULT 1,
        sort_order  INTEGER NOT NULL DEFAULT 0,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $done[] = 'nsi_dictionaries';

    $pdo->exec("CREATE TABLE IF NOT EXISTS nsi_values (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        dictionary_id  INTEGER NOT NULL,
        value          VARCHAR(255) NOT NULL,
        display_text   VARCHAR(255) NOT NULL,
        parent_id      INTEGER,
        sort_order     INTEGER NOT NULL DEFAULT 0,
        is_active      INTEGER NOT NULL DEFAULT 1,
        metadata       TEXT,
        FOREIGN KEY (dictionary_id) REFERENCES nsi_dictionaries(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id)     REFERENCES nsi_values(id)
    )");
    $done[] = 'nsi_values';

    // Индексы НСИ
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_nsi_values_dict ON nsi_values(dictionary_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_nsi_values_active ON nsi_values(dictionary_id, is_active)');

    // ----------------------------------------------------------------
    // 2. Шаблоны заявок
    // ----------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS request_templates (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        category_code  VARCHAR(10)  NOT NULL UNIQUE,
        category_name  VARCHAR(255) NOT NULL,
        title_template VARCHAR(500),
        icon           VARCHAR(50)  DEFAULT 'fa-file-alt',
        description    TEXT,
        sort_order     INTEGER NOT NULL DEFAULT 0,
        is_active      INTEGER NOT NULL DEFAULT 1,
        created_by     INTEGER,
        created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
    $done[] = 'request_templates';

    $pdo->exec("CREATE TABLE IF NOT EXISTS template_fields (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        template_id     INTEGER NOT NULL,
        field_name      VARCHAR(100) NOT NULL,
        field_type      VARCHAR(50)  NOT NULL DEFAULT 'text',
        field_label     VARCHAR(255) NOT NULL,
        placeholder     VARCHAR(255),
        is_required     INTEGER NOT NULL DEFAULT 0,
        sort_order      INTEGER NOT NULL DEFAULT 0,
        options_source  VARCHAR(100),
        options_static  TEXT,
        validation_json TEXT,
        help_text       VARCHAR(500),
        FOREIGN KEY (template_id) REFERENCES request_templates(id) ON DELETE CASCADE
    )");
    $done[] = 'template_fields';

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_template_fields_tpl ON template_fields(template_id, sort_order)');

    // ----------------------------------------------------------------
    // 3. Расширение таблицы requests
    // ----------------------------------------------------------------
    // Добавляем поля, только если их ещё нет
    $cols = array_column($pdo->query('PRAGMA table_info(requests)')->fetchAll(PDO::FETCH_ASSOC), 'name');

    if (!in_array('request_number', $cols)) {
        $pdo->exec('ALTER TABLE requests ADD COLUMN request_number VARCHAR(20)');
        $done[] = 'requests.request_number';
    }
    if (!in_array('template_id', $cols)) {
        $pdo->exec('ALTER TABLE requests ADD COLUMN template_id INTEGER REFERENCES request_templates(id)');
        $done[] = 'requests.template_id';
    }
    if (!in_array('updated_at', $cols)) {
        $pdo->exec('ALTER TABLE requests ADD COLUMN updated_at DATETIME');
        $done[] = 'requests.updated_at';
    }

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_requests_number  ON requests(request_number)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_requests_template ON requests(template_id)');

    // ----------------------------------------------------------------
    // 4. Значения полей заявки
    // ----------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS request_field_values (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        request_id INTEGER NOT NULL,
        field_id   INTEGER NOT NULL,
        value      TEXT,
        UNIQUE(request_id, field_id),
        FOREIGN KEY (request_id) REFERENCES requests(id)   ON DELETE CASCADE,
        FOREIGN KEY (field_id)   REFERENCES template_fields(id)
    )");
    $done[] = 'request_field_values';

    // ----------------------------------------------------------------
    // 5. Отслеживание прочтения (Sprint 5 — таблица создаётся сейчас)
    // ----------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS request_read_status (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        request_id   INTEGER NOT NULL,
        user_id      INTEGER NOT NULL,
        last_read_at DATETIME NOT NULL,
        UNIQUE(request_id, user_id),
        FOREIGN KEY (request_id) REFERENCES requests(id)  ON DELETE CASCADE,
        FOREIGN KEY (user_id)    REFERENCES users(id)      ON DELETE CASCADE
    )");
    $done[] = 'request_read_status';

    // ----------------------------------------------------------------
    // 6. Права доступа к категориям заявок
    // ----------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_template_permissions (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        role_id     INTEGER NOT NULL,
        template_id INTEGER NOT NULL,
        can_view    INTEGER NOT NULL DEFAULT 1,
        can_manage  INTEGER NOT NULL DEFAULT 0,
        UNIQUE(role_id, template_id),
        FOREIGN KEY (role_id)     REFERENCES roles(id)              ON DELETE CASCADE,
        FOREIGN KEY (template_id) REFERENCES request_templates(id)  ON DELETE CASCADE
    )");
    $done[] = 'role_template_permissions';

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_template_permissions (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id     INTEGER NOT NULL,
        template_id INTEGER NOT NULL,
        can_view    INTEGER NOT NULL DEFAULT 1,
        can_manage  INTEGER NOT NULL DEFAULT 0,
        UNIQUE(user_id, template_id),
        FOREIGN KEY (user_id)     REFERENCES users(id)               ON DELETE CASCADE,
        FOREIGN KEY (template_id) REFERENCES request_templates(id)   ON DELETE CASCADE
    )");
    $done[] = 'user_template_permissions';

    // ----------------------------------------------------------------
    // 7. Новые permissions
    // fix: таблица permissions может не содержать колонку description — добавляем при необходимости
    // ----------------------------------------------------------------
    $permCols = array_column(
        $pdo->query('PRAGMA table_info(permissions)')->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );
    if (!in_array('description', $permCols)) {
        $pdo->exec('ALTER TABLE permissions ADD COLUMN description TEXT');
        $done[] = 'permissions.description (добавлена колонка)';
    }

    $newPerms = [
        ['manage_templates',     'Управление шаблонами заявок'],
        ['view_nsi',             'Просмотр справочников НСИ'],
        ['manage_nsi',           'Управление справочниками НСИ'],
        ['view_all_requests',    'Видеть все заявки (без фильтра)'],
        ['manage_request_access','Управление доступом к категориям заявок'],
    ];
    // fix: используем prepare/execute вместо интерполяции строки в SQL
    $stmtIns = $pdo->prepare('INSERT OR IGNORE INTO permissions (name, description) VALUES (?, ?)');
    foreach ($newPerms as [$name, $desc]) {
        $stmtIns->execute([$name, $desc]);
    }
    $done[] = 'permissions (5 новых)';

    // Выдаём все новые права администратору автоматически
    $adminRole = $pdo->query("SELECT id FROM roles WHERE LOWER(name)='администратор' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($adminRole) {
        $rid = (int)$adminRole['id'];
        $stmtPid = $pdo->prepare('SELECT id FROM permissions WHERE name = ? LIMIT 1');
        foreach ($newPerms as [$name]) {
            $stmtPid->execute([$name]);
            $pid = $stmtPid->fetchColumn();
            if ($pid) {
                $pdo->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES ($rid, $pid)");
            }
        }
        $done[] = 'Права выданы роли Администратор';
    }

    // ----------------------------------------------------------------
    // 8. Предзаполненные данные НСИ
    // ----------------------------------------------------------------
    // Справочник: Типы техники (единый с модулем Equipment)
    $pdo->exec("INSERT OR IGNORE INTO nsi_dictionaries (code, name, description, sort_order)
        VALUES ('equipment_types', 'Типы техники', 'Используется в заявках и учёте техники', 1)");
    $etId = $pdo->query("SELECT id FROM nsi_dictionaries WHERE code='equipment_types'")->fetchColumn();
    $equipTypes = ['ПК', 'Моноблок', 'Принтер', 'Планшет', 'Ноутбук', 'Монитор', 'МФУ', 'Сканер', 'Проектор', 'ИБП'];
    $stmtV = $pdo->prepare('INSERT OR IGNORE INTO nsi_values (dictionary_id, value, display_text, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($equipTypes as $i => $et) {
        $stmtV->execute([$etId, mb_strtolower(str_replace(' ', '_', $et)), $et, $i + 1]);
    }
    $done[] = 'НСИ: Типы техники (' . count($equipTypes) . ')';

    // Справочник: Приоритеты заявок
    $pdo->exec("INSERT OR IGNORE INTO nsi_dictionaries (code, name, description, sort_order)
        VALUES ('request_priorities', 'Приоритеты заявок', 'Уровни срочности обращений', 2)");
    $prId = $pdo->query("SELECT id FROM nsi_dictionaries WHERE code='request_priorities'")->fetchColumn();
    $priorities = [
        ['low',      'Низкий'],
        ['normal',   'Средний'],
        ['high',     'Высокий'],
        ['critical', 'Критический'],
    ];
    foreach ($priorities as $i => [$val, $disp]) {
        $stmtV->execute([$prId, $val, $disp, $i + 1]);
    }
    $done[] = 'НСИ: Приоритеты заявок (4)';

    // ----------------------------------------------------------------
    // 9. Базовый шаблон «Общая заявка» для старых записей
    // ----------------------------------------------------------------
    $pdo->exec("INSERT OR IGNORE INTO request_templates
        (category_code, category_name, title_template, icon, description, sort_order)
        VALUES ('GN', 'Общая заявка', '{title}', 'fa-file-alt', 'Шаблон для произвольных заявок', 0)");

    // Шаблон «Поломка техники»
    $pdo->exec("INSERT OR IGNORE INTO request_templates
        (category_code, category_name, title_template, icon, description, sort_order)
        VALUES ('PT', 'Поломка техники', 'Поломка: {equipment_type} — {equipment_name}', 'fa-screwdriver-wrench', 'Заявка на ремонт или замену оборудования', 1)");

    // Поля шаблона «Поломка техники»
    $ptId = $pdo->query("SELECT id FROM request_templates WHERE category_code='PT'")->fetchColumn();
    if ($ptId) {
        $fields = [
            ['description',    'textarea', 'Описание проблемы',   '',                    1, 1,  null,              null,                           null,           'Опишите подробно, что произошло'],
            ['equipment_type', 'select',   'Тип техники',         '',                    1, 2,  'equipment_types', null,                           null,           null],
            ['equipment_name', 'select',   'Наименование техники','',                    1, 3,  'department_equipment', null,                      null,           'Выберите технику, закреплённую за вашим отделом'],
        ];
        $stmtF = $pdo->prepare('INSERT OR IGNORE INTO template_fields
            (template_id, field_name, field_type, field_label, placeholder, is_required, sort_order, options_source, options_static, validation_json, help_text)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($fields as $f) {
            $stmtF->execute(array_merge([$ptId], $f));
        }
    }
    $done[] = 'Шаблон: Поломка техники + поля';

    // Шаблон «ЕМИАС»
    $pdo->exec("INSERT OR IGNORE INTO request_templates
        (category_code, category_name, title_template, icon, description, sort_order)
        VALUES ('EM', 'ЕМИАС', 'ЕМИАС: {description}', 'fa-laptop-medical', 'Обращения по системе ЕМИАС', 2)");
    $emId = $pdo->query("SELECT id FROM request_templates WHERE category_code='EM'")->fetchColumn();
    if ($emId) {
        $stmtF = $pdo->prepare('INSERT OR IGNORE INTO template_fields
            (template_id, field_name, field_type, field_label, placeholder, is_required, sort_order, options_source, options_static, validation_json, help_text)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmtF->execute([$emId, 'description',      'textarea', 'Описание проблемы',  '',              1, 1, null, null, null, null]);
        $stmtF->execute([$emId, 'assistant_number', 'text',     'Номер Ассистент',    '000 000 000',   1, 2, null, null,
            json_encode(['pattern' => '^\d{3} \d{3} \d{3}$', 'pattern_message' => 'Формат: nnn nnn nnn']),
            'Номер обращения в системе Ассистент в формате «nnn nnn nnn»']);
    }
    $done[] = 'Шаблон: ЕМИАС + поля';

    // ----------------------------------------------------------------
    // 10. Привязать существующие заявки к базовому шаблону GN
    // ----------------------------------------------------------------
    $gnId = $pdo->query("SELECT id FROM request_templates WHERE category_code='GN'")->fetchColumn();
    if ($gnId) {
        $pdo->exec("UPDATE requests SET
            template_id    = $gnId,
            request_number = 'GN-' || printf('%05d', id)
        WHERE template_id IS NULL");
        $migrated = $pdo->query('SELECT changes()')->fetchColumn();
        $done[] = "Перенесено старых заявок в GN: $migrated";
    }

    // ----------------------------------------------------------------
    // Готово
    // ----------------------------------------------------------------
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8">';
    echo '<title>Миграция Sprint 1</title>';
    echo '<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:2rem;}</style></head><body>';
    echo '<h2 style="color:#7ecfb3">✅ Миграция Sprint 1 выполнена успешно</h2><ul>';
    foreach ($done as $item) echo "<li>$item</li>";
    echo '</ul>';
    echo '<p style="color:#ffca7a">⚠️ Удалите этот файл после проверки: <code>rm migrate_sprint1.php</code></p>';
    echo '</body></html>';

} catch (\Throwable $e) {
    http_response_code(500);
    echo '<pre style="color:red">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
