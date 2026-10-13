<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

$userId      = getUserId();
$statusFilter= clean($_GET['status'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));

$params = [$userId];
$where  = ['mr.student_id = ?'];

if (!empty($statusFilter)) {
    $where[]  = 'mr.status = ?';
    $params[] = $statusFilter;
}

$query = "SELECT mr.*, s.title as service_title
          FROM requests mr
          LEFT JOIN services s ON s.id = mr.service_id
          WHERE " . implode(' AND ', $where) . "
          ORDER BY mr.created_at DESC";

$result = paginate($pdo, $query, $params, $page, 10);
$requests = $result['data'];

$pageTitle = 'طلباتي';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-inbox-fill me-2"></i>طلباتي</h1>
                <p>متابعة جميع طلباتك المقدمة</p>
            </div>
            <div class="col-auto">
                <a href="/STD/student/new_request.php" class="btn btn-light fw-bold">
                    <i class="bi bi-plus-circle me-1"></i>طلب جديد
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="std-card p-3 mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <?php
            $tabs = [
                '' => 'الكل',
                'new' => 'جديد',
                'under_review' => 'تحت المراجعة',
                'replied' => 'تم الرد',
                'completed' => 'مكتمل',
                'rejected' => 'مرفوض',
                'closed' => 'مغلق',
            ];
            foreach ($tabs as $val => $label): ?>
            <a href="?status=<?= $val ?>"
               class="btn btn-sm <?= $statusFilter === $val ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Requests Table -->
    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
            <h5>لا توجد طلبات <?= $statusFilter ? 'بهذه الحالة' : '' ?></h5>
            <p>يمكنك تقديم طلبك الأول الآن</p>
            <a href="/STD/student/new_request.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>تقديم طلب
            </a>
        </div>
    <?php else: ?>
        <div class="std-card">
            <div class="table-responsive">
                <table class="table table-std table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العنوان</th>
                            <th>الخدمة</th>
                            <th>الأولوية</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td class="text-muted small">#<?= $req['id'] ?></td>
                            <td>
                                <a href="/STD/student/request_details.php?id=<?= $req['id'] ?>" class="text-decoration-none fw-600">
                                    <?= htmlspecialchars(mb_substr($req['title'], 0, 40)) ?><?= mb_strlen($req['title']) > 40 ? '...' : '' ?>
                                </a>
                            </td>
                            <td><span class="badge bg-primary-soft text-primary"><?= htmlspecialchars($req['service_title'] ?? '—') ?></span></td>
                            <td><?= priorityLabel($req['priority']) ?></td>
                            <td><?= requestStatusLabel($req['status']) ?></td>
                            <td class="text-muted small"><?= formatDate($req['created_at']) ?></td>
                            <td>
                                <a href="/STD/student/request_details.php?id=<?= $req['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                    <i class="bi bi-eye me-1"></i>عرض
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-3">
            <?= renderPagination($result['current_page'], $result['total_pages'], '?status=' . urlencode($statusFilter)) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
