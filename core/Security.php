<?php
/**
 * Core\Security — CSRF защита, XSS-фильтрация, валидация файлов
 */
class Security
{
    private static ?Security $instance = null;
    private Config $config;

    private function __construct()
    {
        $this->config = Config::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // =========================================================
    // CSRF
    // =========================================================

    /**
     * Генерировать (или вернуть существующий) CSRF токен
     */
    public function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Проверить CSRF токен
     */
    public function verifyCsrf(?string $token): bool
    {
        if (!$this->config->getBool('SECURITY_CSRF_ENABLED', true)) {
            return true;
        }
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Проверить CSRF или завершить с 403
     */
    public function requireCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$this->verifyCsrf($token)) {
            Logger::getInstance()->warning('CSRF token mismatch', [
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            http_response_code(403);
            die('Недопустимый запрос (CSRF). Вернитесь назад и попробуйте снова.');
        }
    }

    /**
     * HTML-поле с CSRF токеном
     */
    public function csrfField(): string
    {
        $token = $this->generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    // =========================================================
    // XSS
    // =========================================================

    /**
     * Безопасный вывод строки (XSS escape)
     */
    public function escape(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Очистить HTML (оставить только безопасные теги)
     */
    public function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><b><i><u><strong><em><ul><ol><li><a>';
        return strip_tags($html, $allowed);
    }

    // =========================================================
    // Файлы
    // =========================================================

    /**
     * Проверить загружаемый файл
     * Возвращает null при успехе или строку с ошибкой
     */
    public function validateUpload(array $file): ?string
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => 'Файл превышает upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE  => 'Файл превышает MAX_FILE_SIZE формы',
                UPLOAD_ERR_PARTIAL    => 'Файл загружен частично',
                UPLOAD_ERR_NO_FILE    => 'Файл не выбран',
                UPLOAD_ERR_NO_TMP_DIR => 'Нет временной папки',
                UPLOAD_ERR_CANT_WRITE => 'Ошибка записи файла',
                UPLOAD_ERR_EXTENSION  => 'Загрузка заблокирована расширением PHP',
            ];
            return $errors[$file['error'] ?? -1] ?? 'Ошибка загрузки';
        }

        // Размер
        $maxMb = $this->config->getInt('SECURITY_MAX_UPLOAD_SIZE', 10);
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return "Файл слишком большой (максимум {$maxMb} МБ)";
        }

        // Расширение
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = array_map('trim', explode(',', $this->config->get(
            'SECURITY_ALLOWED_EXTENSIONS',
            'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,csv,txt'
        )));
        if (!in_array($ext, $allowed, true)) {
            return "Недопустимое расширение файла: .{$ext}";
        }

        // Проверка что это действительно загружен файл (не подделка)
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Недопустимый источник файла';
        }

        return null; // Всё в порядке
    }

    /**
     * Генерировать безопасное имя файла
     */
    public function safeFilename(string $originalName): string
    {
        $ext  = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        // Убираем всё кроме букв, цифр, дефиса, подчёркивания
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $name = trim($name, '_');
        if ($name === '') {
            $name = 'file';
        }
        return $name . '_' . time() . '.' . $ext;
    }

    // =========================================================
    // Пароли
    // =========================================================

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // =========================================================
    // Rate limiting (простой, на сессиях)
    // =========================================================

    /**
     * Проверить rate limit
     * @param string $key     Ключ действия (например 'login')
     * @param int    $max     Максимум попыток
     * @param int    $window  Окно в секундах
     */
    public function checkRateLimit(string $key, int $max = 5, int $window = 300): bool
    {
        $sKey = '_rl_' . $key;
        $now  = time();

        if (!isset($_SESSION[$sKey])) {
            $_SESSION[$sKey] = ['count' => 0, 'start' => $now];
        }

        // Сброс окна
        if ($now - $_SESSION[$sKey]['start'] > $window) {
            $_SESSION[$sKey] = ['count' => 0, 'start' => $now];
        }

        $_SESSION[$sKey]['count']++;

        if ($_SESSION[$sKey]['count'] > $max) {
            Logger::getInstance()->warning('Rate limit exceeded', ['key' => $key, 'count' => $_SESSION[$sKey]['count']]);
            return false; // Превышен лимит
        }

        return true; // Разрешено
    }

    public function resetRateLimit(string $key): void
    {
        unset($_SESSION['_rl_' . $key]);
    }
}
