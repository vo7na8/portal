<?php
/**
 * Боковая панель навигации
 * Ожидает $menu_items [ключ => ['label'=>..., 'icon'=>..., 'perm'=>...]], $current_page
 */
$_sidebar_user_name = e($_SESSION['user_name'] ?? '');
$_sidebar_user_role = e($_SESSION['role_name'] ?? '');

// Запасные иконки если в массиве иконя нет
$_fallback_icons = [
    'dashboard' => 'fa-gauge-high',
    'news'      => 'fa-newspaper',
    'requests'  => 'fa-clipboard-list',
    'equipment' => 'fa-screwdriver-wrench',
    'users'     => 'fa-users',
    'links'     => 'fa-link',
    'vacations' => 'fa-umbrella-beach',
    'security'  => 'fa-shield-halved',
    'birthdays' => 'fa-cake-candles',
    'roles'     => 'fa-user-tag',
    'profile'   => 'fa-circle-user',
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-building"></i>
        <span class="sidebar-brand-text"><?= e(Config::getInstance()->getAppName()) ?></span>
    </div>

    <div class="user-info">
        <div class="user-avatar">
            <?= mb_strtoupper(mb_substr($_sidebar_user_name, 0, 1, 'UTF-8')) ?>
        </div>
        <div class="user-details">
            <div class="user-name"><?= $_sidebar_user_name ?></div>
            <?php if ($_sidebar_user_role): ?>
            <div class="user-role-badge"><?= $_sidebar_user_role ?></div>
            <?php endif; ?>
        </div>
    </div>

    <nav class="nav-menu">
        <?php foreach ($menu_items as $page => $item):
            // Поддерживаем оба формата: массив ['label','icon'] и простая строка
            if (is_array($item)) {
                $label = $item['label'] ?? $page;
                $icon  = $item['icon']  ?? ($_fallback_icons[$page] ?? 'fa-circle');
            } else {
                $label = $item;
                $icon  = $_fallback_icons[$page] ?? 'fa-circle';
            }
        ?>
        <a href="main.php?page=<?= $page ?>"
           class="nav-item <?= $current_page === $page ? 'active' : '' ?>">
            <i class="fas <?= $icon ?>"></i>
            <span><?= e($label) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <form method="post" action="logout.php" style="margin:0">
            <?= csrf_field() ?>
            <button type="submit" class="logout-btn">
                <i class="fas fa-right-from-bracket"></i>
                <span>Выйти</span>
            </button>
        </form>
    </div>
</aside>
