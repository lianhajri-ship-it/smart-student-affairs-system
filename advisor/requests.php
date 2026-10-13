<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdvisor();

$advisorId    = getUserId();
$statusFilter = clean($_GET['status'] ?? '');
$search       = clean($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));

$params = [$advisorId];
$where  = ['mr.assigned_to = ?'];

if (!empty($statusFilter)) { $where[] = 'mr.status=?'; $params[] = $statusFilter; }
if (!empty($search)) {
    $where[] = "(u.full_name LIKE ? OR mr.title LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}

$query = "SELECT mr.*, u.full_name as student_name,
                 s.title as service_title
          FROM requests mr
          JOIN users u ON u.id = mr.student_id
          LEFT JOIN services s ON s.id = mr.service_id
          WHERE " . implode(' AND ', $where) . "
          ORDER BY mr.created_at DESC";

$result   = paginate($pdo, $query, $params, $page, 15);
$requests = $result['data'];

$pageTitle = 'الطلبات الواردة';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-inbox-fill me-2"></i>الطلبات الواردة</h1>
                <p>طلبات طلابك المحالة إليك</p>
            </div>
            <div class="col-auto">
                <span class="badge bg-light text-dark fs-6"><?= $result['total'] ?> طلب</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="std-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <div class="search-bar">
                    <input type="text" name="q" class="form-control" placeholder="بحث بالاسم أو الموضوع..." value="<?= htmlspecialchars($search) ?>">
                    <i class="bi bi-search search-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <?php foreach (['new'=>'جديد','under_review'=>'تحت المراجعة','replied'=>'تم الرد','completed'=>'مكتمل','rejected'=>'مرفوض','closed'=>'مغلق'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $statusFilter===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">فلتر</button>
                <a href="?" class="btn btn-outline-secondary">إعادة</a>
            </div>
        </form>
    </div>

    <!-- Status Quick Filters -->
    <div class="d-flex gap-2 flex-wrap mb-3">
        <?php
        $quickTabs = [''=> 'الكل', 'new'=>'جديد', 'under_review'=>'تحت المراجعة', 'replied'=>'تم الرد', 'completed'=>'مكتمل'];
        foreach ($quickTabs as $v => $l): ?>
        <a href="?status=<?= $v ?>&q=<?= urlencode($search) ?>"
           class="btn btn-sm <?= $statusFilter===$v ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= $l ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Requests Table -->
    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
            <h5>لا توجد طلبات</h5>
            <p>لم يتم تحويل أي طلبات إليك بعد</p>
        </div>
    <?php else: ?>
        <div class="std-card">
            <div class="table-responsive">
                <table class="table table-std table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>الطالب</th><th>الموضوع</th><th>الخدمة</th><th>الأولوية</th><th>الحالة</th><th>التاريخ</th><th>الإجراءات</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td class="text-muted small">#<?= $req['id'] ?></td>
                            <td class="fw-600 small"><?= htmlspecialchars(mb_substr($req['student_name'],0,25)) ?></td>
                            <td>
                                <a href="/STD/advisor/request_details.php?id=<?= $req['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars(mb_substr($req['title'],0,35)) ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($req['service_title']): ?>
                                    <span class="badge bg-primary-soft text-primary small"><?= htmlspecialchars(mb_substr($req['service_title'],0,20)) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= priorityLabel($req['priority']) ?></td>
                            <td><?= requestStatusLabel($req['status']) ?></td>
                            <td class="text-muted small"><?= formatDate($req['created_at']) ?></td>
                            <td>
                                <a href="/STD/advisor/request_details.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-primary px-2">
                                    <i class="bi bi-eye me-1"></i>عرض
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            <?= renderPagination($result['current_page'], $result['total_pages'],
                '?status='.urlencode($statusFilter).'&q='.urlencode($search)) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
