<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$statusFilter = clean($_GET['status'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));

$params = [];
$where  = ['1=1'];
if (!empty($statusFilter)) {
    $where[]  = 'cr.review_status=?';
    $params[] = $statusFilter;
}

$query = "
    SELECT cr.*,
           reviewer.full_name as reviewer_name,
           cc.student_id,
           student.full_name as student_name,
           cm.message_text as message_content
    FROM chatbot_reviews cr
    JOIN users reviewer ON reviewer.id = cr.reviewed_by
    JOIN chatbot_conversations cc ON cc.id = cr.conversation_id
    JOIN users student ON student.id = cc.student_id
    LEFT JOIN chatbot_messages cm ON cm.id = cr.message_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY cr.created_at DESC
";

$result  = paginate($pdo, $query, $params, $page, 20);
$reviews = $result['data'];

$rawStats = $pdo->query("SELECT review_status, COUNT(*) as cnt FROM chatbot_reviews GROUP BY review_status")->fetchAll();
$stats = array_column($rawStats, 'cnt', 'review_status');

$pageTitle = 'مراجعات الشات بوت';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-chat-square-text-fill me-2"></i>مراجعات الشات بوت</h1>
                <p>مراجعات وملاحظات أداء الشات بوت</p>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:#1a5f7a;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#e8f4f8;color:#1a5f7a;"><i class="bi bi-chat-dots-fill"></i></div>
                    <div>
                        <div class="stat-number" style="color:#1a5f7a;"><?= array_sum($stats) ?></div>
                        <div class="stat-label">إجمالي المراجعات</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:#27ae60;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#eafaf1;color:#27ae60;"><i class="bi bi-hand-thumbs-up-fill"></i></div>
                    <div>
                        <div class="stat-number" style="color:#27ae60;"><?= $stats['good'] ?? 0 ?></div>
                        <div class="stat-label">جيد</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:#f39c12;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fef9e7;color:#f39c12;"><i class="bi bi-pencil-square"></i></div>
                    <div>
                        <div class="stat-number" style="color:#f39c12;"><?= $stats['needs_improvement'] ?? 0 ?></div>
                        <div class="stat-label">يحتاج تحسين</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card" style="border-color:#e74c3c;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fdedec;color:#e74c3c;"><i class="bi bi-hand-thumbs-down-fill"></i></div>
                    <div>
                        <div class="stat-number" style="color:#e74c3c;"><?= $stats['bad'] ?? 0 ?></div>
                        <div class="stat-label">سيء</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="std-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">تصفية حسب التقييم</label>
                <select name="status" class="form-select">
                    <option value="">كل التقييمات</option>
                    <option value="good"              <?= $statusFilter==='good'             ?'selected':'' ?>>جيد</option>
                    <option value="needs_improvement" <?= $statusFilter==='needs_improvement'?'selected':'' ?>>يحتاج تحسين</option>
                    <option value="bad"               <?= $statusFilter==='bad'              ?'selected':'' ?>>سيء</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">فلتر</button>
                <a href="?" class="btn btn-outline-secondary">إعادة</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <?php if (empty($reviews)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-chat-square-text"></i></div>
            <h5>لا توجد مراجعات</h5>
        </div>
    <?php else: ?>
    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الطالب</th>
                        <th>رسالة الشات بوت</th>
                        <th>التقييم</th>
                        <th>ملاحظة</th>
                        <th>المراجع</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $r):
                        $statusBadge = match($r['review_status']) {
                            'good'              => ['success',   'جيد',          'hand-thumbs-up-fill'],
                            'needs_improvement' => ['warning',   'يحتاج تحسين',  'pencil-square'],
                            'bad'               => ['danger',    'سيء',          'hand-thumbs-down-fill'],
                            default             => ['secondary', $r['review_status'], 'question'],
                        };
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $r['id'] ?></td>
                        <td class="small fw-600"><?= htmlspecialchars($r['student_name']) ?></td>
                        <td class="small text-muted" style="max-width:260px;">
                            <?php if ($r['message_content']): ?>
                                <span title="<?= htmlspecialchars($r['message_content']) ?>">
                                    <?= htmlspecialchars(mb_substr($r['message_content'], 0, 80)) ?><?= mb_strlen($r['message_content'] ?? '') > 80 ? '…' : '' ?>
                                </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $statusBadge[0] ?>">
                                <i class="bi bi-<?= $statusBadge[2] ?> me-1"></i><?= $statusBadge[1] ?>
                            </span>
                        </td>
                        <td class="small text-muted" style="max-width:200px;">
                            <?= $r['review_note'] ? htmlspecialchars(mb_substr($r['review_note'], 0, 80)) : '—' ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($r['reviewer_name']) ?></td>
                        <td class="small text-muted"><?= formatDateTime($r['created_at']) ?></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
