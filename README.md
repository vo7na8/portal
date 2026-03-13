# 🏢 Корпоративный Портал

> Модульная система управления корпоративными процессами для Synology NAS

## 📋 Описание

Корпоративный портал - это веб-приложение для управления внутренними процессами организации:
- Система заявок (IT, HR, административные)
- Управление новостями
- Учет техники и оборудования
- Управление пользователями и ролями
- График отпусков
- Дни рождения сотрудников
- Полезные ссылки
- Информационная безопасность

## 🖥️ Системные требования

### Минимальные
- **PHP**: 8.0 или выше
- **SQLite**: 3.x
- **Web-сервер**: Apache 2.4+ или Nginx 1.18+
- **RAM**: 512 MB
- **Disk**: 100 MB

### Рекомендуемые (для Synology NAS)
- **Модель**: DS1522+ или выше
- **DSM**: 7.2.2 или выше
- **PHP**: 8.2
- **RAM**: 2 GB+

### PHP расширения
- PDO
- PDO_SQLite
- mbstring
- openssl
- fileinfo
- json

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

# Редактируем под свои нужды
nano .env
```

### 3. Установка прав доступа
```bash
# Для Synology
chown -R http:http /volume1/web/portal
chmod -R 755 /volume1/web/portal
chmod -R 777 /volume1/web/portal/cache
chmod -R 777 /volume1/web/portal/logs
chmod -R 777 /volume1/web/portal/uploads
chmod -R 777 /volume1/web/portal/data
```

### 4. Инициализация базы данных
```bash
# Запустите в браузере:
http://your-nas-ip/portal/install.php

# Или через PHP CLI:
php install.php

# После установки удалите файл:
rm install.php
```

### 5. Создание .htaccess (для Apache)
```bash
# Файл уже есть в репозитории
# Убедитесь, что mod_rewrite включен
```

### 6. Первый вход
```
URL: http://your-nas-ip/portal
Логин: admin
Пароль: admin123

⚠️ ВАЖНО: Смените пароль после первого входа!
```

## 📁 Структура проекта

```
portal/
├── core/                   # Ядро системы
│   ├── App.php            # Главный класс приложения
│   ├── Database.php       # Работа с БД
│   ├── Auth.php           # Аутентификация
│   ├── Security.php       # Безопасность (CSRF, XSS)
│   ├── Validator.php      # Валидация данных
│   ├── Session.php        # Управление сессиями
│   ├── Logger.php         # Логирование
│   └── Config.php         # Конфигурация
│
├── modules/               # Модули системы
│   ├── Dashboard/         # Главная страница
│   ├── News/             # Новости
│   ├── Requests/         # Заявки
│   ├── Equipment/        # Техника
│   ├── Users/            # Пользователи
│   ├── Roles/            # Роли и права
│   ├── Links/            # Ссылки
│   ├── Vacations/        # Отпуска
│   ├── Security/         # ИБ
│   └── Birthdays/        # Дни рождения
│
├── public/               # Публичная директория
│   ├── index.php        # Точка входа
│   ├── assets/          # Статические файлы
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│
├── data/                # База данных
│   └── portal.db
│
├── cache/              # Кэш
├── logs/               # Логи
├── uploads/            # Загруженные файлы
├── config/             # Конфигурационные файлы
└── templates/          # Шаблоны
```

## 🔐 Безопасность

### Встроенная защита
- ✅ CSRF токены для всех форм
- ✅ XSS фильтрация
- ✅ SQL Injection защита (Prepared Statements)
- ✅ Валидация всех входных данных
- ✅ Хеширование паролей (bcrypt)
- ✅ Ограничение попыток входа
- ✅ Логирование действий

### Рекомендации
1. Используйте HTTPS в production
2. Регулярно обновляйте систему
3. Делайте резервные копии БД
4. Используйте сложные пароли
5. Ограничьте доступ по IP (если возможно)

## 🔧 Конфигурация

### Основные настройки (.env)
```ini
APP_ENV=production        # development | production
APP_DEBUG=false          # true | false
APP_NAME="Портал"       # Название

