<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: main.php');
    exit;
}
require_once 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            header('Location: main.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h2>⸻ Вход в систему ⸻</h2>
        <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="login-field">
                <label>Логин</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="login-field">
                <label>Пароль</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Войти</button>
        </form>
    </div>
</body>
</html>