<?php
/**
 * pages/requests.php — Sprint 3
 * Табличный список + динамическая форма сзаявки с выбором шаблона
 */
if (!hasPermission($pdo, 'view_requests')) {
    echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>';
    return;
}

$db          = Database::getInstance();
$canAdd      = hasPermission($pdo, 'add_request');
$canTake     = hasPermission($pdo, 'take_request');
$canComplete = hasPermission($pdo, 'edit_request');
$canReassign = hasPermission($pdo, 'reassign_request');
$canAssign   = hasPermission($pdo, 'assign_request');

// Шаблоны для выпадающего списка
$templates = $pdo->query(
    'SELECT id, category_code, category_name, icon FROM request_templates WHERE is_active = 1 ORDER BY sort_order ASC, category_name ASC'
)->fetchAll(PDO::FETCH_ASSOC);

// Список пользователей (для назначения ответственного)
$allUsers = $canAssign
    ? $db->select('SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name')
    : [];

// Фильтры
$fStatus   = $_GET['status']   ?? '';
$fTemplate = (int)($_GET['template'] ?? 0);
$fSearch   = trim($_GET['q'] ?? '');

// Строим WHERE
$where  = ['1=1'];
$params = [];

if ($fStatus)   { $where[] = 'r.status = ?';      $params[] = $fStatus; }
if ($fTemplate) { $where[] = 'r.template_id = ?'; $params[] = $fTemplate; }
if ($fSearch)   {
    $where[]  = '(r.request_number LIKE ? OR r.title LIKE ?)';
    $params[] = "%{$fSearch}%";
    $params[] = "%{$fSearch}%";
}

// Фильтр видимости: обычный пользователь видит только свои заявки
if (!hasPermission($pdo, 'view_all_requests')) {
    $where[]  = '(r.author_id = ? OR r.assigned_to = ?)';
    $params[] = (int)$_SESSION['user_id'];
    $params[] = (int)$_SESSION['user_id'];
}

$whereSQL = implode(' AND ', $where);
$requests = $db->select(
    "SELECT r.*,
            rt.category_code, rt.category_name, rt.icon AS template_icon,
            u.full_name  AS author_name,
            a.full_name  AS assigned_name
     FROM requests r
     LEFT JOIN request_templates rt ON r.template_id = rt.id
     LEFT JOIN users u ON r.author_id  = u.id
     LEFT JOIN users a ON r.assigned_to = a.id
     WHERE {$whereSQL}
     ORDER BY r.created_at DESC",
    $params
);

$statusMap = [
    'новая'    => 'badge-new',
    'в работе' => 'badge-progress',
    'выполнена' => 'badge-done',
];

// fix: была generateCsrf() — функция не существует; правильный хелпер csrf_token()
$csrfToken = csrf_token();
?>

<div class="page-header">
    <h2><i class="fas fa-clipboard-list"></i> Заявки</h2>
    <?php if ($canAdd): ?>
    <button class="btn btn-primary" onclick="openRequestModal()">
        <i class="fas fa-plus"></i> Новая заявка
    </button>
    <?php endif; ?>
</div>

