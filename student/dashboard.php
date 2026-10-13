<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

$userId   = getUserId();
$fullName = $_SESSION['full_name'];

// إحصائيات الطالب
$stats = [];
$stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM requests WHERE student_id = ? GROUP BY status");
$stmt->execute([$userId]);
$requestStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['total']   = array_sum($requestStats);
$stats['new']     = $requestStats['new'] ?? 0;
$stats['replied'] = $requestStats['replied'] ?? 0;
$stats['completed']= $requestStats['completed'] ?? 0;

// آخر الطلبات
$stmt = $pdo->prepare("
    SELECT mr.*, s.title as service_title
    FROM requests mr
    LEFT JOIN services s ON s.id = mr.service_id
    WHERE mr.student_id = ?
    ORDER BY mr.created_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$latestRequests = $stmt->fetchAll();

// الخدمات المميزة
$services = $pdo->query("SELECT * FROM services WHERE status = 'published' LIMIT 4")->fetchAll();

$pageTitle = 'لوحة التحكم';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="row align-items-center">
            <div class="col">
                <h2>أهلاً، <?= htmlspecialchars(explode(' ', $fullName)[0]) ?> </h2>
                <p>مرحباً بك في نظام عمادة شؤون الطلاب. كيف يمكننا مساعدتك اليوم؟</p>
            </div>
            <div class="col-auto d-none d-md-block">
                <a href="/STD/student/chatbot.php" class="btn btn-light btn-lg fw-bold">
                    <i class="bi bi-robot me-2"></i>اسأل المساعد الذكي
                </a>
            </div>
        </div>
        <i class="bi bi-mortarboard banner-bg-icon"></i>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#1a5f7a;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-soft text-primary"><i class="bi bi-inbox-fill"></i></div>
                    <div>
                        <div class="stat-number text-primary"><?= $stats['total'] ?></div>
                        <div class="stat-label">إجمالي طلباتي</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#3498db;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#ebf5fb;color:#3498db;"><i class="bi bi-clock-fill"></i></div>
                    <div>
                        <div class="stat-number" style="color:#3498db;"><?= $stats['new'] ?></div>
                        <div class="stat-label">طلبات جديدة</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#17a589;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#e8f8f5;color:#17a589;"><i class="bi bi-chat-fill"></i></div>
                    <div>
                        <div class="stat-number" style="color:#17a589;"><?= $stats['replied'] ?></div>
                        <div class="stat-label">تم الرد عليها</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#27ae60;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#eafaf1;color:#27ae60;"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="stat-number" style="color:#27ae60;"><?= $stats['completed'] ?></div>
                        <div class="stat-label">مكتملة</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Latest Requests -->
        <div class="col-lg-7">
            <div class="std-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-inbox me-2 text-primary"></i>آخر طلباتي</h5>
                    <a href="/STD/student/my_requests.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>

                <?php if (empty($latestRequests)): ?>
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                        <h5>لا توجد طلبات بعد</h5>
                        <p>يمكنك تقديم طلبك الأول الآن</p>
                        <a href="/STD/student/new_request.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>تقديم طلب
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-std table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>الموضوع</th>
                                    <th>الخدمة</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestRequests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars(mb_substr($req['title'], 0, 30)) ?><?= mb_strlen($req['title']) > 30 ? '...' : '' ?></td>
                                    <td><span class="badge bg-primary-soft text-primary small"><?= htmlspecialchars($req['service_title'] ?? '—') ?></span></td>
                                    <td><?= requestStatusLabel($req['status']) ?></td>
                                    <td class="text-muted small"><?= formatDate($req['created_at']) ?></td>
                                    <td><a href="/STD/student/request_details.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-primary px-2 py-1"><i class="bi bi-eye"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions + Services -->
        <div class="col-lg-5">
            <!-- Quick Actions -->
            <div class="std-card p-4 mb-4">
                <h5 class="mb-3 fw-bold"><i class="bi bi-lightning-charge me-2 text-primary"></i>إجراءات سريعة</h5>
                <div class="d-grid gap-2">
                    <a href="/STD/student/new_request.php" class="btn btn-primary text-start">
                        <i class="bi bi-plus-circle me-2"></i>تقديم طلب جديد
                    </a>
                    <a href="/STD/student/chatbot.php" class="btn btn-outline-primary text-start">
                        <i class="bi bi-robot me-2"></i>المساعد الذكي
                    </a>
                    <a href="/STD/student/faq.php" class="btn btn-outline-secondary text-start">
                        <i class="bi bi-question-circle me-2"></i>الأسئلة الشائعة
                    </a>
                    <a href="/STD/student/guides.php" class="btn btn-outline-secondary text-start">
                        <i class="bi bi-book me-2"></i>الأدلة والنماذج
                    </a>
                </div>
            </div>

            <!-- Featured Services -->
            <div class="std-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-grid me-2 text-primary"></i>الخدمات</h5>
                    <a href="/STD/student/services.php" class="btn btn-sm btn-outline-primary">المزيد</a>
                </div>
                <div class="row g-2">
                    <?php foreach ($services as $svc): ?>
                    <div class="col-6">
                        <a href="/STD/student/services.php?id=<?= $svc['id'] ?>" class="text-decoration-none">
                            <div class="p-3 rounded-3 bg-light text-center h-100" style="transition:.2s;" onmouseover="this.style.background='#e8f4f8'" onmouseout="this.style.background=''">
                                <i class="bi <?= htmlspecialchars($svc['icon']) ?> text-primary fs-4 d-block mb-1"></i>
                                <div class="small fw-600" style="font-size:.78rem;color:#2c3e50;"><?= htmlspecialchars(mb_substr($svc['title'], 0, 22)) ?></div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
