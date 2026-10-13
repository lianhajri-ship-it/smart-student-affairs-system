<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$majors = $pdo->query("
    SELECT m.*, c.title AS college_title, d.title AS degree_title
    FROM majors m
    LEFT JOIN colleges c ON c.id = m.college_id
    LEFT JOIN degrees  d ON d.id = m.degree_id
    ORDER BY m.id DESC
")->fetchAll();

$colleges = $pdo->query("SELECT * FROM colleges WHERE status='active' ORDER BY title")->fetchAll();
$degrees  = $pdo->query("SELECT * FROM degrees  WHERE status='active' ORDER BY title")->fetchAll();

$csrfToken = generateCsrfToken();
$pageTitle = 'إدارة التخصصات';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-diagram-3 me-2"></i>إدارة التخصصات</h1>
            <p class="mb-0">ربط التخصصات بالكليات والدرجات العلمية</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#majorModal"
                    onclick="resetForm()">
                <i class="bi bi-plus-circle me-1"></i>إضافة تخصص
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
                    <th>التخصص</th>
                    <th>الكلية</th>
                    <th>الدرجة العلمية</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($majors)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد تخصصات مضافة بعد</td></tr>
                <?php else: ?>
                <?php foreach ($majors as $maj): ?>
                <tr>
                    <td class="text-muted small"><?= $maj['id'] ?></td>
                    <td class="fw-600"><?= htmlspecialchars($maj['title']) ?></td>
                    <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= htmlspecialchars($maj['college_title'] ?? '—') ?></span></td>
                    <td><span class="badge bg-light text-secondary"><?= htmlspecialchars($maj['degree_title'] ?? '—') ?></span></td>
                    <td>
                        <?= $maj['status'] === 'active'
                            ? '<span class="badge bg-success">فعّال</span>'
                            : '<span class="badge bg-secondary">غير فعّال</span>' ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="editItem(<?= htmlspecialchars(json_encode($maj)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="/STD/backend/delete_item.php?type=major&id=<?= $maj['id'] ?>&csrf=<?= $csrfToken ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('حذف هذا التخصص؟')">
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
<div class="modal fade" id="majorModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/STD/backend/save_major.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة تخصص</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" id="itemId" value="0">

                    <div class="mb-3">
                        <label class="form-label">اسم التخصص <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="itemTitle" class="form-control" required
                               placeholder="مثال: هندسة الحاسب، المحاسبة">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الكلية</label>
                        <select name="college_id" id="itemCollege" class="form-select">
                            <option value="">— اختر الكلية —</option>
                            <?php foreach ($colleges as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الدرجة العلمية</label>
                        <select name="degree_id" id="itemDegree" class="form-select">
                            <option value="">— اختر الدرجة —</option>
                            <?php foreach ($degrees as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').textContent  = 'إضافة تخصص';
    document.getElementById('itemId').value            = '0';
    document.getElementById('itemTitle').value         = '';
    document.getElementById('itemCollege').value       = '';
    document.getElementById('itemDegree').value        = '';
    document.getElementById('itemStatus').value        = 'active';
}
function editItem(data) {
    document.getElementById('modalTitle').textContent  = 'تعديل التخصص';
    document.getElementById('itemId').value            = data.id;
    document.getElementById('itemTitle').value         = data.title;
    document.getElementById('itemCollege').value       = data.college_id || '';
    document.getElementById('itemDegree').value        = data.degree_id  || '';
    document.getElementById('itemStatus').value        = data.status;
    new bootstrap.Modal(document.getElementById('majorModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
