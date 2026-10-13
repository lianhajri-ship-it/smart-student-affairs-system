<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

$csrfToken = generateCsrfToken();

// service_id is required — redirect if missing
$serviceId = (int)($_GET['service_id'] ?? 0);
if ($serviceId <= 0) {
    header('Location: /STD/student/services.php'); exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, d.title as dept_title
    FROM services s
    LEFT JOIN departments d ON d.id = s.department_id
    WHERE s.id = ? AND s.status = 'published'
");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();
if (!$service) { header('Location: /STD/student/services.php'); exit; }

$pageTitle = 'تقديم طلب: ' . $service['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-plus-circle-fill me-2"></i><?= htmlspecialchars($service['title']) ?></h1>
                <p>تقديم طلب للخدمة المحددة</p>
            </div>
            <div class="col-auto">
                <a href="/STD/student/services.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-right me-1"></i>العودة للخدمات
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Form Column -->
        <div class="col-lg-8">

            <!-- Service Info Panel -->
            <div class="std-card p-4 mb-4 border-start border-primary border-3">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary fs-4">
                        <i class="bi <?= htmlspecialchars($service['icon']) ?>"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($service['title']) ?></h5>
                        <?php if ($service['dept_title']): ?>
                            <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($service['dept_title']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($service['description']): ?>
                <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                <?php endif; ?>

                <div class="row g-3">
                    <?php if ($service['conditions']): ?>
                    <div class="col-md-4">
                        <div class="p-3 bg-warning bg-opacity-10 rounded-3 h-100">
                            <h6 class="fw-bold text-warning-emphasis small mb-2">
                                <i class="bi bi-shield-check me-1"></i>شروط الاستفادة
                            </h6>
                            <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($service['conditions'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($service['required_documents']): ?>
                    <div class="col-md-4">
                        <div class="p-3 bg-info bg-opacity-10 rounded-3 h-100">
                            <h6 class="fw-bold text-info-emphasis small mb-2">
                                <i class="bi bi-paperclip me-1"></i>المستندات المطلوبة
                            </h6>
                            <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($service['required_documents'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($service['steps']): ?>
                    <div class="col-md-4">
                        <div class="p-3 bg-success bg-opacity-10 rounded-3 h-100">
                            <h6 class="fw-bold text-success-emphasis small mb-2">
                                <i class="bi bi-list-ol me-1"></i>خطوات التنفيذ
                            </h6>
                            <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($service['steps'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Request Form -->
            <div class="std-card p-4">
                <form method="POST" action="/STD/backend/save_request.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">

                    <div class="row g-3">
                        <!-- Priority -->
                        <div class="col-md-12">
                            <label class="form-label">الأولوية</label>
                            <select name="priority" class="form-select">
                                <option value="low">منخفضة</option>
                                <option value="medium" selected>متوسطة</option>
                                <option value="high">عالية</option>
                            </select>
                        </div>

                        <!-- Title — pre-filled from service -->
                        <div class="col-12">
                            <label class="form-label">عنوان الطلب <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required
                                   placeholder="أدخل عنواناً واضحاً للطلب"
                                   value="<?= htmlspecialchars('طلب: ' . $service['title']) ?>">
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label">تفاصيل الطلب <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control auto-resize" rows="5" required
                                      placeholder="اشرح طلبك بالتفصيل، وأذكر أي معلومات إضافية..."></textarea>
                        </div>

                        <!-- Attachment -->
                        <div class="col-12">
                            <label class="form-label">
                                مرفق <?= $service['required_documents'] ? '<span class="text-danger">*</span>' : '(اختياري)' ?>
                            </label>
                            <input type="file" name="attachment" class="form-control"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="form-text">الملفات المقبولة: PDF, Word, الصور. الحجم الأقصى: 5 ميغابايت</div>
                        </div>

                        <!-- Buttons -->
                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <a href="/STD/student/services.php"
                               class="btn btn-outline-secondary px-4">إلغاء</a>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-send-fill me-2"></i>إرسال الطلب
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-lg-4">
            <div class="std-card p-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-1"></i>حالات الطلب</h6>
                <div class="d-flex flex-column gap-2">
                    <?php
                    $statuses = [
                        ['new',          'primary', 'جديد',          'تم استلام طلبك'],
                        ['under_review', 'warning', 'تحت المراجعة', 'يراجع الفريق طلبك'],
                        ['replied',      'info',    'تم الرد',       'تم الرد على طلبك'],
                        ['completed',    'success', 'مكتمل',         'تمت معالجة طلبك'],
                    ];
                    foreach ($statuses as [$key, $color, $label, $desc]): ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-<?= $color ?>" style="min-width:90px;"><?= $label ?></span>
                        <span class="small text-muted"><?= $desc ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="std-card p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-clock text-primary me-1"></i>وقت الاستجابة المتوقع</h6>
                <p class="text-muted small mb-0">يُرد على معظم الطلبات خلال <strong>1-3 أيام عمل</strong> من تاريخ التقديم.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
