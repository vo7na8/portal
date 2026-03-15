# 🏢 Корпоративный Портал v2.0

> Модульная система управления корпоративными процессами для Synology NAS

[![Version](https://img.shields.io/badge/version-2.0.1-blue.svg)](https://github.com/vo7na8/portal)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## ✨ Что нового в v2.0

- 🏗️ **Модульная архитектура** - легко расширяемая система
- 🔐 **Улучшенная безопасность** - CSRF, XSS защита из коробки
- 📝 **Система логирования** - отслеживание всех действий
- ⚙️ **Конфигурация через .env** - гибкая настройка
- 🎯 **PSR-совместимость** - соответствие стандартам PHP
- 🚀 **Оптимизация** - кэширование, валидация, сессии
- 📚 **Обратная совместимость** - работает со старым кодом
- 🏢 **Организационная структура** - физлица, подразделения, должности

## 📋 Описание

Корпоративный портал - это веб-приложение для управления внутренними процессами организации:

- ✅ Система заявок (IT, HR, административные)
- 📰 Управление новостями
- 💻 Учет техники и оборудования
- 👥 Управление пользователями и ролями
- 🏖️ График отпусков
- 🎂 Дни рождения сотрудников
- 🔗 Полезные ссылки
- 🛡️ Информационная безопасность
- 🏢 Организационная структура (физлица, подразделения, отделения, должности)

## 🖥️ Системные требования

### Минимальные
- **PHP**: 8.0 или выше
- **SQLite**: 3.x
- **Web-сервер**: Apache 2.4+ или Nginx 1.18+
- **RAM**: 512 MB
- **Disk**: 200 MB

### Рекомендуемые (для Synology NAS)
- **Модель**: DS1522+ или выше
- **DSM**: 7.2.2 или выше
- **PHP**: 8.2
- **RAM**: 2 GB+

### PHP расширения
```
✓ PDO
✓ PDO_SQLite
✓ mbstring
✓ openssl
✓ fileinfo
✓ json
```

## 🚀 Быстрая установка

### 1. Клонирование репозитория
```bash
cd /volume1/web
git clone https://github.com/vo7na8/portal.git
cd portal
```

### 2. Настройка окружения
```bash
# Копируем пример конфигурации
cp .env.example .env

# Генерируем секретный ключ
openssl rand -hex 32

# Редактируем .env и вставляем ключ в APP_KEY
nano .env
```

**Минимальные настройки в .env:**
```ini
APP_ENV=production
APP_DEBUG=false
APP_KEY=your-generated-key-here
DB_PATH=data/portal.db
```

### 3. Создание директорий
```bash
# Создаем необходимые папки
mkdir -p logs uploads/temp
```

### 4. Установка прав доступа
```bash
# Для Synology NAS
sudo chown -R http:http /volume1/web/portal
sudo chmod -R 755 /volume1/web/portal
sudo chmod -R 777 /volume1/web/portal/cache
sudo chmod -R 777 /volume1/web/portal/logs
sudo chmod -R 777 /volume1/web/portal/uploads
sudo chmod -R 777 /volume1/web/portal/data
```

### 5. Инициализация базы данных
```bash
# Запустите в браузере:
http://your-nas-ip/portal/install.php

# После установки удалите файл:
rm install.php
```

### 6. Первый вход
```
URL: http://your-nas-ip/portal
Логин: admin
Пароль: admin123

⚠️ ВАЖНО: Смените пароль после первого входа!
```

## 📁 Архитектура v2.0

### Структура проекта
```
portal/
├── core/                   # 🎯 Ядро системы (новое!)
│   ├── Config.php         # Конфигурация
│   ├── Database.php       # PDO обёртка
│   ├── Security.php       # CSRF, XSS, валидация файлов
│   ├── Session.php        # Управление сессиями
│   ├── Logger.php         # Логирование
│   └── Validator.php      # Валидация данных
│
├── config.php             # 🔧 Главный конфиг (обновлен)
├── .env.example           # 📝 Пример конфигурации
├── .env                   # 🔐 Ваша конфигурация (не в git)
│
├── pages/                 # 📄 Страницы приложения
├── handlers/              # ⚙️ Обработчики форм
├── data/                  # 💾 База данных SQLite
├── cache/                 # 🗂️ Кэш
├── logs/                  # 📊 Логи (новое!)
├── uploads/               # 📁 Загруженные файлы
└── assets/                # 🎨 Статические файлы
```

### Новые компоненты

#### Config.php - Централизованная конфигурация
```php
// Singleton паттерн
$config = Config::getInstance();

// Получение значений
$debug = $config->isDebug();
$env = $config->get('app.env');
```

#### Database.php - Улучшенная работа с БД
```php
// Получение экземпляра
$db = Database::getInstance();

// Выполнение запросов
$user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$id]);
$users = $db->select("SELECT * FROM users WHERE active = ?", [1]);
```

#### Security.php - Защита приложения
```php
$security = Security::getInstance();

// CSRF защита
$token = $security->generateCsrfToken();
$security->verifyCsrf($_POST['csrf_token']);

// XSS фильтрация
$clean = $security->escape($userInput);
```

#### Logger.php - Логирование событий
```php
$logger = Logger::getInstance();

$logger->info('User logged in', ['user_id' => 1]);
$logger->error('Database error', ['error' => $e->getMessage()]);
$logger->warning('Failed login attempt', ['ip' => $_SERVER['REMOTE_ADDR']]);
```

#### Validator.php - Валидация данных
```php
$validator = Validator::make($_POST);

if ($validator->validate([
    'email' => 'required|email',
    'name' => 'required|min:3|max:100',
    'age' => 'numeric|min:18|max:100'
])) {
    $validated = $validator->validated();
} else {
    $errors = $validator->errors();
}
```

## 🏢 Организационная структура

### Иерархия данных

Система поддерживает полную иерархию организации:

```
Подразделения (divisions) — верхний уровень
├── Дочерние подразделения (divisions.parent_id)
└── Отделения (departments)
    └── Должности сотрудников (employees)
        └── Физические лица (persons)
            └── Учетные записи (users) — опционально
```

### Таблицы оргструктуры

#### persons — Физические лица

Реестр всех людей в организации (действующие + уволенные).

```sql
id            PK
last_name     Фамилия
first_name    Имя
middle_name   Отчество (необязательно)
birth_date    Дата рождения
has_eds       Наличие ЭЦП (0/1)
eds_cert_number  Номер сертификата
eds_valid_until  Срок действия ЭЦП
note          Примечания
```

#### divisions — Подразделения

Иерархическая структура с самоссылкой.

```sql
id          PK
name        Полное название
short_name  Краткое название
parent_id   FK → divisions.id (родительское подразделение, NULL = верхний уровень)
sort_order  Порядок сортировки
```

**Пример иерархии:**
```
«Главный офис»          (parent_id = NULL)
  └── «IT департамент»  (parent_id = 1)
        └── «Отдел разработки» (parent_id = 2)
  └── «Финансовый департамент» (parent_id = 1)
```

#### departments — Отделения

Входят в подразделения.

```sql
id           PK
name         Название
short_name   Краткое название
division_id  FK → divisions.id
sort_order   Порядок
```

#### employees — Должности

Один человек может иметь несколько должностей (основная работа + совместительство).

```sql
id               PK
person_id        FK → persons.id
department_id    FK → departments.id (NULL если без отделения)
position         Название должности
contract_number  Номер договора
hire_date        Дата приёма
fire_date        Дата увольнения (NULL если работает)
is_active        1 = работает, 0 = уволен
```

#### users → persons связь

```sql
users.person_id → persons.id  -- опциональная привязка аккаунта к физлицу
```

**Пример:** физлицо «Иванов Иван Иванович» может иметь должности
«Главный инженер» (Технический отдел) и «Зам. начальника» (Отдел эксплуатации, совместительство),
а также учётную запись `ivanov` в системе.

### Расширяемость (EAV)

Для добавления произвольных атрибутов без ALTER TABLE:

```php
// Сохранить атрибут
$db->setAttributes('person', $personId, [
    'passport_series' => '1234',
    'inn'             => '123456789012',
]);

// Получить атрибуты
$attrs = $db->getAttributes('person', $personId);
echo $attrs['inn']; // 123456789012
```

### Использование в коде

**Получить все должности человека:**
```php
$jobs = $db->select(
    'SELECT e.*, d.name AS dept_name, dv.name AS div_name
     FROM employees e
     LEFT JOIN departments d  ON d.id  = e.department_id
     LEFT JOIN divisions   dv ON dv.id = d.division_id
     WHERE e.person_id = ? AND e.is_active = 1',
    [$personId]
);
```

**Построить дерево подразделений:**
```php
function buildTree(array $items, $parentId = null): array {
    $branch = [];
    foreach ($items as $item) {
        if (($item['parent_id'] ?? null) === $parentId) {
            $item['children'] = buildTree($items, $item['id']);
            $branch[] = $item;
        }
    }
    return $branch;
}

$divisions = $db->select('SELECT * FROM divisions ORDER BY sort_order');
$tree = buildTree($divisions);
```

### Права доступа оргструктуры

```
view_persons    add_person    edit_person    delete_person
view_structure  add_division  edit_division  delete_division
                add_department edit_department delete_department
                add_employee  edit_employee  delete_employee
```

## 🔐 Безопасность

### Встроенная защита v2.0
- ✅ **CSRF токены** для всех форм (автоматически)
- ✅ **XSS фильтрация** всех выводов
- ✅ **SQL Injection** защита (Prepared Statements)
- ✅ **Валидация** всех входных данных
- ✅ **Хеширование паролей** (bcrypt/argon2)
- ✅ **Rate limiting** попыток входа
- ✅ **Логирование** всех действий
- ✅ **Безопасные сессии** (httponly, secure cookies)
- ✅ **Валидация файлов** (тип, размер, расширение)
- ✅ **Проверки уникальности** при создании записей
- ✅ **Защита от каскадного удаления** подразделений с дочерними элементами

### Добавление CSRF в формы

**Новый способ (рекомендуется):**
```php
<form method="post">
    <?= csrf_field() ?>
    <input type="text" name="title">
    <button>Отправить</button>
</form>
```

**В обработчике:**
```php
<?php
require_once __DIR__ . '/../auth.php';
Core\Security::getInstance()->requireCsrf();
// Остальной код...
```

### XSS защита в выводе

```php
// Безопасно:
<h1><?= e($news['title']) ?></h1>
```

## 📝 Конфигурация

### Файл .env (основной)

```ini
# Окружение
APP_ENV=production          # development, testing, production
APP_DEBUG=false             # Показывать ошибки
APP_KEY=secret-key-here     # Секретный ключ

# База данных
DB_PATH=data/portal.db

# Безопасность
SECURITY_CSRF_ENABLED=true
SECURITY_MAX_UPLOAD_SIZE=10     # MB

# Сессии
SESSION_LIFETIME=7200           # секунды
SESSION_STRICT=true

# Логирование
LOG_LEVEL=info                  # debug, info, warning, error
LOG_PATH=logs

# Кэш
CACHE_ENABLED=true
CACHE_DEFAULT_TTL=300
```

### Глобальные хелперы

```php
// База данных
$db = db();  // Database::getInstance()

// Конфигурация
$value = config('app.debug');

// Безопасность
$clean = e($input);           // Security::escape()
$token = csrf_token();
$html  = csrf_field();

// Редиректы
redirect('/page');
back();

// Получение данных
$title = post('title');      // $_POST['title']
$id    = get('id');          // $_GET['id']

// Flash сообщения
flash('success', 'Сохранено!');
$message = flash('success');

// Логирование
logger()->info('Event');

// Окружение
if (is_development()) { /* ... */ }
if (is_production())  { /* ... */ }

// Форматирование
format_date('2024-03-13');           // 13.03.2024
format_datetime('2024-03-13 14:30'); // 13.03.2024 14:30
```

## 🔄 Миграция со старой версии

**Смотрите полное руководство:** [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)

**Краткие шаги:**
1. Сделайте резервную копию
2. Обновите код из git
3. Создайте .env файл
4. Создайте директорию logs/
5. Проверьте права доступа
6. Постепенно добавляйте CSRF в формы

## 📊 Логирование

### Уровни логов
```php
logger()->debug('Debug info', ['data' => $var]);
logger()->info('User action', ['user_id' => 1]);
logger()->warning('Unusual activity');
logger()->error('Error occurred', ['trace' => $e]);
```

### Файлы логов
```
logs/
├── 2026-03-15.log
├── 2026-03-14.log
└── ...
```

### Просмотр логов
```bash
tail -f logs/$(date +%Y-%m-%d).log
grep ERROR logs/$(date +%Y-%m-%d).log
tail -n 100 logs/$(date +%Y-%m-%d).log
```

## 🐛 Отладка

### Режим разработки
```ini
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=debug
```

## 📈 Производительность

### Кэширование
```php
$data = cacheGet('key', 300);
if ($data === null) {
    $data = expensive_operation();
    cacheSet('key', $data);
}
```

### Оптимизация БД
```bash
sqlite3 data/portal.db "VACUUM;"
sqlite3 data/portal.db "ANALYZE;"
```

> **Совет по производительности:** В разделе «Люди» при большом числе записей
> используйте предварительную загрузку всех должностей одним запросом с группировкой
> по `person_id` в PHP — это устраняет N+1 проблему (201 запрос → 3).

## 🔄 Резервное копирование

### Автоматическое (cron)
```bash
0 2 * * * sqlite3 /volume1/web/portal/data/portal.db ".backup '/volume1/backups/portal_$(date +\%Y\%m\%d).db'"
```

### Ручное
```bash
sqlite3 data/portal.db ".backup 'data/portal_$(date +%Y%m%d).db'"
```

## 📞 Часто задаваемые вопросы

### Как сбросить пароль администратора?
```sql
sqlite3 data/portal.db
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin';
-- Новый пароль: password
```

### Ошибка "Permission denied"
```bash
sudo chmod -R 777 cache logs uploads data
sudo chown -R http:http /volume1/web/portal
```

### База данных заблокирована
```bash
sudo chmod 666 data/portal.db
sudo chown http:http data/portal.db
rm data/portal.db-shm data/portal.db-wal
```

### CSRF токен не работает
```php
// Убедитесь что в форме есть:
<?= csrf_field() ?>
// И в обработчике:
Core\Security::getInstance()->requireCsrf();
```

## 📝 Changelog

### [2.0.1] - 2026-03-15
- ✨ **Организационная структура** (новый модуль)
  - Физические лица (`persons`) с поддержкой ЭЦП
  - Иерархические подразделения (`divisions`) с `parent_id` (неограниченная вложенность)
  - Отделения (`departments`) внутри подразделений
  - Должности сотрудников (`employees`) — связь many-to-many
  - EAV система (`entity_attributes`) для расширяемых атрибутов
- 🔗 Связь `users ↔ persons` через `users.person_id`
- 🔗 Привязка `equipment → departments`
- 🔧 Inline редактирование должностей
- 🔧 Toggle active/inactive для увольнения и восстановления
- 🔧 Рекурсивное отображение иерархии подразделений (`buildTree` + `renderDivision`)
- 🔍 Поиск по ФИО в разделе «Люди» (LIKE по трём полям)
- 🛡️ 13 новых permissions для оргструктуры
- 🛡️ Проверки уникальности при создании физлиц, подразделений, отделений, должностей
- 🛡️ Защита от каскадного удаления подразделений с дочерними элементами
- 🛡️ Валидация дат (дата рождения не в будущем; дата увольнения не раньше приёма)
- 🐛 Fix #1: Отображение дочерних подразделений
- 🐛 Fix #2: Сохранение иконки при toggle
- 🐛 Fix #3: Форма редактирования должности
- 🐛 Fix #3.1: Привязка user → person

### [2.0.0] - 2026-03-13
- ✨ Модульная архитектура (core/)
- 🔐 CSRF и XSS защита
- 📝 Система логирования
- ⚙️ Конфигурация через .env
- 🎯 Валидатор данных
- 🔒 Улучшенные сессии
- 🚀 Оптимизация кода
- 📚 Обратная совместимость
- 📖 Расширенная документация

### [1.0.0] - 2025
- 🎉 Первый релиз

## 📚 Дополнительные ресурсы

- [Руководство по миграции](MIGRATION_GUIDE.md)
- [Рекомендации по безопасности](SECURITY.md)
- [История изменений](CHANGELOG.md)

## 📄 Лицензия

MIT License - свободно используйте для личных и корпоративных проектов.

## 👨‍💻 Автор

**vo7na8**
- GitHub: [@vo7na8](https://github.com/vo7na8)

## 🙏 Благодарности

- SQLite за надёжную встраиваемую БД
- Synology за отличные NAS системы
- PHP сообщество за стандарты PSR

---

**💡 Совет:** Начните с малого — добавьте CSRF в критичные формы, затем постепенно расширяйте использование новых компонентов.

**Сделано с ❤️ для корпоративной эффективности**
