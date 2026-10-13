<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$services    = $pdo->query("SELECT id,title FROM services WHERE status='published'")->fetchAll();
$departments = $pdo->query("SELECT id,title FROM departments WHERE status='active'")->fetchAll();
$guides      = $pdo->query("
    SELECT g.*, s.title as svc, d.title as dept
    FROM guides g
    LEFT JOIN services s ON s.id=g.service_id
    LEFT JOIN departments d ON d.id=g.department_id
    ORDER BY g.id DESC")->fetchAll();

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM guides WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$csrfToken = generateCsrfToken();
$pageTitle = 'إدارة الأدلة';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col"><h1><i class="bi bi-book-fill me-2"></i>إدارة الأدلة</h1></div>
            <div class="col-auto">
                <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#guideModal">
                    <i class="bi bi-plus-circle me-1"></i>إضافة دليل
                </button>
            </div>
        </div>
    </div>

    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead><tr><th>#</th><th>العنوان</th><th>الخدمة</th><th>القسم</th><th>ملف</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($guides as $g): ?>
                    <tr>
                        <td class="text-muted small"><?= $g['id'] ?></td>
                        <td class="fw-600"><?= htmlspecialchars($g['title']) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($g['svc'] ?? '—') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($g['dept'] ?? '—') ?></td>
                        <td>
                            <?php if ($g['file_path']): ?>
                                <a href="/STD/assets/uploads/<?= htmlspecialchars($g['file_path']) ?>" target="_blank" class="btn btn-xs btn-outline-primary btn-sm px-2"><i class="bi bi-download"></i></a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $g['status']==='published'?'success':($g['status']==='draft'?'warning text-dark':'secondary') ?>"><?= $g['status']==='published'?'منشور':($g['status']==='draft'?'مسودة':'مؤرشف') ?></span></td>
                        <td class="d-flex gap-1">
                            <a href="?edit=<?= $g['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="/STD/backend/delete_item.php?type=guide&id=<?= $g['id'] ?>&token=<?= urlencode($csrfToken) ?>"
                               class="btn btn-sm btn-outline-danger" data-confirm="حذف الدليل؟"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade <?= $edit?'show d-block':'' ?>" id="guideModal" tabindex="-1" <?= $edit?'style="background:rgba(0,0,0,.5);"':'' ?>>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit?'تعديل دليل':'إضافة دليل' ?></h5>
                <a href="/STD/admin/guides.php" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST" action="/STD/backend/save_guide.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                <input type="hidden" name="existing_file" value="<?= htmlspecialchars($edit['file_path'] ?? '') ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">عنوان الدليل *</label>
                            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($edit['title'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="published" <?= ($edit['status']??'')==='published'?'selected':'' ?>>منشور</option>
                                <option value="draft"     <?= ($edit['status']??'')==='draft'    ?'selected':'' ?>>مسودة</option>
                                <option value="archived"  <?= ($edit['status']??'')==='archived' ?'selected':'' ?>>مؤرشف</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الخدمة المرتبطة</label>
                            <select name="service_id" class="form-select">
                                <option value="">— اختياري —</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($edit['service_id']??'')==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">القسم</label>
                            <select name="department_id" class="form-select">
                                <option value="">— اختياري —</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($edit['department_id']??'')==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">رفع الملف (PDF, Word, صورة)</label>
                            <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <?php if (!empty($edit['file_path'])): ?>
                                <div class="form-text">الملف الحالي: <a href="/STD/assets/uploads/<?= htmlspecialchars($edit['file_path']) ?>" target="_blank">عرض</a></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="/STD/admin/guides.php" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
