<?php
/**
 * Управление шаблонами заявок — доступно только администраторам
 */
if (!hasPermission($pdo, 'manage_templates')) {
    echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён. Требуется право manage_templates.</p></div>';
    return;
}

$db        = Database::getInstance();
$templates = $db->select('SELECT * FROM request_templates ORDER BY sort_order, id');
$fieldTypes = [
    'text'     => 'Текстовое поле',
    'textarea' => 'Многострочный текст',
    'select'   => 'Выпадающий список',
    'checkbox' => 'Чекбокс',
    'number'   => 'Число',
    'date'     => 'Дата',
];
// Все справочники НСИ для выбора в select
$nsiDicts  = $db->select('SELECT code, name FROM nsi_dictionaries WHERE is_active=1 ORDER BY sort_order, name');
$nsiOptions = array_merge(
    [['code' => 'department_equipment', 'name' => 'Техника отдела (динамически)']],
    $nsiDicts
);

// Предупреждения из флэш
$flash_success = flash('success');
$flash_error   = flash('error');
?>
<div class="section-header">
    <h2 class="section-title">Шаблоны заявок</h2>
    <button class="btn btn-primary" onclick="togglePanel('tpl-create-panel')">
        <i class="fas fa-plus"></i> Создать шаблон
    </button>
</div>

<?php if ($flash_success): ?>
    <div class="flash flash-success"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
    <div class="flash flash-error"><?= e($flash_error) ?></div>
<?php endif; ?>

<!-- Форма создания шаблона -->
<div id="tpl-create-panel" class="form-container" style="display:none">
    <div class="card-title mb-2">Новый шаблон</div>
    <form method="post" action="handlers/add_template.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label>Код категории (2 символа, лат.) *</label>
                <input type="text" name="category_code" required maxlength="10"
                       pattern="[A-Z0-9]{2,10}" title="Только заглавные латинские буквы и цифры, 2-10 символов"
                       placeholder="IT, PT, HR…" style="text-transform:uppercase">
            </div>
            <div class="form-group">
                <label>Название категории *</label>
                <input type="text" name="category_name" required maxlength="255" placeholder="Поломка техники…">
            </div>
            <div class="form-group">
                <label>FA-иконка (fa-...)</label>
                <input type="text" name="icon" maxlength="50" placeholder="fa-file-alt" value="fa-file-alt">
            </div>
            <div class="form-group">
                <label>Порядок сортировки</label>
                <input type="number" name="sort_order" value="0" min="0">
            </div>
        </div>
        <div class="form-group">
            <label>Шаблон заголовка (переменные в фигурных скобках {field_name})</label>
            <input type="text" name="title_template" maxlength="500" placeholder="Поломка: {equipment_type} — {equipment_name}">
        </div>
        <div class="form-group">
            <label>Описание шаблона</label>
            <input type="text" name="description" maxlength="500" placeholder="Краткое описание…">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
        <button type="button" class="btn btn-secondary" onclick="togglePanel('tpl-create-panel')">Отмена</button>
    </form>
</div>

<!-- Список шаблонов -->
<?php if (empty($templates)): ?>
    <div class="empty-state"><i class="fas fa-layer-group"></i><p>Шаблонов пока нет. Создайте первый.</p></div>
<?php else: ?>
<div class="item-list mt-2">
<?php foreach ($templates as $tpl):
    $tid    = (int)$tpl['id'];
    $fields = $db->select('SELECT * FROM template_fields WHERE template_id=? ORDER BY sort_order', [$tid]);
