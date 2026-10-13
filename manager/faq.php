<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$services    = $pdo->query("SELECT id,title FROM services WHERE status='published'")->fetchAll();
$departments = $pdo->query("SELECT id,title FROM departments WHERE status='active'")->fetchAll();
$faqs        = $pdo->query("
    SELECT f.*, s.title as svc, d.title as dept
    FROM faq f
    LEFT JOIN services s ON s.id=f.service_id
    LEFT JOIN departments d ON d.id=f.department_id
    ORDER BY f.sort_order ASC, f.id DESC")->fetchAll();

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM faq WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$csrfToken = generateCsrfToken();
$pageTitle = 'إدارة الأسئلة الشائعة';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col"><h1><i class="bi bi-question-circle-fill me-2"></i>إدارة الأسئلة الشائعة</h1></div>
            <div class="col-auto">
                <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#faqModal">
                    <i class="bi bi-plus-circle me-1"></i>إضافة سؤال
                </button>
            </div>
        </div>
    </div>

    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead><tr><th>#</th><th>السؤال</th><th>القسم</th><th>الترتيب</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($faqs as $f): ?>
                    <tr>
                        <td class="text-muted small"><?= $f['id'] ?></td>
                        <td>
                            <div class="fw-600 small"><?= htmlspecialchars(mb_substr($f['question'],0,60)) ?></div>
                            <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars(mb_substr($f['answer'],0,60)) ?>...</div>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($f['dept'] ?? '—') ?></td>
                        <td class="text-center"><?= $f['sort_order'] ?></td>
                        <td><span class="badge bg-<?= $f['status']==='published'?'success':'secondary' ?>"><?= $f['status']==='published'?'منشور':'غير منشور' ?></span></td>
                        <td class="d-flex gap-1">
                            <a href="?edit=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="/STD/backend/delete_item.php?type=faq&id=<?= $f['id'] ?>&token=<?= urlencode($csrfToken) ?>"
                               class="btn btn-sm btn-outline-danger" data-confirm="حذف السؤال؟"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade <?= $edit?'show d-block':'' ?>" id="faqModal" tabindex="-1" <?= $edit?'style="background:rgba(0,0,0,.5);"':'' ?>>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit?'تعديل سؤال':'إضافة سؤال شائع' ?></h5>
                <a href="/STD/manager/faq.php" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST" action="/STD/backend/save_faq.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">السؤال *</label>
                            <textarea name="question" class="form-control" rows="2" required><?= htmlspecialchars($edit['question'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">الجواب *</label>
                            <textarea name="answer" class="form-control" rows="4" required><?= htmlspecialchars($edit['answer'] ?? '') ?></textarea>
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
                        <div class="col-md-6">
                            <label class="form-label">كلمات مفتاحية</label>
                            <input type="text" name="keywords" class="form-control" value="<?= htmlspecialchars($edit['keywords'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الترتيب</label>
                            <input type="number" name="sort_order" class="form-control" value="<?= $edit['sort_order'] ?? 0 ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="published" <?= ($edit['status']??'')==='published'?'selected':'' ?>>منشور</option>
                                <option value="draft"     <?= ($edit['status']??'')==='draft'    ?'selected':'' ?>>مسودة</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="/STD/manager/faq.php" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
