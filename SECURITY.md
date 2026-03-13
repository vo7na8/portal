# 🛡️ Рекомендации по безопасности

## 📊 Обзор

Корпоративный портал v2.0 включает множество встроенных механизмов безопасности. Однако для максимальной защиты вашей системы следуйте этим рекомендациям.

## 🔒 Встроенные механизмы защиты

### Автоматическая защита

✅ **CSRF Protection** - Автоматическая защита всех форм  
✅ **XSS Filtering** - Фильтрация всех выводов  
✅ **SQL Injection** - Prepared Statements  
✅ **Password Hashing** - bcrypt/argon2  
✅ **Session Security** - httponly, secure cookies  
✅ **File Upload Validation** - Тип, размер, MIME  
✅ **Rate Limiting** - Ограничение попыток входа  

## 🛠️ Настройка Production окружения

### 1. Конфигурация .env

**Критичные настройки:**

```ini
# ОБЯЗАТЕЛЬНО в production!
APP_ENV=production
APP_DEBUG=false

# Генерируйте уникальный ключ!
APP_KEY=$(openssl rand -hex 32)

# Включите защиту
SECURITY_CSRF_ENABLED=true

# Ограничьте размер файлов
SECURITY_MAX_UPLOAD_SIZE=10

# Разрешите только безопасные форматы
SECURITY_ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,jpg,png

# Уменьшите время жизни сессии
SESSION_LIFETIME=3600  # 1 час

# Логируйте только ошибки
LOG_LEVEL=error
```

### 2. Права доступа

**Правильные права:**

```bash
# Директории и файлы
chmod -R 755 /volume1/web/portal

# Запись только для нужных папок
chmod 777 /volume1/web/portal/cache
chmod 777 /volume1/web/portal/logs
chmod 777 /volume1/web/portal/uploads
chmod 777 /volume1/web/portal/data

# База данных
chmod 666 /volume1/web/portal/data/portal.db

# Запретить запись в .env
chmod 400 /volume1/web/portal/.env
```

**Не давайте 777 на все файлы!**

### 3. Защита .htaccess (Apache)

**Создайте или обновите .htaccess:**

```apache
# Запрет доступа к чувствительным файлам
<FilesMatch "\.(env|log|db|db-shm|db-wal|md|git.*|htaccess)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Запрет доступа к директориям
<Directory "core">
    Order deny,allow
    Deny from all
</Directory>

<Directory "data">
    Order deny,allow
    Deny from all
</Directory>

<Directory "logs">
    Order deny,allow
    Deny from all
</Directory>

# Защита от directory browsing
Options -Indexes

# Ограничение времени выполнения
php_value max_execution_time 30

# Ограничение загрузки файлов
php_value upload_max_filesize 10M
php_value post_max_size 10M

# Защита от hotlinking
RewriteEngine On
RewriteCond %{HTTP_REFERER} !^$
RewriteCond %{HTTP_REFERER} !^https://(www\.)?your-domain\.com [NC]
RewriteRule \.(jpg|jpeg|png|gif|pdf)$ - [F,L]
```

### 4. Настройка HTTPS

**Обязательно используйте HTTPS в production!**

#### Для Synology DSM:

1. Перейдите в **Панель управления** → **Безопасность** → **Сертификат**
2. Создайте сертификат или используйте Let's Encrypt
3. Назначьте сертификат на ваш веб-сервер

#### Принудительное перенаправление на HTTPS:

```apache
# .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 🔐 Пароли и аутентификация

### Требования к паролям

**Минимальные требования:**
- Минимум 12 символов
- Заглавные и строчные буквы
- Цифры
- Специальные символы

**Примеры сильных паролей:**
```
✅ Tr0ng!Pass@2024#Synology
✅ MyC0rp$Secure#Portal2026
✅ Admin!2024@SecurePortal#

❌ admin123
❌ password
❌ 12345678
```

### Смена пароля администратора

**ОБЯЗАТЕЛЬНО смените пароль по умолчанию!**

```bash
# Войдите как admin и смените пароль в профиле
# Или через SQL:
sqlite3 data/portal.db

-- Сгенерируйте новый хэш
php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT);"

-- Обновите в БД
UPDATE users SET password = 'new-hash-here' WHERE username = 'admin';
```

### Регулярная смена паролей

- Меняйте пароли каждые 90 дней
- Не используйте одинаковые пароли для разных систем
- Используйте менеджеры паролей (KeePass, 1Password, Bitwarden)

## 📁 Загрузка файлов

### Разрешенные типы

**Ограничьте список в .env:**

```ini
# Безопасные форматы
SECURITY_ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif

