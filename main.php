<?php
/**
 * Главный каркас портала
 */
require_once __DIR__ . '/auth.php';

$current_page = preg_replace('/[^a-z_]/', '', $_GET['page'] ?? 'dashboard');

// Меню с проверкой прав
$menu_items = [];
$menu_map = [
    'dashboard' => ['perm' => 'view_dashboard', 'label' => 'Главная'],
    'news'      => ['perm' => 'view_news',      'label' => 'Новости'],
    'requests'  => ['perm' => 'view_requests',  'label' => 'Заявки'],
    'equipment' => ['perm' => 'view_equipment', 'label' => 'Техника'],
    'users'     => ['perm' => 'view_users',     'label' => 'Пользователи'],
    'links'     => ['perm' => 'view_links',     'label' => 'Ссылки'],
    'vacations' => ['perm' => 'view_vacations', 'label' => 'Отпуска'],
    'security'  => ['perm' => 'view_security',  'label' => 'ИБ'],
    'birthdays' => ['perm' => 'view_birthdays', 'label' => 'Дни рождения'],
    'roles'     => ['perm' => 'manage_roles',   'label' => 'Роли и права'],
];

foreach ($menu_map as $page => $item) {
    if (hasPermission($pdo, $item['perm'])) {
        $menu_items[$page] = $item['label'];
    }
}

// Если страница недоступна — переходим на первую доступную
if (!array_key_exists($current_page, $menu_items)) {
    $current_page = array_key_first($menu_items) ?? 'dashboard';
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
$layout_title   = $menu_items[$current_page] ?? '';

include __DIR__ . '/templates/layout.php';
