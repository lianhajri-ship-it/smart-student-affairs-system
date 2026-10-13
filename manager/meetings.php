<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!isAdmin() && !isAdvisor()) { header('Location: /STD/index.php'); exit; }

$statusFilter = clean($_GET['status'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if (!empty($statusFilter)) {
    $where[]  = 'm.status=?';
    $params[] = $statusFilter;
}

$query = "
    SELECT m.*,
           u.full_name as creator_name,
           COUNT(r.id) as total_recs,
           SUM(r.rec_status IS NOT NULL) as completed_recs
    FROM meeting_minutes m
    JOIN users u ON u.id = m.created_by
    LEFT JOIN meeting_recommendations r ON r.meeting_id = m.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY m.id
    ORDER BY m.meeting_date DESC, m.created_at DESC
";

$result   = paginate($pdo, $query, $params, $page, 20);
$meetings = $result['data'];

$totalCount      = $pdo->query("SELECT COUNT(*) FROM meeting_minutes")->fetchColumn();
$completeCount   = $pdo->query("SELECT COUNT(*) FROM meeting_minutes WHERE status='complete'")->fetchColumn();
$incompleteCount = $pdo->query("SELECT COUNT(*) FROM meeting_minutes WHERE status='incomplete'")->fetchColumn();

$csrfToken = generateCsrfToken();
$pageTitle = 'محاضر الاجتماعات';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-journal-text me-2"></i>محاضر الاجتماعات</h1>
                <p>إدارة محاضر الاجتماعات وتوصياتها</p>
            </div>
            <?php if (isAdmin()): ?>
            <div class="col-auto">
                <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#meetingModal"
                        onclick="resetForm()">
                    <i class="bi bi-plus-circle me-1"></i>إضافة اجتماع
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
            <div class="stat-card" style="border-color:#1a5f7a;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#e8f4f8;color:#1a5f7a;"><i class="bi bi-journal-text"></i></div>
                    <div>
                        <div class="stat-number" style="color:#1a5f7a;"><?= $totalCount ?></div>
                        <div class="stat-label">إجمالي المحاضر</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="stat-card" style="border-color:#27ae60;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#eafaf1;color:#27ae60;"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="stat-number" style="color:#27ae60;"><?= $completeCount ?></div>
                        <div class="stat-label">مكتمل</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="stat-card" style="border-color:#f39c12;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fef9e7;color:#f39c12;"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-number" style="color:#f39c12;"><?= $incompleteCount ?></div>
                        <div class="stat-label">غير مكتمل</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="std-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">تصفية حسب الحالة</label>
                <select name="status" class="form-select">
                    <option value="">كل المحاضر</option>
                    <option value="complete"   <?= $statusFilter==='complete'  ?'selected':'' ?>>مكتمل</option>
                    <option value="incomplete" <?= $statusFilter==='incomplete'?'selected':'' ?>>غير مكتمل</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">فلتر</button>
                <a href="?" class="btn btn-outline-secondary">إعادة</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <?php if (empty($meetings)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-journal-text"></i></div>
            <h5>لا توجد محاضر اجتماعات</h5>
        </div>
    <?php else: ?>
    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>عنوان الاجتماع</th>
                        <th>التاريخ</th>
                        <th>التوصيات</th>
                        <th>الحالة</th>
                        <th>أضيف بواسطة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meetings as $m):
                        $total     = (int)$m['total_recs'];
                        $completed = (int)$m['completed_recs'];
                        $isComplete = $m['status'] === 'complete';
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $m['id'] ?></td>
                        <td class="fw-600">
                            <a href="/STD/manager/meeting_details.php?id=<?= $m['id'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($m['title']) ?>
                            </a>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($m['meeting_date']) ?></td>
                        <td class="small">
                            <?php if ($total > 0): ?>
                                <span class="badge <?= $isComplete ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= $completed ?>/<?= $total ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isComplete): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>مكتمل</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>غير مكتمل</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($m['creator_name']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="/STD/manager/meeting_details.php?id=<?= $m['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (isAdmin()): ?>
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="editItem(<?= htmlspecialchars(json_encode($m)) ?>)"
                                        title="تعديل">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="/STD/backend/delete_item.php?type=meeting&id=<?= $m['id'] ?>&csrf=<?= $csrfToken ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('حذف هذا المحضر وجميع توصياته؟')"
                                   title="حذف">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">
        <?= renderPagination($result['current_page'], $result['total_pages'], '?status='.urlencode($statusFilter)) ?>
    </div>
    <?php endif; ?>
</div>

<?php if (isAdmin()): ?>
<!-- Add/Edit Meeting Modal -->
<div class="modal fade" id="meetingModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/STD/backend/save_meeting.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">إضافة اجتماع</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" id="itemId" value="0">

                    <div class="mb-3">
                        <label class="form-label">عنوان الاجتماع <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="itemTitle" class="form-control" required
                               placeholder="مثال: اجتماع لجنة الشؤون الطلابية">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ الاجتماع <span class="text-danger">*</span></label>
                        <input type="date" name="meeting_date" id="itemDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" id="itemNotes" class="form-control" rows="3"
                                  placeholder="ملاحظات إضافية عن الاجتماع..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>حفظ</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').textContent  = 'إضافة اجتماع';
    document.getElementById('itemId').value            = '0';
    document.getElementById('itemTitle').value         = '';
    document.getElementById('itemDate').value          = '';
    document.getElementById('itemNotes').value         = '';
}
function editItem(data) {
    document.getElementById('modalTitle').textContent  = 'تعديل الاجتماع';
    document.getElementById('itemId').value            = data.id;
    document.getElementById('itemTitle').value         = data.title;
    document.getElementById('itemDate').value          = data.meeting_date;
    document.getElementById('itemNotes').value         = data.notes || '';
    new bootstrap.Modal(document.getElementById('meetingModal')).show();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
