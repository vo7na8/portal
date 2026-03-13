<?php
/**
 * Страница входа
 */
require_once __DIR__ . '/config.php';

// Уже авторизован — на главную
if ($session->isLoggedIn()) {
    redirect('main.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting: не более 10 попыток за 5 минут
    if (!$security->checkRateLimit('login', 10, 300)) {
        $error = 'Слишком много попыток. Подождите несколько минут.';
    } else {
        $v = Validator::make($_POST);
        if (!$v->validate(['username' => 'required', 'password' => 'required'])) {
            $error = 'Заполните все поля.';
        } else {
            $username = $v->validated()['username'];
            $password = $_POST['password'];

            $user = Database::getInstance()->selectOne(
                'SELECT * FROM users WHERE username = ?',
                [$username]
            );

            if ($user && $security->verifyPassword($password, $user['password_hash'] ?? $user['password'] ?? '')) {
                // Успех
                $security->resetRateLimit('login');
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role_id']   = $user['role_id'];

                // Подтягиваем название роли
                $role = Database::getInstance()->selectOne(
                    'SELECT name FROM roles WHERE id = ?',
                    [$user['role_id']]
                );
                $_SESSION['role_name'] = $role['name'] ?? '';

                $logger->info('User logged in', ['user' => $username]);
                $session->flash('success', 'Добро пожаловать, ' . $user['full_name'] . '!');
                redirect('main.php');
            } else {
                $logger->warning('Failed login attempt', ['user' => $username]);
                $error = 'Неверный логин или пароль.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — <?= e(Config::getInstance()->getAppName()) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <script src="script.js" defer></script>
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-logo">
        <i class="fas fa-building"></i>
    </div>
    <h2><?= e(Config::getInstance()->getAppName()) ?></h2>
    <p class="login-subtitle">Вход в систему</p>

    <?php if ($error): ?>
    <div class="error-message">
        <i class="fas fa-circle-xmark"></i> <?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <div class="login-field">
            <label for="username"><i class="fas fa-user"></i> Логин</label>
            <input type="text" id="username" name="username"
                   value="<?= e($_POST['username'] ?? '') ?>"
                   required autofocus autocomplete="username">
        </div>
        <div class="login-field">
            <label for="password"><i class="fas fa-lock"></i> Пароль</label>
            <input type="password" id="password" name="password"
                   required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login">
            <i class="fas fa-right-to-bracket"></i> Войти
        </button>
    </form>
</div>
</body>
</html>
