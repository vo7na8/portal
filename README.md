# 🏢 Корпоративный Портал v2.0

> Модульная система управления корпоративными процессами для Synology NAS

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/vo7na8/portal)
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
├── pages/                 # 📄 Старые страницы (совместимость)
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

### Добавление CSRF в формы

**Старый способ (все еще работает):**
```php
<form method="post">
    <input type="text" name="title">
    <button>Отправить</button>
</form>
```

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

// Проверка CSRF
Core\Security::getInstance()->requireCsrf();

// Остальной код...
```

### XSS защита в выводе

**Небезопасно:**
```php
<h1><?= $news['title'] ?></h1>
```

**Безопасно:**
```php
<h1><?= e($news['title']) ?></h1>
<!-- или -->
<h1><?= htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8') ?></h1>
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
$value = config('app.debug');  // Config::getInstance()->get(...)

// Безопасность
$clean = e($input);           // Security::escape()
$token = csrf_token();        // Генерация CSRF токена
$html = csrf_field();         // HTML поле с токеном

// Редиректы
redirect('/page');           // Редирект на страницу
back();                      // Вернуться назад

// Получение данных
$title = post('title');      // $_POST['title']
$id = get('id');            // $_GET['id']

// Flash сообщения
flash('success', 'Сохранено!');
$message = flash('success');  // Получить и удалить

// Логирование
logger()->info('Event');

// Окружение
if (is_development()) { /* ... */ }
if (is_production()) { /* ... */ }

// Форматирование
format_date('2024-03-13');         // 13.03.2024
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
logger()->debug('Debug info', ['data' => $var]);    // Отладка
logger()->info('User action', ['user_id' => 1]);     // Информация
logger()->warning('Unusual activity');                // Предупреждение
logger()->error('Error occurred', ['trace' => $e]);  // Ошибка
```

### Файлы логов
```
logs/
├── 2026-03-13.log         # Сегодняшние логи
├── 2026-03-12.log         # Вчерашние
└── ...
```

### Просмотр логов
```bash
# В реальном времени
tail -f logs/$(date +%Y-%m-%d).log

# Поиск ошибок
grep ERROR logs/$(date +%Y-%m-%d).log

# Последние 100 строк
tail -n 100 logs/$(date +%Y-%m-%d).log
```

## 🐛 Отладка

### Режим разработки
```ini
# .env
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=debug
```

### Вывод переменных
```php
// В development режиме
logger()->debug('Variable value', ['var' => $value]);

// Или старый добрый способ
var_dump($value); // Только в development!
```

## 📈 Производительность

### Кэширование (совместимость)
```php
// Старый способ (все еще работает)
$data = cacheGet('key', 300);
if ($data === null) {
    $data = expensive_operation();
    cacheSet('key', $data);
}
```

### Оптимизация БД
```bash
# SQLite оптимизация
sqlite3 data/portal.db "VACUUM;"
sqlite3 data/portal.db "ANALYZE;"
```

## 🔄 Резервное копирование

### Автоматическое (cron)
```bash
# Добавьте в Synology Task Scheduler
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
# Удалите WAL файлы
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

- SQLite за надежную встраиваемую БД
- Synology за отличные NAS системы
- PHP сообщество за стандарты PSR

---

**💡 Совет:** Начните с малого - добавьте CSRF в критичные формы, затем постепенно расширяйте использование новых компонентов.

**Сделано с ❤️ для корпоративной эффективности**