?>
<div class="card mb-1">
    <!-- Шапка -->
    <div class="item-row">
        <div style="display:flex;align-items:center;gap:.8rem;flex:1">
            <div class="badge badge-new" style="font-size:1rem;padding:.4rem .6rem">
                <i class="fas <?= e($tpl['icon'] ?: 'fa-file-alt') ?>"></i>
            </div>
            <div>
                <div style="font-weight:600">
                    <?= e($tpl['category_name']) ?>
                    <code style="font-size:.75rem;background:var(--bg-content);padding:.1rem .4rem;border-radius:4px;margin-left:.4rem"><?= e($tpl['category_code']) ?></code>
                    <?php if (!$tpl['is_active']): ?>
                        <span class="badge badge-closed" style="margin-left:.4rem">отключен</span>
                    <?php endif; ?>
                </div>
                <?php if ($tpl['description']): ?>
                    <div class="text-muted" style="font-size:.83rem;margin-top:.15rem"><?= e($tpl['description']) ?></div>
                <?php endif; ?>
                <?php if ($tpl['title_template']): ?>
                    <div class="text-muted" style="font-size:.78rem;margin-top:.1rem">
                        <i class="fas fa-heading" style="font-size:.7rem"></i> <?= e($tpl['title_template']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="item-actions" style="gap:.4rem">
            <!-- Тоггл активности -->
            <form method="post" action="handlers/toggle_template.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $tid ?>">
                <button type="submit" class="btn btn-secondary btn-sm"
                    title="<?= $tpl['is_active'] ? 'Отключить' : 'Включить' ?>">
                    <i class="fas <?= $tpl['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                </button>
            </form>
            <!-- Редактировать -->
            <button class="btn btn-secondary btn-sm" onclick="togglePanel('tpl-edit-<?= $tid ?>')">
                <i class="fas fa-pen"></i>
            </button>
            <!-- Удалить -->
            <form method="post" action="handlers/delete_template.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $tid ?>">
                <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Удалить шаблон &laquo;<?= e($tpl['category_name']) ?>&raquo; и все его поля?">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            <!-- Аккордеон полей -->
            <button class="btn btn-secondary btn-sm" onclick="togglePanel('tpl-fields-<?= $tid ?>')">
                <i class="fas fa-list-check"></i>
                <span class="badge badge-new" style="font-size:.65rem;margin-left:.3rem"><?= count($fields) ?></span>
            </button>
        </div>
    </div>

    <!-- Форма редактирования шаблона -->
    <div id="tpl-edit-<?= $tid ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border)">
        <div class="card-title mb-2">Редактировать шаблон</div>
        <form method="post" action="handlers/edit_template.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $tid ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Код *</label>
                    <input type="text" name="category_code" required maxlength="10"
                        pattern="[A-Z0-9]{2,10}" value="<?= e($tpl['category_code']) ?>"
                        style="text-transform:uppercase">
                </div>
                <div class="form-group">
                    <label>Название *</label>
                    <input type="text" name="category_name" required maxlength="255" value="<?= e($tpl['category_name']) ?>">
                </div>
                <div class="form-group">
                    <label>FA-иконка</label>
                    <input type="text" name="icon" maxlength="50" value="<?= e($tpl['icon']) ?>">
                </div>
                <div class="form-group">
                    <label>Порядок</label>
                    <input type="number" name="sort_order" value="<?= (int)$tpl['sort_order'] ?>" min="0">
                </div>
            </div>
            <div class="form-group">
                <label>Шаблон заголовка</label>
                <input type="text" name="title_template" maxlength="500" value="<?= e($tpl['title_template']) ?>">
            </div>
            <div class="form-group">
                <label>Описание</label>
                <input type="text" name="description" maxlength="500" value="<?= e($tpl['description']) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Сохранить</button>
        </form>
    </div>

    <!-- Управление полями -->
    <div id="tpl-fields-<?= $tid ?>" style="display:none;border-top:1px solid var(--border)">
        <div style="padding:.8rem 1.2rem">
            <div class="card-title mb-2">Поля шаблона</div>

            <!-- Текущие поля -->
            <?php if (empty($fields)): ?>
                <p class="text-muted" style="font-size:.85rem">Полей пока нет.</p>
            <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;margin-bottom:1rem">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);color:var(--text-muted)">
                        <th style="padding:.4rem .6rem;text-align:left">#</th>
                        <th style="padding:.4rem .6rem;text-align:left">Поле</th>
                        <th style="padding:.4rem .6rem;text-align:left">Тип</th>
                        <th style="padding:.4rem .6rem;text-align:left">Обяз.•</th>
                        <th style="padding:.4rem .6rem;text-align:left">Источник</th>
                        <th style="padding:.4rem .6rem"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($fields as $f): $fid = (int)$f['id']; ?>
                <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:.4rem .6rem;color:var(--text-muted)"><?= (int)$f['sort_order'] ?></td>
                    <td style="padding:.4rem .6rem">
                        <strong><?= e($f['field_label']) ?></strong><br>
                        <code style="font-size:.75rem"><?= e($f['field_name']) ?></code>
                        <?php if ($f['help_text']): ?>
                            <span style="font-size:.75rem;color:var(--text-muted)"> — <?= e($f['help_text']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.4rem .6rem">
                        <span class="badge badge-progress"><?= e($fieldTypes[$f['field_type']] ?? $f['field_type']) ?></span>
                    </td>
                    <td style="padding:.4rem .6rem;text-align:center">
                        <?= $f['is_required'] ? '<i class="fas fa-check text-success"></i>' : '—' ?>
                    </td>
                    <td style="padding:.4rem .6rem;font-size:.78rem;color:var(--text-muted)">
                        <?= e($f['options_source'] ?: ($f['options_static'] ? 'static' : '—')) ?>
                    </td>
                    <td style="padding:.4rem .6rem">
                        <form method="post" action="handlers/delete_template_field.php" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $fid ?>">
                            <input type="hidden" name="template_id" value="<?= $tid ?>">
                            <button class="btn btn-danger btn-sm"
                                data-confirm="Удалить поле &laquo;<?= e($f['field_label']) ?>&raquo;?">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Форма добавления поля -->
            <div style="background:var(--bg-content);border:1px solid var(--border);border-radius:var(--radius-md);padding:1rem">
                <div class="card-title mb-1" style="font-size:.9rem">Добавить поле</div>
                <form method="post" action="handlers/add_template_field.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="template_id" value="<?= $tid ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Техн. название * <small>(лат., без пробелов)</small></label>
                            <input type="text" name="field_name" required maxlength="100"
                                pattern="[a-z_][a-z0-9_]*" title="Только a-z, 0-9, _"
                                placeholder="description, equipment_type…">
                        </div>
                        <div class="form-group">
                            <label>Название поля *</label>
                            <input type="text" name="field_label" required maxlength="255" placeholder="Описание проблемы…">
                        </div>
                        <div class="form-group">
                            <label>Тип поля *</label>
                            <select name="field_type" id="ft-<?= $tid ?>" onchange="toggleFieldOptions(this, <?= $tid ?>)">
                                <?php foreach ($fieldTypes as $k => $v): ?>
                                <option value="<?= e($k) ?>"><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Порядок</label>
                            <input type="number" name="sort_order" value="<?= count($fields) + 1 ?>" min="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Подсказка (placeholder)</label>
                            <input type="text" name="placeholder" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label>Подсказка (справка)</label>
                            <input type="text" name="help_text" maxlength="500">
                        </div>
                    </div>
                    <!-- Поля для типа select -->
                    <div id="field-select-opts-<?= $tid ?>" style="display:none">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Источник НСИ (если есть)</label>
                                <select name="options_source">
                                    <option value="">— статичный список —</option>
                                    <?php foreach ($nsiOptions as $nd): ?>
                                    <option value="<?= e($nd['code']) ?>"><?= e($nd['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Статич. опции (каждая с новой строки)</label>
                                <textarea name="options_static" rows="3"
                                    placeholder="Значение 1&#10;Значение 2…"></textarea>
                            </div>
                        </div>
                    </div>
                    <!-- Поля для типа text -->
                    <div id="field-text-opts-<?= $tid ?>" style="display:none">
                        <div class="form-group">
                            <label>Регецс валидации (regex)</label>
                            <input type="text" name="validation_pattern" maxlength="255"
                                placeholder="^\\d{3} \\d{3} \\d{3}$">
                        </div>
                        <div class="form-group">
                            <label>Сообщение ошибки</label>
                            <input type="text" name="validation_message" maxlength="255"
                                placeholder="Формат: nnn nnn nnn">
                        </div>
                    </div>
                    <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
                        <label style="display:inline-flex;align-items:center;gap:.4rem;cursor:pointer">
                            <input type="checkbox" name="is_required" value="1"> Обязательное
                        </label>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Добавить поле
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function toggleFieldOptions(select, tid) {
    const isSelect = select.value === 'select';
    const isText   = (select.value === 'text');
    document.getElementById('field-select-opts-' + tid).style.display = isSelect ? '' : 'none';
    document.getElementById('field-text-opts-' + tid).style.display   = isText   ? '' : 'none';
}
function togglePanel(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = (el.style.display === 'none' || !el.style.display) ? '' : 'none';
}
</script>