# Опасно! Не разрешайте:
# exe,bat,sh,php,js,html,htm,svg,xml
```

### Ограничение размера

```ini
# Рекомендуемый максимум: 10MB
SECURITY_MAX_UPLOAD_SIZE=10
```

### Защита директории uploads/

```apache
# uploads/.htaccess
# Запретить выполнение PHP
php_flag engine off

# Разрешить только чтение
Options -Indexes -ExecCGI

# Запретить .php файлы
<FilesMatch "\.php$">
    Order deny,allow
    Deny from all
</FilesMatch>
```

## 🔄 Резервное копирование

### Автоматическое резервное копирование

**Настройте cron задачу в Synology:**

```bash
# Панель управления → Планировщик задач → Создать

# Расписание: Каждый день в 02:00
0 2 * * * /usr/bin/sqlite3 /volume1/web/portal/data/portal.db ".backup '/volume1/backups/portal_$(date +\%Y\%m\%d).db'"

# Удаление старых резервных копий (старше 30 дней)
0 3 * * * find /volume1/backups/ -name "portal_*.db" -mtime +30 -delete
```

### Хранение резервных копий

- Храните ежедневные резервные копии 7 дней
- Храните еженедельные резервные копии 4 недели
- Храните ежемесячные резервные копии 12 месяцев
- Храните копии вне NAS (облако, внешний HDD)

## 📊 Мониторинг и логи

### Проверка логов

**Регулярно проверяйте:**

```bash
# Ошибки
grep "ERROR" logs/$(date +%Y-%m-%d).log

# Попытки взлома
grep "Failed login" logs/$(date +%Y-%m-%d).log
grep "CSRF" logs/$(date +%Y-%m-%d).log

# Подозрительная активность
grep "admin" logs/$(date +%Y-%m-%d).log | grep "DELETE"
```

### Алерты

**Настройте оповещения на:**
- 5+ неудачных попыток входа
- CSRF атаки
- Загрузка недопустимых файлов
- Ошибки базы данных

## 🌐 Сетевая безопасность

### Файрвол

**Ограничьте доступ по IP (если возможно):**

```apache
# .htaccess - доступ только с локальной сети
<Directory "/volume1/web/portal">
    Order deny,allow
    Deny from all
    Allow from 192.168.1.0/24
    Allow from 10.0.0.0/8
</Directory>
```

### VPN

**Для внешнего доступа:**
- Используйте Synology VPN Server
- Не открывайте порты напрямую в интернет
- Используйте сильную аутентификацию VPN

## 🔍 Регулярные проверки

### Еженедельно

- [ ] Проверить логи на ошибки
- [ ] Проверить попытки взлома
- [ ] Проверить размер БД и логов
- [ ] Проверить резервные копии

### Ежемесячно

- [ ] Обновить PHP и DSM
- [ ] Проверить обновления portal
- [ ] Аудит пользователей и ролей
- [ ] Проверить права доступа
- [ ] Тест восстановления из резервной копии

### Ежеквартально

- [ ] Аудит безопасности
- [ ] Обзор логов за период
- [ ] Проверка сложности паролей
- [ ] Обновление документации

## ⚠️ При компрометации

### Немедленные действия

1. **Отключите портал**
   ```bash
   mv index.php index.php.backup
   echo "Maintenance" > index.php
   ```

2. **Смените все пароли**
   - Администратора
   - Всех пользователей
   - Базы данных (если есть)

3. **Проанализируйте логи**
   ```bash
   grep -r "suspicious" logs/
   grep -r "ERROR" logs/
   ```

4. **Проверьте файлы**
   ```bash
   find . -type f -mtime -7  # Измененные за 7 дней
   ```

5. **Восстановите из резервной копии**

6. **Обновите все**
   - Portal
   - PHP
   - DSM

## 📞 Контакты

**Сообщить об уязвимости:**

- Email: security@your-domain.com
- GitHub Issues: [github.com/vo7na8/portal/issues](https://github.com/vo7na8/portal/issues)

**При сообщении укажите:**
- Версию portal
- Описание проблемы
- Шаги для воспроизведения
- Возможное влияние

---

**Безопасность - это процесс, а не одноразовая настройка. Регулярно проверяйте и обновляйте вашу систему!**
