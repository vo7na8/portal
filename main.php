<?php
/**
 * Главный каркас портала
 */
require_once __DIR__ . '/auth.php';

$current_page = preg_replace('/[^a-z_]/', '', $_GET['page'] ?? 'dashboard');

// Меню с проверкой прав
$menu_items = [];
$menu_map = [
    'dashboard' => ['perm' => 'view_dashboard', 'label' => 'Главная',        'icon' => 'fa-house'],
    'news'      => ['perm' => 'view_news',      'label' => 'Новости',        'icon' => 'fa-newspaper'],
    'requests'  => ['perm' => 'view_requests',  'label' => 'Заявки',        'icon' => 'fa-clipboard-list'],
    'equipment' => ['perm' => 'view_equipment', 'label' => 'Техника',        'icon' => 'fa-screwdriver-wrench'],
    'users'     => ['perm' => 'view_users',     'label' => 'Пользователи',  'icon' => 'fa-users'],
    'links'     => ['perm' => 'view_links',     'label' => 'Ссылки',        'icon' => 'fa-link'],
    'vacations' => ['perm' => 'view_vacations', 'label' => 'Отпуска',        'icon' => 'fa-umbrella-beach'],
    'security'  => ['perm' => 'view_security',  'label' => 'ИБ',            'icon' => 'fa-shield-halved'],
    'birthdays' => ['perm' => 'view_birthdays', 'label' => 'Дни рождения', 'icon' => 'fa-cake-candles'],
    'roles'     => ['perm' => 'manage_roles',   'label' => 'Роли и права',   'icon' => 'fa-user-shield'],
];

foreach ($menu_map as $page => $item) {
    if (hasPermission($pdo, $item['perm'])) {
        $menu_items[$page] = $item;
    }
}

// Profile доступен всегда (добавляем в меню без проверки прав)
$menu_items['profile'] = ['perm' => null, 'label' => 'Мой профиль', 'icon' => 'fa-circle-user'];

// Если страница недоступна — переходим на первую доступную
$visible = array_filter($menu_items, fn($v) => $v['perm'] !== null);
if (!array_key_exists($current_page, $menu_items)) {
    $current_page = array_key_first($visible) ?? 'dashboard';
}

$page_file = __DIR__ . "/pages/{$current_page}.php";

// Сбор контента через output buffering
ob_start();
if (file_exists($page_file)) {
    include $page_file;
} else {
    echo '<div class="empty-state"><i class="fas fa-circle-question"></i><p>Страница не найдена.</p></div>';
}
$layout_content = ob_get_clean();
$layout_title   = $menu_items[$current_page]['label'] ?? '';

include __DIR__ . '/templates/layout.php';
