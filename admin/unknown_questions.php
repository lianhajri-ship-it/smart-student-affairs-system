<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$statusFilter = clean($_GET['status'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if (!empty($statusFilter)) {
    $where[]  = 'uq.status=?';
    $params[] = $statusFilter;
}

$query = "
    SELECT uq.*,
           u.full_name as student_name,
           u.student_number,
           rv.full_name as reviewer_name
    FROM unknown_questions uq
    JOIN users u ON u.id = uq.student_id
    LEFT JOIN users rv ON rv.id = uq.reviewed_by
    WHERE " . implode(' AND ', $where) . "
    ORDER BY uq.created_at DESC
";

$result    = paginate($pdo, $query, $params, $page, 25);
$questions = $result['data'];

$stats = [
    'pending'    => $pdo->query("SELECT COUNT(*) FROM unknown_questions WHERE status='pending'")->fetchColumn(),
    'reviewed'   => $pdo->query("SELECT COUNT(*) FROM unknown_questions WHERE status='reviewed'")->fetchColumn(),
    'added_to_kb'=> $pdo->query("SELECT COUNT(*) FROM unknown_questions WHERE status='added_to_kb'")->fetchColumn(),
];

$csrfToken = generateCsrfToken();
$pageTitle = 'الأسئلة غير المُجابة';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-question-circle-fill me-2"></i>الأسئلة غير المُجابة</h1>
                <p>أسئلة طرحها الطلاب في الشات بوت ولم تُعثر لها إجابة كافية في قاعدة المعرفة</p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
            <div class="stat-card" style="border-color:#e67e22;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fef9e7;color:#e67e22;"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-number" style="color:#e67e22;"><?= $stats['pending'] ?></div>
                        <div class="stat-label">في الانتظار</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="stat-card" style="border-color:#2980b9;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#ebf5fb;color:#2980b9;"><i class="bi bi-eye-fill"></i></div>
                    <div>
                        <div class="stat-number" style="color:#2980b9;"><?= $stats['reviewed'] ?></div>
                        <div class="stat-label">تمت المراجعة</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="stat-card" style="border-color:#27ae60;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#eafaf1;color:#27ae60;"><i class="bi bi-database-fill-add"></i></div>
                    <div>
                        <div class="stat-number" style="color:#27ae60;"><?= $stats['added_to_kb'] ?></div>
                        <div class="stat-label">أُضيف للمعرفة</div>
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
                    <option value="">كل الأسئلة</option>
                    <option value="pending"    <?= $statusFilter==='pending'    ?'selected':'' ?>>في الانتظار</option>
                    <option value="reviewed"   <?= $statusFilter==='reviewed'   ?'selected':'' ?>>تمت المراجعة</option>
                    <option value="added_to_kb"<?= $statusFilter==='added_to_kb'?'selected':'' ?>>أُضيف للمعرفة</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">فلتر</button>
                <a href="?" class="btn btn-outline-secondary">إعادة</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <?php if (empty($questions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-question-circle"></i></div>
            <h5>لا توجد أسئلة غير مُجابة</h5>
            <p class="text-muted">رائع! كل الأسئلة لها إجابات في قاعدة المعرفة.</p>
        </div>
    <?php else: ?>
    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>سؤال الطالب</th>
                        <th>الطالب</th>
                        <th>الثقة</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $q):
                        $statusMap = [
                            'pending'     => ['warning text-dark', 'في الانتظار',    'hourglass-split'],
                            'reviewed'    => ['info',              'تمت المراجعة',  'eye-fill'],
                            'added_to_kb' => ['success',           'أُضيف للمعرفة', 'database-fill-add'],
                        ];
                        [$sBadge, $sLabel, $sIcon] = $statusMap[$q['status']] ?? ['secondary','—','question'];
                        $score = (float)$q['confidence_score'];
                        $scoreColor = $score < 0.1 ? 'danger' : ($score < 0.25 ? 'warning' : 'info');
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $q['id'] ?></td>
                        <td style="max-width:340px;">
                            <p class="mb-0 small fw-600 text-dark" style="white-space:pre-wrap;word-break:break-word;">
                                <?= htmlspecialchars(mb_substr($q['question_text'], 0, 120)) ?><?= mb_strlen($q['question_text']) > 120 ? '…' : '' ?>
                            </p>
                        </td>
                        <td class="small">
                            <?= htmlspecialchars($q['student_name']) ?>
                            <?php if ($q['student_number']): ?>
                                <br><span class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($q['student_number']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $scoreColor ?>"><?= round($score * 100) ?>%</span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $sBadge ?>">
                                <i class="bi bi-<?= $sIcon ?> me-1"></i><?= $sLabel ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= formatDateTime($q['created_at']) ?></td>
                        <td>
                            <div class="d-flex gap-1 flex-nowrap">
                                <!-- Quick add to KB -->
                                <button class="btn btn-sm btn-outline-success"
                                        onclick="openAddToKB(<?= $q['id'] ?>, <?= htmlspecialchars(json_encode($q['question_text'])) ?>)"
                                        title="إضافة لقاعدة المعرفة">
                                    <i class="bi bi-database-fill-add"></i>
                                </button>
                                <!-- Mark reviewed -->
                                <?php if ($q['status'] === 'pending'): ?>
                                <a href="/STD/backend/mark_unknown_reviewed.php?id=<?= $q['id'] ?>&action=reviewed&csrf=<?= $csrfToken ?>&status=<?= urlencode($statusFilter) ?>"
                                   class="btn btn-sm btn-outline-info" title="تمت المراجعة">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                <!-- Delete -->
                                <a href="/STD/backend/mark_unknown_reviewed.php?id=<?= $q['id'] ?>&action=delete&csrf=<?= $csrfToken ?>&status=<?= urlencode($statusFilter) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('حذف هذا السؤال؟')"
                                   title="حذف">
                                    <i class="bi bi-trash"></i>
                                </a>
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

<!-- Add to Knowledge Base Modal -->
<div class="modal fade" id="kbModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/STD/backend/save_knowledge.php" id="kbForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-database-fill-add me-2"></i>إضافة لقاعدة المعرفة</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="0">
                    <input type="hidden" name="uq_id" id="uqId" value="">

                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-lightbulb me-1"></i>
                        سؤال الطالب الأصلي مُضمَّن في الحقل أدناه كبداية للمحتوى — أكمل الإجابة ثم احفظ.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">عنوان المقالة <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="kbTitle" class="form-control" required
                               placeholder="عنوان واضح ومختصر للموضوع">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">النوع</label>
                            <select name="type" class="form-select">
                                <option value="faq">سؤال شائع (FAQ)</option>
                                <option value="info">معلومات عامة</option>
                                <option value="service">خدمة</option>
                                <option value="policy">سياسة / لائحة</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الكلمات المفتاحية</label>
                            <input type="text" name="keywords" id="kbKeywords" class="form-control"
                                   placeholder="فصل بفاصلة">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المحتوى / الإجابة <span class="text-danger">*</span></label>
                        <textarea name="content" id="kbContent" class="form-control" rows="6" required
                                  placeholder="اكتب الإجابة التفصيلية هنا..."></textarea>
                    </div>
                    <input type="hidden" name="status" value="published">
                    <input type="hidden" name="service_id" value="">
                    <input type="hidden" name="department_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>حفظ وإضافة للمعرفة</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openAddToKB(uqId, questionText) {
    document.getElementById('uqId').value     = uqId;
    document.getElementById('kbTitle').value  = questionText.substring(0, 100);
    document.getElementById('kbContent').value = 'س: ' + questionText + '\n\nج: ';
    document.getElementById('kbKeywords').value = '';
    // Patch form to also mark unknown question as added_to_kb on submit
    document.getElementById('kbForm').setAttribute('data-uq-id', uqId);
    new bootstrap.Modal(document.getElementById('kbModal')).show();
    // Focus on content to let admin write the answer
    setTimeout(() => document.getElementById('kbContent').focus(), 400);
}

// After KB save, also mark the unknown question as added_to_kb via hidden redirect
document.getElementById('kbForm').addEventListener('submit', function() {
    const uqId = this.getAttribute('data-uq-id');
    if (uqId) {
        // Append uq_id so the backend can mark it
        let inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'uq_id'; inp.value = uqId;
        this.appendChild(inp);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
