<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$admins = $pdo->query("
    SELECT u.*, ut.title as user_type
    FROM users u JOIN user_types ut ON ut.id=u.user_type_id
    WHERE u.user_type_id IN (2,3) ORDER BY u.user_type_id, u.created_at DESC")->fetchAll();

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=? AND user_type_id IN (2,3)");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$csrfToken = generateCsrfToken();
$pageTitle = 'إدارة الإداريين';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col"><h1><i class="bi bi-person-badge-fill me-2"></i>إدارة الإداريين</h1></div>
            <div class="col-auto">
                <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#adminModal">
                    <i class="bi bi-plus-circle me-1"></i>إضافة إداري
                </button>
            </div>
        </div>
    </div>

    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead><tr><th>#</th><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($admins as $a): ?>
                    <tr>
                        <td class="text-muted small"><?= $a['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= getUserAvatar($a['profile_image'] ?? '') ?>" alt="" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                                <span class="fw-600 small"><?= htmlspecialchars($a['full_name']) ?></span>
                            </div>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($a['email']) ?></td>
                        <td>
                            <span class="badge <?= $a['user_type']==='manager'?'bg-danger':'bg-primary' ?>">
                                <?= $a['user_type']==='manager'?'مدير':'إداري' ?>
                            </span>
                        </td>
                        <td><span class="badge bg-<?= $a['status']==='active'?'success':'secondary' ?>"><?= $a['status']==='active'?'نشط':'موقوف' ?></span></td>
                        <td class="small text-muted"><?= $a['last_login'] ? formatDate($a['last_login']) : '—' ?></td>
                        <td class="d-flex gap-1">
                            <a href="?edit=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <?php if ($a['id'] !== getUserId()): ?>
                            <a href="/STD/backend/toggle_user_status.php?id=<?= $a['id'] ?>&action=<?= $a['status']==='active'?'suspend':'activate' ?>&token=<?= urlencode($csrfToken) ?>"
                               class="btn btn-sm btn-outline-<?= $a['status']==='active'?'warning':'success' ?>"
                               data-confirm="<?= $a['status']==='active'?'إيقاف':'تفعيل' ?> هذا الحساب؟">
                                <i class="bi bi-<?= $a['status']==='active'?'pause':'play' ?>-circle"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade <?= $edit?'show d-block':'' ?>" id="adminModal" tabindex="-1" <?= $edit?'style="background:rgba(0,0,0,.5);"':'' ?>>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit?'تعديل إداري':'إضافة إداري جديد' ?></h5>
                <a href="/STD/manager/admins.php" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST" action="/STD/backend/save_user.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                <input type="hidden" name="user_type" value="admin">
                <input type="hidden" name="redirect" value="admins">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">الاسم الكامل *</label>
                            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($edit['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">البريد الإلكتروني *</label>
                            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($edit['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">الدور</label>
                            <select name="role_type" class="form-select">
                                <option value="admin" <?= ($edit['user_type_id']??2)==2?'selected':'' ?>>إداري</option>
                                <option value="manager" <?= ($edit['user_type_id']??2)==3?'selected':'' ?>>مدير</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($edit['status']??'active')==='active'?'selected':'' ?>>نشط</option>
                                <option value="inactive" <?= ($edit['status']??'')==='inactive'?'selected':'' ?>>غير نشط</option>
                            </select>
                        </div>
                        <?php if (!$edit): ?>
                        <div class="col-12">
                            <label class="form-label">كلمة المرور *</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="/STD/manager/admins.php" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
