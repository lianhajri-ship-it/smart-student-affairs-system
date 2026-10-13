<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$types = $pdo->query("SELECT * FROM user_types ORDER BY id ASC")->fetchAll();

$csrfToken = generateCsrfToken();
$pageTitle = 'أدوار المستخدمين';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-tags me-2"></i>أدوار المستخدمين</h1>
            <p class="mb-0">عرض وإدارة أدوار المستخدمين في النظام</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#typeModal"
                    onclick="resetForm()">
                <i class="bi bi-plus-circle me-1"></i>إضافة دور
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
                    <th>الدور (عربي)</th>
                    <th>الدور (إنجليزي)</th>
                    <th>عدد المستخدمين</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($types)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">لا توجد أدوار</td></tr>
                <?php else: ?>
                <?php foreach ($types as $t):
                    $cnt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type_id=?");
                    $cnt->execute([$t['id']]);
                    $userCount = (int)$cnt->fetchColumn();
                ?>
                <tr>
                    <td class="text-muted small"><?= $t['id'] ?></td>
                    <td class="fw-600"><?= htmlspecialchars($t['arabic_title']) ?></td>
                    <td class="text-muted small"><code><?= htmlspecialchars($t['title']) ?></code></td>
                    <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= $userCount ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="editItem(<?= htmlspecialchars(json_encode($t)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="/STD/backend/delete_item.php?type=user_type&id=<?= $t['id'] ?>&csrf=<?= $csrfToken ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('حذف هذا الدور نهائياً؟')">
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
<div class="modal fade" id="typeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/STD/backend/save_user_type.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة دور</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" id="itemId" value="0">
                    <div class="mb-3">
                        <label class="form-label">اسم الدور بالعربي <span class="text-danger">*</span></label>
                        <input type="text" name="arabic_title" id="itemArabicTitle" class="form-control"
                               placeholder="مثال: طالب، مدير، مرشد أكاديمي" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم الدور بالإنجليزي <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="itemTitle" class="form-control"
                               placeholder="مثال: student, admin, manager" required>
                        <div class="form-text">يُستخدم داخلياً — لا يظهر للطلاب</div>
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
    document.getElementById('modalTitle').textContent   = 'إضافة دور';
    document.getElementById('itemId').value             = '0';
    document.getElementById('itemTitle').value          = '';
    document.getElementById('itemArabicTitle').value    = '';
}
function editItem(data) {
    document.getElementById('modalTitle').textContent   = 'تعديل الدور';
    document.getElementById('itemId').value             = data.id;
    document.getElementById('itemTitle').value          = data.title;
    document.getElementById('itemArabicTitle').value    = data.arabic_title;
    new bootstrap.Modal(document.getElementById('typeModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