<!-- Фильтры таблицы -->
<form method="get" class="table-filters" id="filterForm">
    <input type="hidden" name="page" value="requests">
    <input type="text" name="q" value="<?= htmlspecialchars($fSearch) ?>"
           placeholder="Поиск по номеру / теме…" class="form-control filter-search">
    <select name="status" class="form-control filter-select" onchange="this.form.submit()">
        <option value="">Все статусы</option>
        <option value="новая"   <?= $fStatus==='новая'    ? 'selected' : '' ?>>Новая</option>
        <option value="в работе" <?= $fStatus==='в работе' ? 'selected' : '' ?>>В работе</option>
        <option value="выполнена" <?= $fStatus==='выполнена' ? 'selected' : '' ?>>Выполнена</option>
    </select>
    <select name="template" class="form-control filter-select" onchange="this.form.submit()">
        <option value="0">Все категории</option>
        <?php foreach ($templates as $t): ?>
        <option value="<?= $t['id'] ?>" <?= $fTemplate===$t['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['category_name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-search"></i></button>
    <?php if ($fStatus || $fTemplate || $fSearch): ?>
    <a href="?page=requests" class="btn btn-outline btn-sm">Сброс</a>
    <?php endif; ?>
</form>

<!-- Таблица заявок -->
<?php if (empty($requests)): ?>
<div class="empty-state">
    <i class="fas fa-clipboard-list fa-3x text-muted"></i>
    <p>Заявок не найдено</p>
</div>
<?php else: ?>
<div class="table-responsive">
<table class="data-table requests-table" id="requestsTable">
    <thead>
        <tr>
            <th class="sortable" data-col="request_number" style="width:120px">№ заявки</th>
            <th class="sortable" data-col="status" style="width:110px">Статус</th>
            <th class="sortable" data-col="title">Тема</th>
            <th class="sortable" data-col="assigned_name" style="width:160px">Исполнитель</th>
            <th class="sortable" data-col="created_at" style="width:140px">Создана</th>
            <th style="width:130px">Действия</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($requests as $r):
        $rid      = (int)$r['id'];
        $comments = $db->select(
            'SELECT rc.*, u.full_name FROM request_comments rc
             LEFT JOIN users u ON rc.user_id = u.id
             WHERE rc.request_id = ? ORDER BY rc.created_at ASC',
            [$rid]
        );
        $numDisplay = $r['request_number'] ?? ('#' . $rid);
    ?>
    <!-- Основная строка -->
    <tr class="request-row" data-id="<?= $rid ?>" onclick="toggleRequestDetails(<?= $rid ?>)">
        <td>
            <span class="request-number">
                <?php if ($r['template_icon']): ?>
                <i class="fas <?= htmlspecialchars($r['template_icon']) ?> text-muted" style="font-size:.75rem"></i>
                <?php endif; ?>
                <?= htmlspecialchars($numDisplay) ?>
            </span>
        </td>
        <td>
            <span class="badge <?= $statusMap[$r['status']] ?? 'badge-closed' ?>"><?= htmlspecialchars($r['status']) ?></span>
        </td>
        <td class="request-title-cell">
            <span class="request-title"><?= htmlspecialchars($r['title']) ?></span>
            <?php if ($r['category_name']): ?>
            <span class="request-category text-muted"><?= htmlspecialchars($r['category_name']) ?></span>
            <?php endif; ?>
        </td>
        <td><?= $r['assigned_name'] ? htmlspecialchars($r['assigned_name']) : '<em class="text-muted">не назначен</em>' ?></td>
        <td class="text-muted" style="font-size:.82rem"><?= format_datetime($r['created_at']) ?></td>
        <td onclick="event.stopPropagation()" class="request-actions-cell">
            <?php if ($canTake && $r['status'] === 'новая'): ?>
            <form method="post" action="handlers/take_request.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $rid ?>">
                <button type="submit" class="btn btn-success btn-sm" title="Взять">Взять</button>
            </form>
            <?php endif; ?>
            <?php if ($canComplete && $r['status'] === 'в работе'): ?>
            <form method="post" action="handlers/complete_request.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $rid ?>">
                <button type="submit" class="btn btn-secondary btn-sm" title="Закрыть">Закрыть</button>
            </form>
            <?php endif; ?>
            <button class="btn-icon" title="Подробнее" onclick="toggleRequestDetails(<?= $rid ?>);event.stopPropagation()">
                <i class="fas fa-chevron-down" id="chevron-<?= $rid ?>"></i>
            </button>
        </td>
    </tr>
    <!-- Разворачиваемая строка с деталями -->
    <tr class="request-details-row" id="details-<?= $rid ?>" style="display:none">
        <td colspan="6">
            <div class="request-details-body">
                <!-- Описание -->
                <?php if (!empty($r['description'])): ?>
                <div class="details-section">
                    <h5><i class="fas fa-align-left"></i> Описание</h5>
                    <p style="white-space:pre-wrap"><?= htmlspecialchars($r['description']) ?></p>
                </div>
                <?php endif; ?>

                <!-- Динамические поля шаблона -->
                <?php
                if ($r['template_id']) {
                    $fieldVals = $db->select(
                        'SELECT tf.field_label, rfv.value
                         FROM request_field_values rfv
                         JOIN template_fields tf ON rfv.field_id = tf.id
                         WHERE rfv.request_id = ? AND rfv.value != \'\'
                         ORDER BY tf.sort_order ASC',
                        [$rid]
                    );
                    if (!empty($fieldVals)):
                ?>
                <div class="details-section">
                    <h5><i class="fas fa-list-check"></i> Дополнительная информация</h5>
                    <dl class="field-values-list">
                    <?php foreach ($fieldVals as $fv): ?>
                        <dt><?= htmlspecialchars($fv['field_label']) ?></dt>
                        <dd><?= htmlspecialchars($fv['value']) ?></dd>
                    <?php endforeach; ?>
                    </dl>
                </div>
                <?php endif; } ?>

                <!-- Мета: автор, номер -->
                <div class="details-meta text-muted">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($r['author_name'] ?? '—') ?></span>
                    <?php if ($r['request_number']): ?>
                    <span><i class="fas fa-hashtag"></i> <?= htmlspecialchars($r['request_number']) ?></span>
                    <?php endif; ?>
                    <?php if ($canReassign && $r['status'] !== 'выполнена'): ?>
                    <form method="post" action="handlers/reassign_request.php" style="display:inline-flex;gap:.3rem;align-items:center">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= $rid ?>">
                        <select name="assigned_to" class="form-control" style="padding:.25rem .6rem;font-size:.82rem">
                            <option value="">— снять —</option>
                            <?php foreach ($allUsers as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= (int)$r['assigned_to']===(int)$u['id'] ? 'selected':'' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-user-pen"></i> Переназначить</button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Комментарии -->
                <div class="details-section">
                    <h5><i class="fas fa-comments"></i> Комментарии (<?= count($comments) ?>)</h5>
                    <?php if (!empty($comments)): ?>
                    <div class="comment-list">
                        <?php foreach ($comments as $c): ?>
                        <div class="comment-item <?= $c['is_log'] ? 'comment-log' : '' ?>">
                            <strong><?= htmlspecialchars($c['full_name'] ?? 'Система') ?></strong>
                            <?php if ($c['is_log']): ?>
                            <span class="badge badge-progress" style="font-size:.7rem">изменение</span>
                            <?php endif; ?>
                            <span class="comment-date"><?= format_datetime($c['created_at']) ?></span><br>
                            <span style="font-size:.88rem;white-space:pre-wrap"><?= htmlspecialchars($c['body']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted" style="font-size:.85rem">Комментариев пока нет</p>
                    <?php endif; ?>
                    <form method="post" action="handlers/add_request_comment.php" class="comment-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= $rid ?>">
                        <input type="text" name="comment" placeholder="Комментарий к заявке…" required class="form-control">
                        <button type="submit" class="btn btn-primary btn-sm">Отправить</button>
                    </form>
                </div>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<!-- ================================================================
     Модальное окно: создание заявки с шаблоном
     ================================================================ -->
<?php if ($canAdd): ?>
<div class="modal" id="requestModal" style="display:none">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h4><i class="fas fa-plus"></i> Новая заявка</h4>
            <button onclick="closeRequestModal()" class="modal-close">&times;</button>
        </div>
        <form method="post" action="handlers/add_request.php" id="newRequestForm">
            <?= csrf_field() ?>
            <div class="modal-body">

                <!-- Шаг 1: выбор шаблона -->
                <div class="form-group">
                    <label>Тип заявки <span class="required">*</span></label>
                    <select name="template_id" id="templateSelect" class="form-control" required
                            onchange="loadTemplateFields(this.value)">
                        <option value="">— выберите тип заявки —</option>
                        <?php foreach ($templates as $t): ?>
                        <option value="<?= $t['id'] ?>">
                            <?= htmlspecialchars('[' . $t['category_code'] . '] ' . $t['category_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Заголовок -->
                <div class="form-group">
                    <label>Тема <span class="required">*</span></label>
                    <input type="text" name="title" id="requestTitle" class="form-control"
                           required maxlength="255" placeholder="Уточните тему…">
                </div>

                <!-- Динамические поля шаблона -->
                <div id="templateFieldsContainer"></div>

                <!-- Ответственный (только админ/менеджер) -->
                <?php if ($canAssign): ?>
                <div class="form-group" id="assignedToGroup">
                    <label>Ответственный</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">— не назначен —</option>
                        <?php foreach ($allUsers as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeRequestModal()">Отмена</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Отправить заявку
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// =========================================================
//  Сортировка таблицы
// =========================================================
document.querySelectorAll('.requests-table th.sortable').forEach(th => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', () => {
        const col  = th.dataset.col;
        const tbody = document.querySelector('.requests-table tbody');
        const rows  = [...tbody.querySelectorAll('tr.request-row')];
        const asc   = th.dataset.order !== 'asc';
        th.dataset.order = asc ? 'asc' : 'desc';

        document.querySelectorAll('.requests-table th.sortable').forEach(t => {
            t.textContent = t.textContent.replace(/ [\u2191\u2193]$/, '');
        });
        th.textContent += asc ? ' \u2191' : ' \u2193';

        rows.sort((a, b) => {
            const ai = a.dataset.id, bi = b.dataset.id;
            const av = a.querySelector(`td:nth-child(${[...th.parentNode.children].indexOf(th)+1})`).textContent.trim();
            const bv = b.querySelector(`td:nth-child(${[...th.parentNode.children].indexOf(th)+1})`).textContent.trim();
            return asc ? av.localeCompare(bv, 'ru') : bv.localeCompare(av, 'ru');
        });

        rows.forEach(row => {
            const det = document.getElementById('details-' + row.dataset.id);
            tbody.appendChild(row);
            if (det) tbody.appendChild(det);
        });
    });
});

// =========================================================
//  Разворачивание строки с деталями
// =========================================================
function toggleRequestDetails(id) {
    const row  = document.getElementById('details-' + id);
    const chev = document.getElementById('chevron-' + id);
    if (!row) return;
    const open = row.style.display === 'none';
    row.style.display  = open ? 'table-row' : 'none';
    chev.className = open ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
}

// =========================================================
//  Модальное окно создания заявки
// =========================================================
function openRequestModal()  { document.getElementById('requestModal').style.display = 'flex'; }
function closeRequestModal() {
    document.getElementById('requestModal').style.display = 'none';
    document.getElementById('templateFieldsContainer').innerHTML = '';
    document.getElementById('templateSelect').value = '';
    document.getElementById('requestTitle').value = '';
}

// =========================================================
//  Динамическая загрузка полей шаблона (AJAX)
// =========================================================
async function loadTemplateFields(templateId) {
    const container = document.getElementById('templateFieldsContainer');
    container.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Загрузка…</p>';

    if (!templateId) { container.innerHTML = ''; return; }

    try {
        const res  = await fetch(`handlers/get_template_fields.php?template_id=${templateId}`);
        const data = await res.json();
        if (!data.success) { container.innerHTML = `<p class="text-danger">${data.error}</p>`; return; }

        container.innerHTML = '';

        // Автоподстановка заголовка из title_template (если поле темы пусто)
        const titleInput = document.getElementById('requestTitle');
        if (!titleInput.value && data.template.title_template) {
            const tpl = data.template.title_template.replace(/\{[^}]+\}/g, '').trim();
            if (tpl) titleInput.placeholder = tpl;
        }

        if (!data.fields || data.fields.length === 0) return;

        data.fields.forEach(field => {
            const group = document.createElement('div');
            group.className = 'form-group';

            const label = document.createElement('label');
            label.innerHTML = `${escHtml(field.field_label)}${field.is_required ? ' <span class="required">*</span>' : ''}`;
            if (field.help_text) label.title = field.help_text;
            group.appendChild(label);

            let input;
            const name = `field_${field.field_name}`;

            switch (field.field_type) {
                case 'textarea':
                    input = document.createElement('textarea');
                    input.rows = 3;
                    break;
                case 'select':
                    input = document.createElement('select');
                    const emptyOpt = document.createElement('option');
                    emptyOpt.value = '';
                    emptyOpt.textContent = '— выберите —';
                    input.appendChild(emptyOpt);
                    if (field.options && field.options.length) {
                        field.options.forEach(opt => {
                            const o = document.createElement('option');
                            o.value = opt.value;
                            o.textContent = opt.display_text;
                            input.appendChild(o);
                        });
                    }
                    break;
                case 'checkbox':
                    const wrap = document.createElement('label');
                    wrap.className = 'checkbox-label';
                    input = document.createElement('input');
                    input.type = 'checkbox';
                    input.value = '1';
                    wrap.appendChild(input);
                    wrap.appendChild(document.createTextNode(' ' + field.field_label));
                    group.appendChild(wrap);
                    input.name = name;
                    group.appendChild(document.createElement('br'));
                    container.appendChild(group);
                    return;
                case 'number':
                    input = document.createElement('input');
                    input.type = 'number';
                    break;
                case 'date':
                    input = document.createElement('input');
                    input.type = 'date';
                    break;
                case 'file':
                    input = document.createElement('input');
                    input.type = 'file';
                    break;
                default:
                    input = document.createElement('input');
                    input.type = 'text';
            }

            input.name = name;
            input.className = 'form-control';
            if (field.placeholder) input.placeholder = field.placeholder;
            if (field.is_required) input.required = true;

            // Паттерн валидации
            if (field.validation_rules && field.validation_rules.pattern) {
                input.pattern = field.validation_rules.pattern;
                if (field.validation_rules.pattern_message) {
                    input.title = field.validation_rules.pattern_message;
                }
            }

            group.appendChild(input);

            // Подсказка
            if (field.help_text) {
                const hint = document.createElement('small');
                hint.className = 'form-hint';
                hint.textContent = field.help_text;
                group.appendChild(hint);
            }

            container.appendChild(group);
        });

    } catch(e) {
        container.innerHTML = '<p class="text-danger">Ошибка загрузки полей</p>';
    }
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// =========================================================
//  Поиск по Enter в строке поиска
// =========================================================
document.getElementById('filterForm')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') e.target.closest('form').submit();
});
</script>
