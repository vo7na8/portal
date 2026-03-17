<?php
/**
 * pages/nsi_management.php — Управление справочниками НСИ
 * Доступно: пользователи с правом manage_nsi (Администратор)
 */
if (!hasPermission($pdo, 'manage_nsi') && !hasPermission($pdo, 'view_nsi')) {
    echo '<div class="alert alert-danger">Нет доступа</div>';
    return;
}

$canEdit = hasPermission($pdo, 'manage_nsi');

// Загружаем все справочники
$dictionaries = $pdo->query('
    SELECT id, code, name, description, is_active, sort_order,
           (SELECT COUNT(*) FROM nsi_values WHERE dictionary_id = nsi_dictionaries.id AND is_active = 1) AS value_count
    FROM nsi_dictionaries
    ORDER BY sort_order ASC, name ASC
')->fetchAll(PDO::FETCH_ASSOC);

// Активный справочник (из GET)
$activeDictId = (int)($_GET['dict'] ?? ($dictionaries[0]['id'] ?? 0));
$activeDict   = null;
$nsiValues    = [];

if ($activeDictId) {
    $stmt = $pdo->prepare('SELECT * FROM nsi_dictionaries WHERE id = ?');
    $stmt->execute([$activeDictId]);
    $activeDict = $stmt->fetch(PDO::FETCH_ASSOC);

    $vStmt = $pdo->prepare('
        SELECT id, value, display_text, parent_id, sort_order, is_active, metadata
        FROM nsi_values
        WHERE dictionary_id = ?
        ORDER BY sort_order ASC, display_text ASC
    ');
    $vStmt->execute([$activeDictId]);
    $nsiValues = $vStmt->fetchAll(PDO::FETCH_ASSOC);
}

$csrfToken = generateCsrf();
?>

<div class="page-header">
    <h2><i class="fas fa-database"></i> Справочники НСИ</h2>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary" onclick="openDictModal()">
        <i class="fas fa-plus"></i> Добавить справочник
    </button>
    <?php endif; ?>
</div>

<div class="nsi-layout">
    <!-- Левая панель: список справочников -->
    <div class="nsi-sidebar">
        <div class="nsi-sidebar-header">Справочники</div>
        <?php foreach ($dictionaries as $d): ?>
        <a href="?page=nsi_management&dict=<?= $d['id'] ?>"
           class="nsi-dict-item <?= $d['id'] == $activeDictId ? 'active' : '' ?> <?= !$d['is_active'] ? 'inactive' : '' ?>">
            <span class="dict-name"><?= htmlspecialchars($d['name']) ?></span>
            <span class="dict-count badge"><?= $d['value_count'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Правая панель: значения выбранного справочника -->
    <div class="nsi-content">
        <?php if ($activeDict): ?>
        <div class="nsi-content-header">
            <div>
                <h3><?= htmlspecialchars($activeDict['name']) ?>
                    <code class="dict-code"><?= htmlspecialchars($activeDict['code']) ?></code>
                    <?php if (!$activeDict['is_active']): ?>
                    <span class="badge badge-secondary">Неактивен</span>
                    <?php endif; ?>
                </h3>
                <?php if ($activeDict['description']): ?>
                <p class="text-muted"><?= htmlspecialchars($activeDict['description']) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($canEdit): ?>
            <div class="nsi-content-actions">
                <button class="btn btn-sm btn-outline" onclick="openDictModal(<?= $activeDict['id'] ?>)">
                    <i class="fas fa-pen"></i> Изменить
                </button>
                <button class="btn btn-sm btn-primary" onclick="openValueModal()">
                    <i class="fas fa-plus"></i> Добавить значение
                </button>
            </div>
            <?php endif; ?>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:60px">Сорт.</th>
                    <th>Код</th>
                    <th>Наименование</th>
                    <th style="width:90px">Статус</th>
                    <?php if ($canEdit): ?><th style="width:100px">Действия</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($nsiValues as $nv): ?>
            <tr class="<?= !$nv['is_active'] ? 'row-inactive' : '' ?>">
                <td><?= $nv['sort_order'] ?></td>
                <td><code><?= htmlspecialchars($nv['value']) ?></code></td>
                <td><?= htmlspecialchars($nv['display_text']) ?></td>
                <td>
                    <span class="badge <?= $nv['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                        <?= $nv['is_active'] ? 'Активно' : 'Архив' ?>
                    </span>
                </td>
                <?php if ($canEdit): ?>
                <td>
                    <button class="btn-icon" title="Редактировать"
                        onclick='openValueModal(<?= htmlspecialchars(json_encode($nv), ENT_QUOTES) ?>)'>
                        <i class="fas fa-pen"></i>
                    </button>
                    <?php if ($nv['is_active']): ?>
                    <button class="btn-icon text-danger" title="Деактивировать"
                        onclick="deleteNsiValue(<?= $nv['id'] ?>, '<?= htmlspecialchars($nv['display_text'], ENT_QUOTES) ?>')"
                    ><i class="fas fa-archive"></i></button>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($nsiValues)): ?>
            <tr><td colspan="<?= $canEdit ? 5 : 4 ?>" class="text-center text-muted">Нет значений</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-database fa-3x text-muted"></i>
            <p>Выберите справочник слева</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно: справочник -->
<div class="modal" id="dictModal" style="display:none">
    <div class="modal-dialog">
        <div class="modal-header">
            <h4 id="dictModalTitle">Справочник</h4>
            <button onclick="closeDictModal()" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="dictForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" id="dictId">
                <div class="form-group">
                    <label>Код <span class="required">*</span></label>
                    <input type="text" name="code" id="dictCode" class="form-control"
                           placeholder="equipment_types" required pattern="[a-zA-Z_][a-zA-Z0-9_]{1,48}">
                    <small class="form-hint">Буквы, цифры, _ — используется в шаблонах</small>
                </div>
                <div class="form-group">
                    <label>Наименование <span class="required">*</span></label>
                    <input type="text" name="name" id="dictName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" id="dictDescription" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Порядок сортировки</label>
                        <input type="number" name="sort_order" id="dictSortOrder" class="form-control" value="0">
                    </div>
                    <div class="form-group" id="dictActiveGroup" style="display:none">
                        <label>&nbsp;</label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" id="dictIsActive" checked>
                            Активен
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeDictModal()">Отмена</button>
            <button class="btn btn-primary" onclick="saveDict()">Сохранить</button>
        </div>
    </div>
</div>

<!-- Модальное окно: значение НСИ -->
<div class="modal" id="valueModal" style="display:none">
    <div class="modal-dialog">
        <div class="modal-header">
            <h4 id="valueModalTitle">Значение справочника</h4>
            <button onclick="closeValueModal()" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="valueForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" id="valueId">
                <input type="hidden" name="dictionary_id" value="<?= $activeDictId ?>">
                <div class="form-group" id="valueCodeGroup">
                    <label>Код значения <span class="required">*</span></label>
                    <input type="text" name="value" id="valueCode" class="form-control"
                           placeholder="pc" required pattern="[a-zA-Z0-9_\-]{1,100}">
                    <small class="form-hint">Латиница, цифры, _ и -</small>
                </div>
                <div class="form-group">
                    <label>Наименование <span class="required">*</span></label>
                    <input type="text" name="display_text" id="valueDisplay" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Порядок сортировки</label>
                        <input type="number" name="sort_order" id="valueSortOrder" class="form-control" value="0">
                    </div>
                    <div class="form-group" id="valueActiveGroup" style="display:none">
                        <label>&nbsp;</label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" id="valueIsActive" checked>
                            Активно
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeValueModal()">Отмена</button>
            <button class="btn btn-primary" onclick="saveValue()">Сохранить</button>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= $csrfToken ?>';
const ACTIVE_DICT_ID = <?= $activeDictId ?>;

// --- Справочники ---
function openDictModal(id) {
    document.getElementById('dictId').value = id || '';
    document.getElementById('dictCode').disabled = !!id;
    document.getElementById('dictActiveGroup').style.display = id ? '' : 'none';
    document.getElementById('dictModalTitle').textContent = id ? 'Редактировать справочник' : 'Новый справочник';
    if (!id) {
        document.getElementById('dictForm').reset();
    }
    document.getElementById('dictModal').style.display = 'flex';
}
function closeDictModal() { document.getElementById('dictModal').style.display = 'none'; }

async function saveDict() {
    const form = document.getElementById('dictForm');
    const data = new FormData(form);
    const id   = document.getElementById('dictId').value;
    const url  = id ? '../handlers/edit_nsi_dictionary.php' : '../handlers/add_nsi_dictionary.php';
    const res  = await fetch(url, { method: 'POST', body: data });
    const json = await res.json();
    if (json.success) { closeDictModal(); location.reload(); }
    else { alert(json.error || 'Ошибка'); }
}

// --- Значения ---
function openValueModal(row) {
    const isEdit = !!row;
    document.getElementById('valueId').value = isEdit ? row.id : '';
    document.getElementById('valueCode').value = isEdit ? row.value : '';
    document.getElementById('valueCode').disabled = isEdit;
    document.getElementById('valueCodeGroup').style.display = isEdit ? 'none' : '';
    document.getElementById('valueDisplay').value = isEdit ? row.display_text : '';
    document.getElementById('valueSortOrder').value = isEdit ? row.sort_order : 0;
    document.getElementById('valueActiveGroup').style.display = isEdit ? '' : 'none';
    if (isEdit) document.getElementById('valueIsActive').checked = row.is_active == 1;
    document.getElementById('valueModalTitle').textContent = isEdit ? 'Редактировать значение' : 'Новое значение';
    document.getElementById('valueModal').style.display = 'flex';
}
function closeValueModal() { document.getElementById('valueModal').style.display = 'none'; }

async function saveValue() {
    const form = document.getElementById('valueForm');
    const data = new FormData(form);
    const id   = document.getElementById('valueId').value;
    const url  = id ? '../handlers/edit_nsi_value.php' : '../handlers/add_nsi_value.php';
    const res  = await fetch(url, { method: 'POST', body: data });
    const json = await res.json();
    if (json.success) { closeValueModal(); location.reload(); }
    else { alert(json.error || 'Ошибка'); }
}

async function deleteNsiValue(id, name) {
    if (!confirm(`Деактивировать «${name}»?`)) return;
    const data = new FormData();
    data.append('csrf_token', CSRF);
    data.append('id', id);
    const res  = await fetch('../handlers/delete_nsi_value.php', { method: 'POST', body: data });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.error || 'Ошибка');
}
</script>
