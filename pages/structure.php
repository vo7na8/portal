<?php
if (!hasPermission($pdo, 'view_structure')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db          = Database::getInstance();
$canAddDiv   = hasPermission($pdo, 'add_division');
$canEditDiv  = hasPermission($pdo, 'edit_division');
$canDelDiv   = hasPermission($pdo, 'delete_division');
$canAddDep   = hasPermission($pdo, 'add_department');
$canEditDep  = hasPermission($pdo, 'edit_department');
$canDelDep   = hasPermission($pdo, 'delete_department');

$divisions = $db->select(
    'SELECT dv.*,
        COUNT(DISTINCT d.id) AS dept_count,
        COUNT(DISTINCT e.id) AS emp_count
     FROM divisions dv
     LEFT JOIN departments d ON d.division_id = dv.id
     LEFT JOIN employees e ON e.department_id = d.id AND e.is_active = 1
     WHERE dv.parent_id IS NULL
     GROUP BY dv.id ORDER BY dv.sort_order, dv.name'
);
$allDivisions = $db->select('SELECT id, name FROM divisions ORDER BY name');
?>
<h2 class="section-title">Структура организации</h2>

<?php if ($canAddDiv): ?>
<div class="form-container">
    <div class="card-title mb-2">Добавить подразделение</div>
    <form method="post" action="handlers/add_division.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Название *</label><input type="text" name="name" required maxlength="255"></div>
            <div class="form-group"><label>Краткое название</label><input type="text" name="short_name" maxlength="50"></div>
            <div class="form-group">
                <label>Входит в подразделение (родитель)</label>
                <select name="parent_id">
                    <option value="">— верхний уровень —</option>
                    <?php foreach ($allDivisions as $dv): ?>
                    <option value="<?= (int)$dv['id'] ?>"><?= e($dv['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<?php if (empty($divisions)): ?>
<div class="empty-state"><i class="fas fa-sitemap"></i><p>Структура пуста</p></div>
<?php else: ?>
<div class="item-list">
<?php foreach ($divisions as $dv):
    $dvId   = (int)$dv['id'];
    $depts  = $db->select(
        'SELECT d.*,
            COUNT(e.id) AS emp_count
         FROM departments d
         LEFT JOIN employees e ON e.department_id = d.id AND e.is_active = 1
         WHERE d.division_id = ?
         GROUP BY d.id ORDER BY d.sort_order, d.name',
        [$dvId]
    );
?>
<div class="card mb-1">
    <!-- Подразделение -->
    <div class="item-row">
        <div style="flex:1">
            <div style="font-weight:600;font-size:1.05rem"><?= e($dv['name']) ?>
                <?php if ($dv['short_name']): ?><span class="text-muted" style="font-size:.85rem;font-weight:400"> (<?= e($dv['short_name']) ?>)</span><?php endif; ?>
            </div>
            <div class="text-muted" style="font-size:.82rem">
                <i class="fas fa-door-open"></i> Отделений: <?= (int)$dv['dept_count'] ?>
                &nbsp;<i class="fas fa-user"></i> Сотрудников: <?= (int)$dv['emp_count'] ?>
            </div>
        </div>
        <div class="item-actions">
            <?php if ($canAddDep): ?>
            <button class="btn btn-secondary btn-sm" data-toggle-comments="adddep-<?= $dvId ?>" title="Добавить отделение">
                <i class="fas fa-folder-plus"></i>
            </button>
            <?php endif; ?>
            <?php if ($canEditDiv): ?>
            <button class="btn btn-secondary btn-sm" data-toggle-comments="divedit-<?= $dvId ?>">
                <i class="fas fa-pen"></i>
            </button>
            <?php endif; ?>
            <?php if ($canDelDiv): ?>
            <form method="post" action="handlers/delete_division.php" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $dvId ?>">
                <button class="btn btn-danger btn-sm" data-confirm="Удалить подразделение?">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Форма добавления отделения -->
    <?php if ($canAddDep): ?>
    <div id="adddep-<?= $dvId ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border);background:var(--bg-content)">
        <div class="card-title mb-1" style="font-size:.9rem">Новое отделение</div>
        <form method="post" action="handlers/add_department.php">
            <?= csrf_field() ?>
            <input type="hidden" name="division_id" value="<?= $dvId ?>">
            <div class="form-row">
                <div class="form-group"><label>Название *</label><input type="text" name="name" required maxlength="255"></div>
                <div class="form-group"><label>Краткое</label><input type="text" name="short_name" maxlength="50"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Добавить</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($canEditDiv): ?>
    <div id="divedit-<?= $dvId ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border);background:var(--bg-content)">
        <form method="post" action="handlers/edit_division.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $dvId ?>">
            <div class="form-row">
                <div class="form-group"><label>Название</label><input type="text" name="name" value="<?= e($dv['name']) ?>" required maxlength="255"></div>
                <div class="form-group"><label>Краткое</label><input type="text" name="short_name" value="<?= e($dv['short_name'] ?? '') ?>" maxlength="50"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Сохранить</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Отделения -->
    <?php if (!empty($depts)): ?>
    <div style="padding:.4rem 1.2rem .8rem;">
    <?php foreach ($depts as $dep):
        $depId = (int)$dep['id'];
    ?>
    <div style="border:1px solid var(--border);border-radius:var(--radius);padding:.6rem .9rem;margin-top:.5rem;background:var(--card-bg)">
        <div class="flex" style="justify-content:space-between;flex-wrap:wrap;gap:.4rem">
            <div>
                <span style="font-weight:500"><?= e($dep['name']) ?></span>
                <?php if ($dep['short_name']): ?><span class="text-muted" style="font-size:.82rem"> (<?= e($dep['short_name']) ?>)</span><?php endif; ?>
                <span class="text-muted" style="font-size:.8rem;margin-left:.5rem">&bull; <?= (int)$dep['emp_count'] ?> чел.</span>
            </div>
            <div class="flex" style="gap:.3rem">
                <?php if ($canEditDep): ?>
                <button class="btn btn-secondary btn-sm" data-toggle-comments="depedit-<?= $depId ?>">
                    <i class="fas fa-pen"></i>
                </button>
                <?php endif; ?>
                <?php if ($canDelDep): ?>
                <form method="post" action="handlers/delete_department.php" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $depId ?>">
                    <button class="btn btn-danger btn-sm" data-confirm="Удалить отделение?">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($canEditDep): ?>
        <div id="depedit-<?= $depId ?>" style="display:none;margin-top:.6rem">
            <form method="post" action="handlers/edit_department.php">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $depId ?>">
                <div class="form-row">
                    <div class="form-group"><label>Название</label><input type="text" name="name" value="<?= e($dep['name']) ?>" required maxlength="255"></div>
                    <div class="form-group"><label>Краткое</label><input type="text" name="short_name" value="<?= e($dep['short_name'] ?? '') ?>" maxlength="50"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Сохранить</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; endif; ?>
</div>
