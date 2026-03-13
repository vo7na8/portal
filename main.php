<?php
require_once 'auth.php';
$current_page = $_GET['page'] ?? 'dashboard';

// Определяем доступные страницы на основе разрешений
$menu_items = [];

if (hasPermission($pdo, 'view_dashboard')) $menu_items['dashboard'] = 'Главная';
if (hasPermission($pdo, 'view_news')) $menu_items['news'] = 'Новости';
if (hasPermission($pdo, 'view_requests')) $menu_items['requests'] = 'Заявки';
if (hasPermission($pdo, 'view_equipment')) $menu_items['equipment'] = 'Техника';
if (hasPermission($pdo, 'view_users')) $menu_items['users'] = 'Пользователи';
if (hasPermission($pdo, 'view_links')) $menu_items['links'] = 'Ссылки';
if (hasPermission($pdo, 'view_vacations')) $menu_items['vacations'] = 'Отпуска';
if (hasPermission($pdo, 'view_security')) $menu_items['security'] = 'ИБ';
if (hasPermission($pdo, 'view_birthdays')) $menu_items['birthdays'] = 'Дни рождения';
if (hasPermission($pdo, 'manage_roles')) $menu_items['roles'] = 'Роли и права';

// Если текущая страница недоступна, редирект на первую доступную
if (!array_key_exists($current_page, $menu_items)) {
    $current_page = array_key_first($menu_items) ?? 'dashboard';
}

function getIconForPage($page) {
    return match($page) {
        'dashboard' => 'fa-home',
        'news' => 'fa-newspaper',
        'requests' => 'fa-clipboard-list',
        'equipment' => 'fa-tools',
        'users' => 'fa-users',
        'links' => 'fa-link',
        'vacations' => 'fa-umbrella-beach',
        'security' => 'fa-shield-alt',
        'birthdays' => 'fa-birthday-cake',
        'roles' => 'fa-user-tag',
        default => 'fa-circle'
    };
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Портал</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="script.js" defer></script>
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="user-info">
            <span class="user-role-badge"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
            <div class="user-name">
                <i class="fas fa-user-circle"></i>
                <span><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        </div>
        <nav class="nav-menu">
            <?php foreach ($menu_items as $page => $name): ?>
                <a href="?page=<?= $page ?>" class="nav-item <?= $current_page == $page ? 'active' : '' ?>">
                    <i class="fas <?= getIconForPage($page) ?>"></i> <?= $name ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="main-content">
        <?php include "pages/$current_page.php"; ?>
    </main>
</div>
</body>
</html>