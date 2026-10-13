<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$colleges = $pdo->query("SELECT * FROM colleges ORDER BY id DESC")->fetchAll();

$csrfToken = generateCsrfToken();
$pageTitle = 'إدارة الكليات';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-building me-2"></i>إدارة الكليات</h1>
            <p class="mb-0">إضافة وتعديل الكليات الجامعية</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#collegeModal"
                    onclick="resetForm()">
                <i class="bi bi-plus-circle me-1"></i>إضافة كلية
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
                    <th>اسم الكلية</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($colleges)): ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">لا توجد كليات مضافة بعد</td></tr>
                <?php else: ?>
                <?php foreach ($colleges as $col): ?>
                <tr>
                    <td class="text-muted small"><?= $col['id'] ?></td>
                    <td class="fw-600"><?= htmlspecialchars($col['title']) ?></td>
                    <td>
                        <?= $col['status'] === 'active'
                            ? '<span class="badge bg-success">فعّالة</span>'
                            : '<span class="badge bg-secondary">غير فعّالة</span>' ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="editItem(<?= htmlspecialchars(json_encode($col)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="/STD/backend/delete_item.php?type=college&id=<?= $col['id'] ?>&csrf=<?= $csrfToken ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('حذف هذه الكلية؟')">
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
<div class="modal fade" id="collegeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/STD/backend/save_college.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة كلية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" id="itemId" value="0">

                    <div class="mb-3">
                        <label class="form-label">اسم الكلية <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="itemTitle" class="form-control" required
                               placeholder="مثال: كلية الهندسة">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" id="itemStatus" class="form-select">
                            <option value="active">فعّالة</option>
                            <option value="inactive">غير فعّالة</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').textContent  = 'إضافة كلية';
    document.getElementById('itemId').value            = '0';
    document.getElementById('itemTitle').value         = '';
    document.getElementById('itemStatus').value        = 'active';
}
function editItem(data) {
    document.getElementById('modalTitle').textContent  = 'تعديل الكلية';
    document.getElementById('itemId').value            = data.id;
    document.getElementById('itemTitle').value         = data.title;
    document.getElementById('itemStatus').value        = data.status;
    new bootstrap.Modal(document.getElementById('collegeModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
