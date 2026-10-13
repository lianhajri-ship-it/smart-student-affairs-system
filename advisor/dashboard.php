<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdvisor();

$advisorId = getUserId();

// Count students assigned to this advisor
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE academic_advisor_id = ? AND status='active'");
$stmt->execute([$advisorId]);
$totalStudents = (int)$stmt->fetchColumn();

// Count open requests from advisor's students
$stmt2 = $pdo->prepare("
    SELECT COUNT(*) FROM requests mr
    JOIN users u ON u.id = mr.student_id
    WHERE u.academic_advisor_id = ?
      AND mr.status IN ('new','under_review')
");
$stmt2->execute([$advisorId]);
$openRequests = (int)$stmt2->fetchColumn();

// Recent students
$stmt3 = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.student_number,
           c.title AS college_title, d.title AS degree_title,
           u.status, u.created_at
    FROM users u
    JOIN user_types ut ON ut.id = u.user_type_id
    LEFT JOIN colleges c ON c.id = u.college_id
    LEFT JOIN degrees  d ON d.id = u.degree_id
    WHERE ut.title = 'student' AND u.academic_advisor_id = ?
    ORDER BY u.created_at DESC
    LIMIT 10
");
$stmt3->execute([$advisorId]);
$recentStudents = $stmt3->fetchAll();

$pageTitle = 'لوحة تحكم المرشد الأكاديمي';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="mb-0">
                    <i class="bi bi-person-check-fill me-2"></i>
                    مرحباً، <?= htmlspecialchars(explode(' ', $fullName)[0]) ?>
                </h1>
                <p class="mb-0 opacity-75 small mt-1">لوحة تحكم المرشد الأكاديمي</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="std-card p-4 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-800 lh-1"><?= $totalStudents ?></div>
                        <div class="text-muted small mt-1">إجمالي الطلاب</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="std-card p-4 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                        <i class="bi bi-inbox-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-800 lh-1"><?= $openRequests ?></div>
                        <div class="text-muted small mt-1">طلبات مفتوحة</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Students Table -->
    <div class="std-card">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-700"><i class="bi bi-people me-2 text-primary"></i>طلابي المسجلون</h6>
            <a href="/STD/advisor/students.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
        </div>
        <?php if (empty($recentStudents)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                لم يتم تسجيل طلاب لديك بعد
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الرقم الجامعي</th>
                        <th>الكلية</th>
                        <th>الدرجة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentStudents as $s): ?>
                    <tr>
                        <td>
                            <div class="fw-600 small"><?= htmlspecialchars($s['full_name']) ?></div>
                            <div class="text-muted x-small"><?= htmlspecialchars($s['email']) ?></div>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($s['student_number'] ?? '—') ?></td>
                        <td class="small"><?= htmlspecialchars($s['college_title'] ?? '—') ?></td>
                        <td class="small"><?= htmlspecialchars($s['degree_title'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-<?= $s['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= $s['status'] === 'active' ? 'نشط' : 'غير نشط' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
