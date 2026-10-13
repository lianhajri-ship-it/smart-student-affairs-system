<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

$tab    = $_GET['tab'] ?? 'guides';
$search = clean($_GET['q'] ?? '');

// جلب الأدلة
$guideParams = [];
$guideWhere  = ["g.status = 'published'"];
if (!empty($search)) {
    $guideWhere[]  = "(g.title LIKE ? OR g.description LIKE ?)";
    $guideParams[] = "%$search%";
    $guideParams[] = "%$search%";
}
$guideStmt = $pdo->prepare("
    SELECT g.*, s.title as service_title, d.title as dept_title
    FROM guides g
    LEFT JOIN services s ON s.id = g.service_id
    LEFT JOIN departments d ON d.id = g.department_id
    WHERE " . implode(' AND ', $guideWhere) . "
    ORDER BY g.id DESC
");
$guideStmt->execute($guideParams);
$guides = $guideStmt->fetchAll();

// جلب النماذج
$formParams = [];
$formWhere  = ["f.status = 'published'"];
if (!empty($search)) {
    $formWhere[]  = "(f.title LIKE ? OR f.description LIKE ?)";
    $formParams[] = "%$search%";
    $formParams[] = "%$search%";
}
$formStmt = $pdo->prepare("
    SELECT f.*, s.title as service_title, d.title as dept_title
    FROM forms f
    LEFT JOIN services s ON s.id = f.service_id
    LEFT JOIN departments d ON d.id = f.department_id
    WHERE " . implode(' AND ', $formWhere) . "
    ORDER BY f.id DESC
");
$formStmt->execute($formParams);
$forms = $formStmt->fetchAll();

$pageTitle = 'الأدلة والنماذج';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <h1><i class="bi bi-book-fill me-2"></i>الأدلة والنماذج</h1>
        <p>تصفح الأدلة الإرشادية والنماذج المطلوبة للخدمات</p>
    </div>

    <!-- Search -->
    <div class="std-card p-3 mb-4">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <div class="search-bar flex-grow-1">
                <input type="text" name="q" class="form-control" placeholder="ابحث..." value="<?= htmlspecialchars($search) ?>">
                <i class="bi bi-search search-icon"></i>
            </div>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-search me-1"></i>بحث</button>
        </form>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'guides' ? 'active' : '' ?>" href="?tab=guides&q=<?= urlencode($search) ?>">
                <i class="bi bi-book me-1"></i>الأدلة (<?= count($guides) ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'forms' ? 'active' : '' ?>" href="?tab=forms&q=<?= urlencode($search) ?>">
                <i class="bi bi-file-earmark me-1"></i>النماذج (<?= count($forms) ?>)
            </a>
        </li>
    </ul>

    <!-- Guides Tab -->
    <?php if ($tab === 'guides'): ?>
        <?php if (empty($guides)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="bi bi-book"></i></div><h5>لا توجد أدلة</h5></div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($guides as $guide): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="std-card p-4 h-100">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon bg-primary-soft text-primary flex-shrink-0" style="width:50px;height:50px;border-radius:12px;">
                                <i class="bi bi-file-earmark-pdf fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($guide['title']) ?></h6>
                                <?php if ($guide['description']): ?>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars(mb_substr($guide['description'], 0, 100)) ?></p>
                                <?php endif; ?>
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php if ($guide['service_title']): ?>
                                        <span class="badge bg-primary-soft text-primary"><?= htmlspecialchars($guide['service_title']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($guide['dept_title']): ?>
                                        <span class="badge bg-light text-secondary"><?= htmlspecialchars($guide['dept_title']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($guide['file_path']): ?>
                            <div class="mt-3 pt-3 border-top">
                                <a href="/STD/assets/uploads/<?= htmlspecialchars($guide['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="bi bi-download me-1"></i>تحميل الدليل
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-3 pt-3 border-top">
                                <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>الملف غير متوفر حالياً</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- Forms Tab -->
    <?php else: ?>
        <?php if (empty($forms)): ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="bi bi-file-earmark"></i></div><h5>لا توجد نماذج</h5></div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($forms as $form): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="std-card p-4 h-100">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon flex-shrink-0" style="width:50px;height:50px;border-radius:12px;background:#fef9e7;color:#f39c12;">
                                <i class="bi bi-file-earmark-text fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($form['title']) ?></h6>
                                <?php if ($form['description']): ?>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars(mb_substr($form['description'], 0, 100)) ?></p>
                                <?php endif; ?>
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php if ($form['service_title']): ?>
                                        <span class="badge bg-primary-soft text-primary"><?= htmlspecialchars($form['service_title']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($form['dept_title']): ?>
                                        <span class="badge bg-light text-secondary"><?= htmlspecialchars($form['dept_title']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($form['file_path']): ?>
                            <div class="mt-3 pt-3 border-top">
                                <a href="/STD/assets/uploads/<?= htmlspecialchars($form['file_path']) ?>" target="_blank" class="btn btn-sm btn-warning text-dark">
                                    <i class="bi bi-download me-1"></i>تحميل النموذج
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-3 pt-3 border-top">
                                <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>الملف غير متوفر حالياً</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
