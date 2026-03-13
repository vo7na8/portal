<?php
/**
 * Toast-уведомления из flash-сессии
 * Типы: success, error, warning, info
 */
$_flash_all = Session::getInstance()->getAllFlash();
if (empty($_flash_all)) return;

$_flash_icons = [
    'success' => 'fa-circle-check',
    'error'   => 'fa-circle-xmark',
    'warning' => 'fa-triangle-exclamation',
    'info'    => 'fa-circle-info',
];
?>
<div class="toast-container" id="toastContainer">
<?php foreach ($_flash_all as $type => $message): ?>
    <div class="toast toast-<?= e($type) ?>" role="alert">
        <i class="fas <?= $_flash_icons[$type] ?? 'fa-circle-info' ?>"></i>
        <span><?= e($message) ?></span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endforeach; ?>
</div>
