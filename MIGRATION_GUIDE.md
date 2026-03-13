# 🔄 Руководство по миграции на v2.0

## 📊 Обзор изменений

### Основные улучшения
- ✅ Модульная архитектура
- ✅ CSRF защита для всех форм
- ✅ XSS фильтрация
- ✅ Улучшенная валидация
- ✅ Система логирования
- ✅ Конфигурация через .env
- ✅ Обратная совместимость со старым кодом

### Изменения в структуре
```
СТАРАЯ СТРУКТУРА           →  НОВАЯ СТРУКТУРА
config.php                 →  config.php (обновлен)
auth.php                   →  используется старый
pages/*.php                →  остаются как есть
handlers/*.php             →  остаются как есть
                           →  core/ (новые классы)
                           →  .env (новый)
```

## 🚀 Пошаговая миграция

### Шаг 1: Резервное копирование

```bash
# Перейдите в директорию проекта
cd /volume1/web/portal

# Сделайте резервную копию
sudo cp -r /volume1/web/portal /volume1/web/portal_backup_$(date +%Y%m%d)

# Сохраните базу данных
sqlite3 data/portal.db ".backup 'data/portal_backup.db'"
```

### Шаг 2: Обновление кода

```bash
# Получите последние изменения из git
git pull origin main

# Или скачайте новые файлы вручную и замените
```

### Шаг 3: Настройка .env

```bash
# Копируем пример конфигурации
cp .env.example .env

# Редактируем .env
nano .env
```

**Минимальные настройки:**
```ini
APP_ENV=production
APP_DEBUG=false
DB_PATH=data/portal.db
```

### Шаг 4: Создание необходимых директорий

```bash
# Создаем директории для логов (если еще нет)
mkdir -p logs
mkdir -p uploads/temp

# Устанавливаем права
sudo chown -R http:http .
sudo chmod -R 755 .
sudo chmod -R 777 cache logs uploads data
```

### Шаг 5: Обновление .htaccess (если Apache)

Добавьте в `.htaccess`:
```apache
# Защита чувствительных файлов
<FilesMatch "\.(env|log|db)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Запрет доступа к core/
<Directory core>
    Order deny,allow
    Deny from all
</Directory>
```

### Шаг 6: Тестирование

```bash
# Откройте в браузере
http://your-nas-ip/portal

# Проверьте:
1. Вход в систему
2. Создание заявки
3. Добавление новости
4. Работу всех модулей
```

### Шаг 7: Проверка логов

```bash
# Просмотр логов на ошибки
tail -f logs/$(date +%Y-%m-%d).log

# Если есть ошибки - исправляем
```

## 🔧 Обновление старого кода (опционально)

### Добавление CSRF защиты в формы

**Старый код:**
```php
<form method="post" action="handlers/add_news.php">
    <input type="text" name="title">
    <button type="submit">Добавить</button>
</form>
```

**Новый код:**
```php
<form method="post" action="handlers/add_news.php">
    <?= csrf_field() ?>
    <input type="text" name="title">
    <button type="submit">Добавить</button>
</form>
```

### Добавление CSRF проверки в обработчики

**В начале каждого handler файла:**
```php
<?php
require_once __DIR__ . '/../auth.php';

// ДОБАВЬТЕ ЭТУ СТРОКУ:
Core\Security::getInstance()->requireCsrf();

// Остальной код...
```

### XSS защита в выводе

**Старый код:**
```php
<h2><?= $news['title'] ?></h2>
```

**Новый код:**
```php
<h2><?= e($news['title']) ?></h2>
<!-- Или -->
<h2><?= htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8') ?></h2>
```

### Использование нового Database класса

**Старый код:**
```php
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$id]);
$news = $stmt->fetch();
```

**Новый код:**
```php
$news = db()->selectOne("SELECT * FROM news WHERE id = ?", [$id]);
```

### Валидация данных

**Новый подход:**
```php
use Core\Validator;

$validator = Validator::make($_POST);

if ($validator->validate([
    'title' => 'required|min:3|max:255',
    'content' => 'required|min:10',
    'email' => 'email'
])) {
    // Данные валидны
    $validated = $validator->validated();
} else {
    // Ошибки
    $errors = $validator->errors();
}
```

## ⚠️ Возможные проблемы

### Проблема 1: Ошибка "Class not found"

**Решение:**
```bash
# Проверьте наличие всех файлов core/
ls -la core/

# Должны быть:
# Config.php, Database.php, Security.php, Session.php, Logger.php, Validator.php
```

### Проблема 2: Ошибка "Permission denied" для logs/

**Решение:**
```bash
sudo chmod -R 777 logs
sudo chown -R http:http logs
```

### Проблема 3: CSRF токен не работает

**Решение:**
```php
// Убедитесь, что сессия запущена
// config.php уже запускает сессию автоматически

// В форме добавьте:
<?= csrf_field() ?>

// В обработчике добавьте:
Core\Security::getInstance()->requireCsrf();
```

### Проблема 4: База данных заблокирована

**Решение:**
```bash
# Проверьте права на файл БД
sudo chmod 666 data/portal.db
sudo chown http:http data/portal.db

# Удалите WAL файлы если есть
rm data/portal.db-shm
rm data/portal.db-wal
```

## ✅ Проверочный список после миграции

- [ ] Сделана резервная копия
- [ ] Создан файл .env
- [ ] Созданы директории logs/ и uploads/
- [ ] Установлены права доступа
- [ ] Обновлен .htaccess
- [ ] Вход в систему работает
- [ ] Все модули доступны
- [ ] Формы создания/редактирования работают
- [ ] Нет ошибок в логах
- [ ] CSRF защита работает

## 🔙 Откат назад

Если что-то пошло не так:

```bash
# Удалите текущую версию
sudo rm -rf /volume1/web/portal

# Восстановите резервную копию
sudo cp -r /volume1/web/portal_backup_YYYYMMDD /volume1/web/portal

# Восстановите БД
cp data/portal_backup.db data/portal.db
```

## 📚 Дополнительные ресурсы

- [README.md](README.md) - Общая документация
- [SECURITY.md](SECURITY.md) - Рекомендации по безопасности
- [CHANGELOG.md](CHANGELOG.md) - История изменений

## ❓ Помощь

Если возникли проблемы:
1. Проверьте логи: `tail -f logs/$(date +%Y-%m-%d).log`
2. Проверьте права доступа: `ls -la`
3. Создайте issue в GitHub
