<?php
// Страница профиля текущего пользователя
$db   = Database::getInstance();
$uid  = (int)$_SESSION['user_id'];
$user = $db->selectOne(
    'SELECT u.*, r.name as role_name, r.description as role_desc FROM users u LEFT JOIN roles r ON u.role_id=r.id WHERE u.id=?',
    [$uid]
);
if (!$user) { echo '<div class="empty-state"><i class="fas fa-user-slash"></i><p>Пользователь не найден.</p></div>'; return; }

// Статистика пользователя
$myRequests = $db->selectOne('SELECT COUNT(*) cnt, SUM(status=\'\u0432\u044b\u043f\u043e\u043b\u043d\u0435\u043d\u0430\') done FROM requests WHERE author_id=?', [$uid]);
$myAssigned = $db->selectOne('SELECT COUNT(*) cnt FROM requests WHERE assigned_to=? AND status != \'\u0432\u044b\u043f\u043e\u043b\u043d\u0435\u043d\u0430\'', [$uid]);

// Все права пользователя
$userPerms = $db->select(
    'SELECT p.name, p.description FROM permissions p
     JOIN role_permissions rp ON p.id=rp.permission_id
     WHERE rp.role_id=? ORDER BY p.name',
    [$user['role_id'] ?? 0]
);

// Смена пароля
$pwdError   = $session->getFlash('pwd_error');
$pwdSuccess = $session->getFlash('pwd_success');
?>
<h2 class="section-title">Мой профиль</h2>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.4rem" class="mb-2">

    <!-- Инфо -->
    <div class="card">
        <div style="display:flex;align-items:center;gap:1.2rem;margin-bottom:1.2rem">
            <div class="user-avatar" style="width:56px;height:56px;font-size:1.6rem;flex-shrink:0">
                <?= mb_strtoupper(mb_substr(e($user['full_name']),0,1)) ?>
            </div>
            <div>
                <div style="font-size:1.15rem;font-weight:500"><?= e($user['full_name']) ?></div>
                <div class="text-muted" style="font-size:.85rem">@<?= e($user['username']) ?></div>
                <span class="user-role-badge" style="margin-top:.3rem"><?= e($user['role_name'] ?? 'Нет роли') ?></span>
            </div>
        </div>
        <?php if ($user['role_desc']): ?>
        <p class="text-muted" style="font-size:.85rem;margin-bottom:.8rem"><?= e($user['role_desc']) ?></p>
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem">
            <div class="stat-card" style="padding:1rem">
                <div class="stat-number" style="font-size:1.8rem"><?= (int)($myRequests['cnt'] ?? 0) ?></div>
                <div class="stat-label">Моих заявок</div>
            </div>
            <div class="stat-card" style="padding:1rem">
                <div class="stat-number" style="font-size:1.8rem;color:var(--accent-orange)"><?= (int)($myAssigned['cnt'] ?? 0) ?></div>
                <div class="stat-label">В работе</div>
            </div>
        </div>
    </div>

    <!-- Смена пароля -->
    <div class="card">
        <div class="card-title mb-2"><i class="fas fa-key" style="margin-right:.4rem;color:var(--accent)"></i>Сменить пароль</div>
        <?php if ($pwdError):  ?><div class="alert alert-error mb-2"><?= e($pwdError) ?></div><?php endif; ?>
        <?php if ($pwdSuccess):?><div class="alert alert-success mb-2"><?= e($pwdSuccess) ?></div><?php endif; ?>
        <form method="post" action="handlers/change_password.php">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Текущий пароль</label>
                <input type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label>Новый пароль</label>
                <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Подтверждение</label>
                <input type="password" name="new_password_confirmation" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-lock"></i> Сменить
            </button>
        </form>
    </div>

</div>

<!-- Права -->
<div class="card">
    <div class="card-title mb-2">
        <i class="fas fa-shield-halved" style="margin-right:.4rem;color:var(--accent-green)"></i>Мои разрешения
        <span class="badge badge-done" style="margin-left:.5rem"><?= count($userPerms) ?></span>
    </div>
    <?php if (empty($userPerms)): ?>
    <p class="text-muted">Нет назначенных прав</p>
    <?php else: ?>
    <div style="display:flex;flex-wrap:wrap;gap:.45rem">
        <?php foreach ($userPerms as $p): ?>
        <span class="badge badge-new"><?= e($p['name']) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
