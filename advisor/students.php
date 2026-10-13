<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdvisor();

$advisorId = getUserId();

$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.phone, u.student_number,
           c.title AS college_title, d.title AS degree_title,
           u.status, u.created_at
    FROM users u
    JOIN user_types ut ON ut.id = u.user_type_id
    LEFT JOIN colleges c ON c.id = u.college_id
    LEFT JOIN degrees  d ON d.id = u.degree_id
    WHERE ut.title = 'student' AND u.academic_advisor_id = ?
    ORDER BY u.full_name ASC
");
$stmt->execute([$advisorId]);
$students = $stmt->fetchAll();

$pageTitle = 'قائمة طلابي';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-people-fill me-2"></i>قائمة طلابي</h1>
            </div>
        </div>
    </div>

    <div class="std-card">
        <?php if (empty($students)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                لم يتم تسجيل طلاب لديك بعد
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>الرقم الجامعي</th>
                        <th>البريد</th>
                        <th>الجوال</th>
                        <th>الكلية</th>
                        <th>الدرجة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $i => $s): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-600 small"><?= htmlspecialchars($s['full_name']) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($s['student_number'] ?? '—') ?></td>
                        <td class="small"><?= htmlspecialchars($s['email']) ?></td>
                        <td class="small"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
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
