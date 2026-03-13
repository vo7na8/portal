# 🚀 Руководство по развёртыванию

## Требования

| Компонент | Требование |
|---|---|
| PHP | ≥ 8.1 |
| Веб-сервер | Apache 2.4+ с `mod_rewrite`, `mod_headers` |
| SQLite | встроен (PDO) |
| PHP-расширения | `pdo`, `pdo_sqlite`, `mbstring`, `fileinfo` |

---

## 1. Клонирование репозитория

```bash
cd /var/www
git clone https://github.com/vo7na8/portal.git portal
cd portal
```

---

## 2. Создать `.env`

```bash
cp .env.example .env
nano .env
```

**Обязательно замените:**

```dotenv
APP_NAME="Название вашего портала"
APP_ENV=production
APP_DEBUG=false
DB_PATH=/var/www/portal/data/portal.db
```

---

## 3. Создать папки и выставить права

```bash
# Папки которые должен писать веб-сервер
mkdir -p data logs cache uploads/temp

chown www-data:www-data data logs cache uploads
chmod 750 data logs cache
chmod 755 uploads

# Файл базы (если уже существует)
# chown www-data:www-data data/portal.db
# chmod 640 data/portal.db

# .env нельзя читать apache
chown root:www-data .env
chmod 640 .env
```

---

## 4. Инициализация БД

База данных создаётся автоматически при первом обращении через `Database::getInstance()`.

Если хотите выполнить инициализацию вручную:

```bash
php -r "require 'config.php'; echo 'DB OK';"  
```

**Учётные данные по умолчанию:**
- Логин: `admin`
- Пароль: `admin123` (**смените немедленно после первого входа!**)

Перейдите в профиль и смените пароль через: `Главная` → иконка профиля.

---

## 5. Настройка Apache VirtualHost

```apache
<VirtualHost *:80>
    ServerName portal.example.com
    DocumentRoot /var/www/portal

    <Directory /var/www/portal>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  /var/log/apache2/portal-error.log
    CustomLog /var/log/apache2/portal-access.log combined
</VirtualHost>
```

Затем:
```bash
a2ensite portal.conf
a2enmod rewrite headers
systemctl reload apache2
```

---

## 6. HTTPS (настоятельно рекомендуется)

```bash
apt install certbot python3-certbot-apache
certbot --apache -d portal.example.com
```

PHPPortal автоматически определит `$_SERVER['HTTPS']` и выставит `secure=true` для cookie.

---

## 7. Cron: очистка старых логов и кэша

```cron
# Еженедельно удалять логи старше 30 дней
0 3 * * 0 find /var/www/portal/logs -name "*.log" -mtime +30 -delete

# Ежедневно очищать кэш старше 10 минут
0 4 * * * find /var/www/portal/cache -name "*.cache" -mtime +1 -delete
```

---

## 8. Чеклист перед запуском

- [ ] `.env` создан, `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Папки `data/`, `logs/`, `cache/` созданы, владелец `www-data`
- [ ] Пароль `admin` изменён через профиль
- [ ] HTTPS настроен
- [ ] Apache `mod_rewrite` и `mod_headers` включены
- [ ] Проверен вход / выход
- [ ] Проверена создать заявку
- [ ] Проверено отображение ошибок (logs/, не на экран)
- [ ] `nas config.txt` удалён из репозитория или запрещён (.htaccess)
