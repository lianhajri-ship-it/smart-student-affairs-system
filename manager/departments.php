<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$departments = $pdo->query("SELECT * FROM departments ORDER BY id DESC")->fetchAll();

$csrfToken = generateCsrfToken();
$pageTitle = 'إدارة الأقسام';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-building-fill me-2"></i>إدارة الأقسام</h1>
            <p class="mb-0">إدارة أقسام الخدمات المقدمة</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#deptModal"
                    onclick="resetForm()">
                <i class="bi bi-plus-circle me-1"></i>إضافة قسم
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
                    <th>اسم القسم</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">لا توجد أقسام مضافة بعد</td></tr>
                <?php else: ?>
                <?php foreach ($departments as $dept): ?>
                <tr>
                    <td class="text-muted small"><?= $dept['id'] ?></td>
                    <td class="fw-600"><?= htmlspecialchars($dept['title']) ?></td>
                    <td>
                        <?= $dept['status'] === 'active'
                            ? '<span class="badge bg-success">فعّال</span>'
                            : '<span class="badge bg-secondary">غير فعّال</span>' ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="editItem(<?= htmlspecialchars(json_encode($dept)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="/STD/backend/delete_item.php?type=department&id=<?= $dept['id'] ?>&csrf=<?= $csrfToken ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('حذف هذا القسم؟')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- Modal -->
<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/STD/backend/save_department.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة قسم</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" id="itemId" value="0">

                    <div class="mb-3">
                        <label class="form-label">اسم القسم <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="itemTitle" class="form-control" required
                               placeholder="مثال: شؤون الطلاب، التسجيل والقبول">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" id="itemStatus" class="form-select">
                            <option value="active">فعّال</option>
                            <option value="inactive">غير فعّال</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>حفظ</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').textContent = 'إضافة قسم';
    document.getElementById('itemId').value           = '0';
    document.getElementById('itemTitle').value        = '';
    document.getElementById('itemStatus').value       = 'active';
}
function editItem(data) {
    document.getElementById('modalTitle').textContent = 'تعديل القسم';
    document.getElementById('itemId').value           = data.id;
    document.getElementById('itemTitle').value        = data.title;
    document.getElementById('itemStatus').value       = data.status;
    new bootstrap.Modal(document.getElementById('deptModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
