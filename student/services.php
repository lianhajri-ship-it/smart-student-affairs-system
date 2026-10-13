<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

// جلب الأقسام للفلتر
$departments = $pdo->query("SELECT * FROM departments WHERE status = 'active' ORDER BY title")->fetchAll();

// الفلتر
$deptFilter = (int)($_GET['dept'] ?? 0);
$search     = clean($_GET['q'] ?? '');

$params = [];
$where  = ["s.status = 'published'"];

if ($deptFilter > 0) {
    $where[]  = "s.department_id = ?";
    $params[] = $deptFilter;
}
if (!empty($search)) {
    $where[]  = "(s.title LIKE ? OR s.description LIKE ? OR s.keywords LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "SELECT s.*, d.title as dept_title
        FROM services s
        LEFT JOIN departments d ON d.id = s.department_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

// تفاصيل خدمة محددة
$selectedService = null;
if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT s.*, d.title as dept_title
        FROM services s
        LEFT JOIN departments d ON d.id = s.department_id
        WHERE s.id = ? AND s.status = 'published'
    ");
    $stmt->execute([(int)$_GET['id']]);
    $selectedService = $stmt->fetch();
}

$pageTitle = 'الخدمات';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-grid-fill me-2"></i>الخدمات الطلابية</h1>
                <p>تصفح جميع الخدمات المتاحة من عمادة شؤون الطلاب</p>
            </div>
            <div class="col-auto">
                <a href="/STD/student/new_request.php" class="btn btn-light fw-bold">
                    <i class="bi bi-plus-circle me-1"></i>تقديم طلب
                </a>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="std-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="search-bar">
                    <input type="text" name="q" class="form-control" placeholder="ابحث عن خدمة..." value="<?= htmlspecialchars($search) ?>">
                    <i class="bi bi-search search-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <select name="dept" class="form-select">
                    <option value="0">جميع الأقسام</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $deptFilter == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>بحث
                </button>
            </div>
        </form>
    </div>

    <!-- Service Detail Modal -->
    <?php if ($selectedService): ?>
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi <?= htmlspecialchars($selectedService['icon']) ?> me-2"></i>
                        <?= htmlspecialchars($selectedService['title']) ?>
                    </h5>
                    <a href="/STD/student/services.php<?= $deptFilter || $search ? '?dept='.$deptFilter.'&q='.urlencode($search) : '' ?>" class="btn-close btn-close-white"></a>
                </div>
                <div class="modal-body p-4">
                    <?php if ($selectedService['dept_title']): ?>
                        <p class="text-muted small mb-3"><i class="bi bi-building me-1"></i><?= htmlspecialchars($selectedService['dept_title']) ?></p>
                    <?php endif; ?>

                    <p><?= nl2br(htmlspecialchars($selectedService['description'])) ?></p>

                    <?php if ($selectedService['conditions']): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold text-primary"><i class="bi bi-shield-check me-2"></i>شروط الاستفادة</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($selectedService['conditions'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($selectedService['required_documents']): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold text-primary"><i class="bi bi-paperclip me-2"></i>المستندات المطلوبة</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($selectedService['required_documents'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($selectedService['steps']): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold text-primary"><i class="bi bi-list-ol me-2"></i>خطوات التنفيذ</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($selectedService['steps'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <a href="/STD/student/new_request.php?service_id=<?= $selectedService['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>تقديم طلب لهذه الخدمة
                    </a>
                    <a href="/STD/student/services.php" class="btn btn-outline-secondary">إغلاق</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Services Grid -->
    <?php if (empty($services)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-grid"></i></div>
            <h5>لا توجد خدمات مطابقة</h5>
            <p>جرب البحث بكلمات مختلفة</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($services as $svc): ?>
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="service-card d-flex flex-column h-100">
                    <a href="/STD/student/services.php?id=<?= $svc['id'] ?>" class="text-decoration-none flex-grow-1">
                        <div class="service-icon-wrap">
                            <i class="bi <?= htmlspecialchars($svc['icon']) ?>"></i>
                        </div>
                        <div class="service-title"><?= htmlspecialchars($svc['title']) ?></div>
                        <div class="service-desc"><?= htmlspecialchars(mb_substr($svc['description'], 0, 80)) ?>...</div>
                        <?php if ($svc['dept_title']): ?>
                            <div class="mt-2">
                                <span class="badge bg-primary-soft text-primary small"><?= htmlspecialchars($svc['dept_title']) ?></span>
                            </div>
                        <?php endif; ?>
                    </a>
                    <div class="mt-3 d-flex gap-2">
                        <a href="/STD/student/services.php?id=<?= $svc['id'] ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                            <i class="bi bi-info-circle me-1"></i>التفاصيل
                        </a>
                        <a href="/STD/student/new_request.php?service_id=<?= $svc['id'] ?>" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="bi bi-send me-1"></i>تقديم
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
