<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$colleges = $pdo->query("SELECT * FROM colleges WHERE status='active' ORDER BY title")->fetchAll();

// Fetch advisor type id
$advisorTypeId = (int)$pdo->query("SELECT id FROM user_types WHERE title='academic_advisor'")->fetchColumn();

$advisors = $pdo->query("
    SELECT u.id, u.full_name, u.email, u.phone, u.national_id,
           u.college_id, c.title AS college_title,
           u.status, u.created_at
    FROM users u
    LEFT JOIN colleges c ON c.id = u.college_id
    WHERE u.user_type_id = $advisorTypeId
    ORDER BY u.full_name ASC
")->fetchAll();

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=? AND user_type_id=?");
    $stmt->execute([(int)$_GET['edit'], $advisorTypeId]);
    $edit = $stmt->fetch();
}

$csrfToken = generateCsrfToken();
$pageTitle = 'إدارة المرشدين الأكاديميين';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col"><h1><i class="bi bi-person-check-fill me-2"></i>إدارة المرشدين الأكاديميين</h1></div>
            <div class="col-auto">
                <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#advisorModal">
                    <i class="bi bi-plus-circle me-1"></i>إضافة مرشد
                </button>
            </div>
        </div>
    </div>

    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الجوال</th>
                        <th>الكلية</th>
                        <th>عدد الطلاب</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($advisors as $a):
                        $sc = $pdo->prepare("SELECT COUNT(*) FROM users WHERE academic_advisor_id=?");
                        $sc->execute([$a['id']]);
                        $studentCount = (int)$sc->fetchColumn();
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $a['id'] ?></td>
                        <td>
                            <div class="fw-600 small"><?= htmlspecialchars($a['full_name']) ?></div>
                        </td>
                        <td class="small"><?= htmlspecialchars($a['email']) ?></td>
                        <td class="small"><?= htmlspecialchars($a['phone'] ?? '—') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($a['college_title'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary"><?= $studentCount ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $a['status']==='active'?'success':($a['status']==='suspended'?'danger':'secondary') ?>">
                                <?= $a['status']==='active'?'نشط':($a['status']==='suspended'?'موقوف':'غير نشط') ?>
                            </span>
                        </td>
                        <td class="d-flex gap-1">
                            <a href="?edit=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($a['status']==='active'): ?>
                                <a href="/STD/backend/toggle_user_status.php?id=<?= $a['id'] ?>&action=suspend&token=<?= urlencode($csrfToken) ?>"
                                   class="btn btn-sm btn-outline-warning" data-confirm="إيقاف هذا الحساب؟"><i class="bi bi-pause-circle"></i></a>
                            <?php else: ?>
                                <a href="/STD/backend/toggle_user_status.php?id=<?= $a['id'] ?>&action=activate&token=<?= urlencode($csrfToken) ?>"
                                   class="btn btn-sm btn-outline-success" data-confirm="تفعيل هذا الحساب؟"><i class="bi bi-play-circle"></i></a>
                            <?php endif; ?>
                            <a href="/STD/backend/delete_advisor.php?id=<?= $a['id'] ?>&token=<?= urlencode($csrfToken) ?>"
                               class="btn btn-sm btn-outline-danger"
                               data-confirm="حذف هذا المرشد؟ سيتم إلغاء ربطه بجميع الطلاب.">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($advisors)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">لا يوجد مرشدون أكاديميون بعد</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade <?= $edit ? 'show d-block' : '' ?>" id="advisorModal" tabindex="-1"
     <?= $edit ? 'style="background:rgba(0,0,0,.5);"' : '' ?>>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit ? 'تعديل مرشد أكاديمي' : 'إضافة مرشد أكاديمي' ?></h5>
                <a href="/STD/manager/advisors.php" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST" action="/STD/backend/save_advisor.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?= htmlspecialchars($edit['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الهوية </label>
                            <input type="text" name="national_id" class="form-control"
                                   value="<?= htmlspecialchars($edit['national_id'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الجوال</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($edit['phone'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= htmlspecialchars($edit['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">الكلية</label>
                            <select name="college_id" class="form-select">
                                <option value="">— اختر —</option>
                                <?php foreach ($colleges as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        <?= ($edit['college_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">
                                كلمة المرور <?= $edit ? '' : '<span class="text-danger">*</span>' ?>
                            </label>
                            <input type="password" name="password" class="form-control"
                                   <?= $edit ? '' : 'required' ?> minlength="8"
                                   placeholder="<?= $edit ? 'اتركها فارغة للإبقاء على القديمة' : 'أدخل كلمة المرور' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="active"    <?= ($edit['status'] ?? 'active') === 'active'    ? 'selected' : '' ?>>نشط</option>
                                <option value="inactive"  <?= ($edit['status'] ?? '') === 'inactive'  ? 'selected' : '' ?>>غير نشط</option>
                                <option value="suspended" <?= ($edit['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>موقوف</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="/STD/manager/advisors.php" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
