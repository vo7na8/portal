<?php
/**
 * AJAX: Возвращает поля шаблона для динамической формы создания заявки
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('jsonSuccess')) {
    function jsonSuccess(mixed $data = [], int $code = 200): never {
        http_response_code($code);
        echo json_encode(['success' => true] + (array)$data);
        exit;
    }
}
if (!function_exists('jsonError')) {
    function jsonError(string $message, int $code = 400): never {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}

if (empty($_SESSION['user_id'])) {
    jsonError('Не авторизован', 401);
}

$template_id = (int)($_GET['template_id'] ?? 0);
if (!$template_id) { jsonError('Не указан ID шаблона'); }

try {
    $template = $pdo->prepare('
        SELECT id, category_code, category_name, title_template, icon
        FROM request_templates
        WHERE id = ? AND is_active = 1
    ');
    $template->execute([$template_id]);
    $tmpl = $template->fetch(PDO::FETCH_ASSOC);
    if (!$tmpl) { jsonError('Шаблон не найден', 404); }

    // fix: колонка называется validation_json (см. миграцию), не validation_rules
    $fields = $pdo->prepare('
        SELECT id, field_name, field_type, field_label, placeholder,
               is_required, sort_order, options_source, validation_json, help_text
        FROM template_fields
        WHERE template_id = ?
        ORDER BY sort_order ASC, id ASC
    ');
    $fields->execute([$template_id]);
    $fieldRows = $fields->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fieldRows as &$f) {
        // Декодируем validation_json и отдаём фронту как validation_rules для совместимости
        $f['validation_rules'] = $f['validation_json'] ? json_decode($f['validation_json'], true) : null;
        unset($f['validation_json']);

        if ($f['field_type'] === 'select' && $f['options_source']) {
            $nsi = $pdo->prepare('
                SELECT nv.value, nv.display_text
                FROM nsi_values nv
                JOIN nsi_dictionaries nd ON nv.dictionary_id = nd.id
                WHERE nd.code = ? AND nv.is_active = 1
                ORDER BY nv.sort_order ASC, nv.display_text ASC
            ');
            $nsi->execute([$f['options_source']]);
            $f['options'] = $nsi->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    unset($f);

    jsonSuccess([
        'template' => $tmpl,
        'fields'   => $fieldRows
    ]);
} catch (PDOException $e) {
    $logger->error('get_template_fields: ' . $e->getMessage());
    jsonError('Ошибка получения полей шаблона');
}
