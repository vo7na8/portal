<?php
/**
 * Генерация уникального номера заявки в формате DD-NNNNN
 * Использует транзакцию для предотвращения коллизий при параллельных запросах.
 */

/**
 * Генерирует и резервирует номер заявки.
 * Вызывать ВНУТРИ транзакции INSERT requests.
 *
 * @param PDO $pdo
 * @param int $templateId  ID шаблона заявки
 * @return string  Например: "PT-00007"
 * @throws RuntimeException если шаблон не найден
 */
function generateRequestNumber(PDO $pdo, int $templateId): string
{
    $tmpl = $pdo->prepare('SELECT category_code FROM request_templates WHERE id = ?');
    $tmpl->execute([$templateId]);
    $row = $tmpl->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new \RuntimeException("Шаблон ID:{$templateId} не найден");
    }

    $code = strtoupper($row['category_code']);

    // Глобальный порядковый номер среди ВСЕХ заявок (не только данной категории)
    // MAX(id)+1 достаточен для SQLite (автоинкремент не переиспользуется)
    $maxId = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM requests')->fetchColumn();
    $seq   = $maxId + 1;

    return sprintf('%s-%05d', $code, $seq);
}

/**
 * Определяет ID шаблона «Общая заявка» (GN) — fallback для старых заявок.
 */
function getDefaultTemplateId(PDO $pdo): ?int
{
    $row = $pdo->query("SELECT id FROM request_templates WHERE category_code = 'GN' AND is_active = 1 LIMIT 1")
               ->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}