DB_TYPE=sqlite           # Тип БД
DB_PATH=data/portal.db   # Путь к БД

SESSION_LIFETIME=7200    # Время жизни сессии (сек)
```

### Настройка модулей
Каждый модуль имеет свой config.php в папке модуля.

## 👥 Управление пользователями

### Роли по умолчанию
1. **Администратор** - полный доступ
2. **IT специалист** - управление заявками и техникой
3. **HR менеджер** - управление отпусками и днями рождения
4. **Сотрудник** - просмотр и создание заявок

### Создание роли
```php
// Через веб-интерфейс: Роли и права → Добавить роль
// Или через SQL:
INSERT INTO roles (name, description) VALUES ('Новая роль', 'Описание');
```

## 📊 Модули

### Dashboard (Главная)
- Статистика заявок
- Ближайшие события
- Сотрудники в отпуске
- Дни рождения

### Requests (Заявки)
- Создание заявок
- Назначение исполнителя
- Отслеживание статуса
- Комментарии

### Equipment (Техника)
- Учет оборудования
- История перемещений
- Комментарии к технике

### Users (Пользователи)
- Управление учетными записями
- Назначение ролей
- Просмотр активности

## 🔌 Разработка модулей

### Создание нового модуля
```bash
modules/
└── MyModule/
    ├── Controller.php     # Контроллер
    ├── Model.php         # Модель (работа с БД)
    ├── views/            # Представления
    │   ├── index.php
    │   └── form.php
    ├── config.php        # Конфигурация модуля
    └── permissions.php   # Права доступа
```

### Пример контроллера
```php
<?php
namespace Modules\MyModule;

class Controller extends \Core\BaseController {
    public function index() {
        $this->requirePermission('view_mymodule');
        $data = $this->model->getAll();
        $this->render('index', ['data' => $data]);
    }
}
```

## 🐛 Отладка

### Включение режима разработки
```ini
# .env
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=debug
```

### Просмотр логов
```bash
tail -f logs/app.log
tail -f logs/error.log
tail -f logs/security.log
```

## 📈 Производительность

### Кэширование
- Файловый кэш для редко меняющихся данных
- TTL по умолчанию: 300 секунд
- Очистка кэша: Настройки → Очистить кэш

### Оптимизация БД
```sql
-- Запуск через SQLite CLI
VACUUM;
ANALYZE;
REINDEX;
```

## 🔄 Резервное копирование

### Автоматическое (рекомендуется)
```bash
# Cron задача для Synology
0 2 * * * /usr/bin/sqlite3 /volume1/web/portal/data/portal.db ".backup '/volume1/backups/portal_$(date +\%Y\%m\%d).db'"
```

### Ручное
```bash
sqlite3 data/portal.db ".backup 'data/portal_backup.db'"
```

## 📞 Поддержка

### Часто задаваемые вопросы

**Q: Как сбросить пароль администратора?**
```sql
sqlite3 data/portal.db
UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
-- Новый пароль: password
```

**Q: Ошибка "Permission denied" при загрузке файлов**
```bash
chmod -R 777 /volume1/web/portal/uploads
```

**Q: База данных заблокирована**
```bash
# Проверьте права
chmod 666 data/portal.db
chown http:http data/portal.db
```

## 📝 Changelog

### [2.0.0] - 2026-03-13
- ✨ Полная модульная архитектура
- 🔐 Улучшенная безопасность (CSRF, XSS)
- 🎨 Обновленный UI/UX
- 📊 Расширенная система логирования
- ⚡ Оптимизация производительности
- 📱 Адаптивный дизайн

### [1.0.0] - 2025-XX-XX
- 🎉 Первый релиз

## 📄 Лицензия

MIT License - свободно используйте для личных и корпоративных проектов.

## 👨‍💻 Автор

**vo7na8**
- GitHub: [@vo7na8](https://github.com/vo7na8)

## 🙏 Благодарности

- Font Awesome за иконки
- SQLite за надежную БД
- Synology за отличный NAS

---

**Сделано с ❤️ для корпоративной эффективности**
