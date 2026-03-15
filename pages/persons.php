<?php
if (!hasPermission($pdo, 'view_persons')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db        = Database::getInstance();
$canAdd    = hasPermission($pdo, 'add_person');
$canEdit   = hasPermission($pdo, 'edit_person');
$canDelete = hasPermission($pdo, 'delete_person');

$search   = trim($_GET['q'] ?? '');
$sqlWhere = $search
    ? "WHERE (p.last_name LIKE ? OR p.first_name LIKE ? OR p.middle_name LIKE ?)"
    : '';
$likeParam = "%{$search}%";
$sqlParams = $search ? [$likeParam, $likeParam, $likeParam] : [];

$persons = $db->select(
    "SELECT p.*,
        GROUP_CONCAT(e.position || ' (' || COALESCE(d.short_name, d.name) || ')', '; ') AS positions
     FROM persons p
     LEFT JOIN employees e ON e.person_id = p.id AND e.is_active = 1
     LEFT JOIN departments d ON d.id = e.department_id
     {$sqlWhere}
     GROUP BY p.id
     ORDER BY p.last_name, p.first_name",
    $sqlParams
);

// --- ОПТИМИЗАЦИЯ: загружаем [ДО цикла] все должности и отделения одним запросом ---
$allDepts = $db->select(
    'SELECT d.id, d.name, d.short_name, dv.name AS div_name
     FROM departments d LEFT JOIN divisions dv ON dv.id=d.division_id ORDER BY dv.name, d.name'
);

$rawEmployees = $db->select(
    'SELECT e.*, e.id AS emp_id, d.name AS dept_name, dv.name AS div_name
     FROM employees e
     LEFT JOIN departments d ON d.id = e.department_id
     LEFT JOIN divisions dv ON dv.id = d.division_id
     ORDER BY e.is_active DESC, e.hire_date DESC'
);
// Группируем по person_id
$employeesByPerson = [];
foreach ($rawEmployees as $emp) {
    $employeesByPerson[(int)$emp['person_id']][] = $emp;
}
?>
<h2 class="section-title">Люди</h2>

<!-- Поиск -->
<form method="get" action="" class="flex" style="gap:.5rem;margin-bottom:1rem">
    <input type="hidden" name="page" value="persons">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Поиск по ФИО…"
        style="flex:1;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius-xl);padding:.5rem 1rem;color:var(--text-primary);outline:none">
    <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button>
    <?php if ($search): ?><a href="?page=persons" class="btn btn-secondary">Сброс</a><?php endif; ?>
</form>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-title mb-2">Добавить человека</div>
    <form method="post" action="handlers/add_person.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Фамилия *</label><input type="text" name="last_name" required maxlength="100"></div>
            <div class="form-group"><label>Имя *</label><input type="text" name="first_name" required maxlength="100"></div>
            <div class="form-group"><label>Отчество</label><input type="text" name="middle_name" maxlength="100"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Дата рождения</label><input type="date" name="birth_date"></div>
            <div class="form-group" style="display:flex;align-items:center;gap:.6rem;padding-top:1.6rem">
                <input type="checkbox" name="has_eds" value="1" id="has_eds_new" style="width:1rem;height:1rem">
                <label for="has_eds_new" style="margin:0">Есть ЭЦП</label>
            </div>
            <div class="form-group"><label>Номер сертификата ЭЦП</label><input type="text" name="eds_cert_number" maxlength="100"></div>
            <div class="form-group"><label>ЭЦП действительна до</label><input type="date" name="eds_valid_until"></div>
        </div>
        <div class="form-group"><label>Примечание</label><input type="text" name="note" maxlength="500"></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<?php if (empty($persons)): ?>
<div class="empty-state"><i class="fas fa-id-card"></i><p><?php echo $search ? 'Никто не найден' : 'Людей пока нет'; ?></p></div>
<?php else: ?>
<div class="item-list">
<?php foreach ($persons as $p):
    $pid  = (int)$p['id'];
    $fio  = e($p['last_name']) . ' ' . e($p['first_name']) . (trim($p['middle_name'] ?? '') ? ' ' . e($p['middle_name']) : '');
    // ОПТИМИЗАЦИЯ: берём из предзагруженного массива
    $deps = $employeesByPerson[$pid] ?? [];
?>
<div class="card mb-1">
    <div class="item-row">
        <div style="flex:1;min-width:0">
            <div class="flex" style="gap:.5rem;flex-wrap:wrap;align-items:center">
                <span style="font-weight:600"><?= $fio ?></span>
                <?php if ($p['has_eds']): ?>
                <span class="badge badge-done" title="ЭЦП: <?= e($p['eds_cert_number'] ?? '') ?>">ЭЦП ✔</span>
                <?php else: ?>
                <span class="badge badge-closed">Нет ЭЦП</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($p['birth_date'])): ?>
            <div class="text-muted" style="font-size:.8rem">ДР: <?= format_date($p['birth_date']) ?></div>
            <?php endif; ?>
            <?php if (!empty($p['positions'])): ?>
            <div class="text-muted" style="font-size:.82rem;margin-top:.2rem">
                <i class="fas fa-briefcase"></i> <?= e($p['positions']) ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="item-actions">
            <button class="btn btn-secondary btn-sm" data-toggle-comments="pemp-<?= $pid ?>" title="Должности">
                <i class="fas fa-briefcase"></i> <span><?= count($deps) ?></span>
            </button>
            <?php if ($canEdit): ?>
            <button class="btn btn-secondary btn-sm" data-toggle-comments="pedit-<?= $pid ?>" title="Редактировать">
                <i class="fas fa-pen"></i>
            </button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
            <form method="post" action="handlers/delete_person.php" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $pid ?>">
                <button class="btn btn-danger btn-sm" data-confirm="Удалить <?= $fio ?>?">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Должности -->
    <div id="pemp-<?= $pid ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border);background:var(--bg-content)">
        <div class="card-title mb-1" style="font-size:.9rem">Должности</div>
        <?php if (hasPermission($pdo, 'add_employee')): ?>
        <form method="post" action="handlers/add_employee.php" style="margin-bottom:.8rem">
            <?= csrf_field() ?>
            <input type="hidden" name="person_id" value="<?= $pid ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Отделение</label>
                    <select name="department_id">
                        <option value="">— не указано —</option>
                        <?php foreach ($allDepts as $d): ?>
                        <option value="<?= (int)$d['id'] ?>"><?= e($d['div_name'] ?? '') ?> / <?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Должность *</label><input type="text" name="position" required maxlength="255"></div>
                <div class="form-group"><label>Номер договора</label><input type="text" name="contract_number" maxlength="100"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Принят на работу</label><input type="date" name="hire_date"></div>
                <div class="form-group"><label>Уволен</label><input type="date" name="fire_date"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Добавить должность</button>
        </form>
        <?php endif; ?>
        <?php if (empty($deps)): ?>
        <p class="text-muted" style="font-size:.85rem">Должностей нет</p>
        <?php else: ?>
        <?php foreach ($deps as $emp): ?>
        <div style="border:1px solid var(--border);border-radius:var(--radius);padding:.5rem .8rem;margin-top:.5rem;background:var(--card-bg)">
            <div class="item-row" style="padding:0">
                <div style="flex:1;font-size:.88rem">
                    <span style="font-weight:500"><?= e($emp['position']) ?></span>
                    <?php if (!$emp['is_active']): ?><span class="badge badge-closed" style="font-size:.7rem">Уволен</span><?php endif; ?>
                    <div class="text-muted" style="font-size:.8rem">
                        <?= e($emp['div_name'] ?? '') ?><?= !empty($emp['div_name']) ? ' / ' : '' ?><?= e($emp['dept_name'] ?? 'Без отделения') ?>
                        <?php if ($emp['contract_number']): ?> &bull; Дог.: <?= e($emp['contract_number']) ?><?php endif; ?>
                        <?php if ($emp['hire_date']): ?> &bull; С: <?= format_date($emp['hire_date']) ?><?php endif; ?>
                        <?php if ($emp['fire_date']): ?> по <?= format_date($emp['fire_date']) ?><?php endif; ?>
                    </div>
                </div>
                <div class="flex" style="gap:.3rem">
                    <?php if (hasPermission($pdo, 'edit_employee')): ?>
                    <button class="btn btn-secondary btn-sm" data-toggle-comments="empedit-<?= (int)$emp['emp_id'] ?>" title="Редактировать должность">
                        <i class="fas fa-pen"></i>
                    </button>
                    <form method="post" action="handlers/toggle_employee.php" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$emp['emp_id'] ?>">
                        <button class="btn btn-secondary btn-sm" title="<?= $emp['is_active'] ? 'Отметить уволенным' : 'Восстановить' ?>">
                            <i class="fas <?= $emp['is_active'] ? 'fa-user-xmark' : 'fa-user-check' ?>"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if (hasPermission($pdo, 'delete_employee')): ?>
                    <form method="post" action="handlers/delete_employee.php" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$emp['emp_id'] ?>">
                        <button class="btn btn-danger btn-sm" data-confirm="Удалить должность?">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (hasPermission($pdo, 'edit_employee')): ?>
            <div id="empedit-<?= (int)$emp['emp_id'] ?>" style="display:none;margin-top:.6rem;padding-top:.6rem;border-top:1px solid var(--border)">
                <form method="post" action="handlers/edit_employee.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$emp['emp_id'] ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Отделение</label>
                            <select name="department_id">
                                <option value="">— не указано —</option>
                                <?php foreach ($allDepts as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= (int)$emp['department_id'] === (int)$d['id'] ? 'selected' : '' ?>>
                                    <?= e($d['div_name'] ?? '') ?> / <?= e($d['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Должность *</label><input type="text" name="position" value="<?= e($emp['position']) ?>" required maxlength="255"></div>
                        <div class="form-group"><label>Номер договора</label><input type="text" name="contract_number" value="<?= e($emp['contract_number'] ?? '') ?>" maxlength="100"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Принят на работу</label><input type="date" name="hire_date" value="<?= e($emp['hire_date'] ?? '') ?>"></div>
                        <div class="form-group"><label>Уволен</label><input type="date" name="fire_date" value="<?= e($emp['fire_date'] ?? '') ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Сохранить</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($canEdit): ?>
    <div id="pedit-<?= $pid ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border);background:var(--bg-content)">
        <div class="card-title mb-1" style="font-size:.9rem">Редактировать</div>
        <form method="post" action="handlers/edit_person.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $pid ?>">
            <div class="form-row">
                <div class="form-group"><label>Фамилия *</label><input type="text" name="last_name" value="<?= e($p['last_name']) ?>" required maxlength="100"></div>
                <div class="form-group"><label>Имя *</label><input type="text" name="first_name" value="<?= e($p['first_name']) ?>" required maxlength="100"></div>
                <div class="form-group"><label>Отчество</label><input type="text" name="middle_name" value="<?= e($p['middle_name'] ?? '') ?>" maxlength="100"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Дата рождения</label><input type="date" name="birth_date" value="<?= e($p['birth_date'] ?? '') ?>"></div>
                <div class="form-group" style="display:flex;align-items:center;gap:.6rem;padding-top:1.6rem">
                    <input type="checkbox" name="has_eds" value="1" id="has_eds_<?= $pid ?>" style="width:1rem;height:1rem" <?= $p['has_eds'] ? 'checked' : '' ?>>
                    <label for="has_eds_<?= $pid ?>" style="margin:0">Есть ЭЦП</label>
                </div>
                <div class="form-group"><label>Номер сертификата</label><input type="text" name="eds_cert_number" value="<?= e($p['eds_cert_number'] ?? '') ?>" maxlength="100"></div>
                <div class="form-group"><label>ЭЦП действительна до</label><input type="date" name="eds_valid_until" value="<?= e($p['eds_valid_until'] ?? '') ?>"></div>
            </div>
            <div class="form-group"><label>Примечание</label><input type="text" name="note" value="<?= e($p['note'] ?? '') ?>" maxlength="500"></div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Сохранить</button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
