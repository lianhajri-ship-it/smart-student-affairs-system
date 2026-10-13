<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!isAdmin() && !isAdvisor()) { header('Location: /STD/index.php'); exit; }

$meetingId = (int)($_GET['id'] ?? 0);
if (!$meetingId) { header('Location: /STD/manager/meetings.php'); exit; }

$stmtM = $pdo->prepare("
    SELECT m.*, u.full_name as creator_name,
           COUNT(r.id) as total_recs,
           SUM(r.rec_status IS NOT NULL) as completed_recs
    FROM meeting_minutes m
    JOIN users u ON u.id = m.created_by
    LEFT JOIN meeting_recommendations r ON r.meeting_id = m.id
    WHERE m.id = ?
    GROUP BY m.id
");
$stmtM->execute([$meetingId]);
$meeting = $stmtM->fetch();
if (!$meeting) { header('Location: /STD/manager/meetings.php'); exit; }

$recs = $pdo->prepare("SELECT * FROM meeting_recommendations WHERE meeting_id=? ORDER BY id ASC");
$recs->execute([$meetingId]);
$recommendations = $recs->fetchAll();

$csrfToken = generateCsrfToken();

$recStatusLabels = [
    'approved' => ['success', 'موافقة',    'check-circle-fill'],
    'rejected' => ['danger',  'رفض',       'x-circle-fill'],
    'other'    => ['secondary','أخرى',     'three-dots'],
    'notes'    => ['info',    'ملاحظات',   'info-circle-fill'],
];
$supportTypeLabels = [
    'loan'          => 'سلفة',
    'prize'         => 'جائزة',
    'fixed_grant'   => 'إعانة مقطوعة',
    'marriage_grant'=> 'إعانة زواج',
    'other'         => 'أخرى',
];

$pageTitle = 'تفاصيل الاجتماع: ' . $meeting['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <nav class="modern-breadcrumb" aria-label="breadcrumb">
        <a href="/STD/manager/meetings.php" class="bc-home" title="المحاضر"><i class="bi bi-journal-text"></i></a>
        <i class="bi bi-chevron-left bc-sep"></i>
        <span class="bc-item active"><?= htmlspecialchars($meeting['title']) ?></span>
    </nav>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">

            <!-- Meeting Info Card -->
            <div class="std-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($meeting['title']) ?></h4>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($meeting['meeting_date']) ?></span>
                            <span class="text-muted small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($meeting['creator_name']) ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <?php if ($meeting['status'] === 'complete'): ?>
                            <span class="badge bg-success fs-6"><i class="bi bi-check-circle-fill me-1"></i>مكتمل</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark fs-6"><i class="bi bi-hourglass-split me-1"></i>غير مكتمل</span>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="editMeeting(<?= htmlspecialchars(json_encode($meeting)) ?>)"
                                data-bs-toggle="modal" data-bs-target="#meetingEditModal">
                            <i class="bi bi-pencil me-1"></i>تعديل
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($meeting['notes']): ?>
                <div class="p-3 bg-light rounded-3 mt-3">
                    <p class="mb-0 small" style="white-space:pre-wrap;"><?= htmlspecialchars($meeting['notes']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recommendations Section -->
            <div class="std-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-list-check me-2 text-primary"></i>
                        التوصيات
                        <span class="badge bg-primary ms-1"><?= count($recommendations) ?></span>
                    </h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recModal"
                            onclick="resetRecForm()">
                        <i class="bi bi-plus-circle me-1"></i>إضافة توصية
                    </button>
                </div>

                <?php if (empty($recommendations)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-list-check fs-1 d-block mb-3 opacity-25"></i>
                        <p>لا توجد توصيات بعد</p>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recModal"
                                onclick="resetRecForm()">
                            <i class="bi bi-plus-circle me-1"></i>إضافة أول توصية
                        </button>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                    <?php foreach ($recommendations as $i => $rec):
                        $statusInfo = $recStatusLabels[$rec['rec_status']] ?? ['secondary', 'في الانتظار', 'clock'];
                    ?>
                    <div class="border rounded-3 p-3" style="border-color:#e9ecef!important;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-primary-soft text-primary flex-shrink-0 mt-1"><?= $i + 1 ?></span>
                                <div>
                                    <p class="fw-600 mb-1"><?= htmlspecialchars($rec['title']) ?></p>
                                    <span class="badge bg-<?= $statusInfo[0] ?>">
                                        <i class="bi bi-<?= $statusInfo[2] ?> me-1"></i><?= $statusInfo[1] ?>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="editRec(<?= htmlspecialchars(json_encode($rec)) ?>)"
                                        title="تعديل">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if (isAdmin()): ?>
                                <a href="/STD/backend/delete_item.php?type=recommendation&id=<?= $rec['id'] ?>&meeting_id=<?= $meetingId ?>&csrf=<?= $csrfToken ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('حذف هذه التوصية؟')"
                                   title="حذف">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="row g-2 mt-1">
                            <?php if ($rec['support_type']): ?>
                            <div class="col-sm-4">
                                <div class="p-2 bg-light rounded-3 small">
                                    <span class="text-muted d-block" style="font-size:.72rem;">نوع الدعم</span>
                                    <strong><?= $supportTypeLabels[$rec['support_type']] ?? $rec['support_type'] ?></strong>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($rec['support_amount'] !== null): ?>
                            <div class="col-sm-4">
                                <div class="p-2 bg-light rounded-3 small">
                                    <span class="text-muted d-block" style="font-size:.72rem;">مبلغ الدعم</span>
                                    <strong><?= number_format($rec['support_amount'], 2) ?> ريال</strong>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($rec['disbursement_status']): ?>
                            <div class="col-sm-4">
                                <div class="p-2 <?= $rec['disbursement_status']==='disbursed'?'bg-success bg-opacity-10':'bg-danger bg-opacity-10' ?> rounded-3 small">
                                    <span class="text-muted d-block" style="font-size:.72rem;">حالة الصرف</span>
                                    <strong class="<?= $rec['disbursement_status']==='disbursed'?'text-success':'text-danger' ?>">
                                        <i class="bi bi-<?= $rec['disbursement_status']==='disbursed'?'check-circle-fill':'x-circle-fill' ?> me-1"></i>
                                        <?= $rec['disbursement_status']==='disbursed'?'تم الصرف':'لم يتم الصرف' ?>
                                    </strong>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($rec['rec_notes']): ?>
                        <div class="mt-2 p-2 rounded-3" style="background:#f8f9fa;border-right:3px solid #1a5f7a;">
                            <span class="text-muted small d-block" style="font-size:.72rem;">ملاحظات</span>
                            <p class="mb-0 small" style="white-space:pre-wrap;"><?= htmlspecialchars($rec['rec_notes']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="std-card p-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>ملخص التوصيات</h6>
                <?php
                $total     = (int)$meeting['total_recs'];
                $completed = (int)$meeting['completed_recs'];
                $pct       = $total > 0 ? round($completed / $total * 100) : 0;
                ?>
                <div class="d-flex justify-content-between small mb-1">
                    <span>المنجزة</span>
                    <strong><?= $completed ?>/<?= $total ?></strong>
                </div>
                <div class="progress mb-3" style="height:10px;border-radius:5px;">
                    <div class="progress-bar bg-success" style="width:<?= $pct ?>%;border-radius:5px;"></div>
                </div>

                <?php
                $countByStatus = [];
                foreach ($recommendations as $r) {
                    $k = $r['rec_status'] ?? 'pending';
                    $countByStatus[$k] = ($countByStatus[$k] ?? 0) + 1;
                }
                $statusRows = [
                    'approved' => ['موافقة',   'success'],
                    'rejected' => ['رفض',      'danger'],
                    'other'    => ['أخرى',     'secondary'],
                    'notes'    => ['ملاحظات',  'info'],
                    'pending'  => ['في الانتظار','warning'],
                ];
                foreach ($statusRows as $k => [$label, $color]):
                    if (!isset($countByStatus[$k])) continue;
                ?>
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                    <span class="small"><?= $label ?></span>
                    <span class="badge bg-<?= $color ?>"><?= $countByStatus[$k] ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="std-card p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>معلومات المحضر</h6>
                <div class="d-flex flex-column gap-2 small">
                    <div><span class="text-muted">رقم المحضر:</span> <strong>#<?= $meeting['id'] ?></strong></div>
                    <div><span class="text-muted">التاريخ:</span> <?= htmlspecialchars($meeting['meeting_date']) ?></div>
                    <div><span class="text-muted">أضيف بواسطة:</span> <?= htmlspecialchars($meeting['creator_name']) ?></div>
                    <div><span class="text-muted">تاريخ الإنشاء:</span> <?= formatDateTime($meeting['created_at']) ?></div>
                    <div><span class="text-muted">الحالة:</span>
                        <span class="badge bg-<?= $meeting['status']==='complete'?'success':'warning text-dark' ?>">
                            <?= $meeting['status']==='complete'?'مكتمل':'غير مكتمل' ?>
                        </span>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <a href="/STD/manager/meetings.php" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-arrow-right me-1"></i>العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Recommendation Modal -->
<div class="modal fade" id="recModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="/STD/backend/save_recommendation.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recModalTitle">إضافة توصية</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" id="recId" value="0">
                    <input type="hidden" name="meeting_id" value="<?= $meetingId ?>">

                    <div class="mb-3">
                        <label class="form-label">عنوان التوصية <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="recTitle" class="form-control" required
                               placeholder="وصف التوصية...">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">حالة التوصية</label>
                            <select name="rec_status" id="recStatus" class="form-select">
                                <option value="">— في الانتظار —</option>
                                <option value="approved">موافقة</option>
                                <option value="rejected">رفض</option>
                                <option value="other">أخرى</option>
                                <option value="notes">ملاحظات</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">حالة الصرف</label>
                            <select name="disbursement_status" id="recDisbursement" class="form-select">
                                <option value="">— غير محدد —</option>
                                <option value="disbursed">تم الصرف</option>
                                <option value="not_disbursed">لم يتم الصرف</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">نوع الدعم</label>
                            <select name="support_type" id="recSupportType" class="form-select">
                                <option value="">— غير محدد —</option>
                                <option value="loan">سلفة</option>
                                <option value="prize">جائزة</option>
                                <option value="fixed_grant">إعانة مقطوعة</option>
                                <option value="marriage_grant">إعانة زواج</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">مبلغ الدعم (ريال)</label>
                            <input type="number" name="support_amount" id="recSupportAmount"
                                   class="form-control" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="rec_notes" id="recNotes" class="form-control" rows="3"
                                  placeholder="ملاحظات إضافية..."></textarea>
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

<?php if (isAdmin()): ?>
<!-- Edit Meeting Modal -->
<div class="modal fade" id="meetingEditModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/STD/backend/save_meeting.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل الاجتماع</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" id="meetingEditId" value="<?= $meetingId ?>">

                    <div class="mb-3">
                        <label class="form-label">عنوان الاجتماع <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="meetingEditTitle" class="form-control" required
                               value="<?= htmlspecialchars($meeting['title']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ الاجتماع <span class="text-danger">*</span></label>
                        <input type="date" name="meeting_date" id="meetingEditDate" class="form-control" required
                               value="<?= htmlspecialchars($meeting['meeting_date']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" id="meetingEditNotes" class="form-control" rows="3"><?= htmlspecialchars($meeting['notes'] ?? '') ?></textarea>
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
<?php endif; ?>

<script>
function resetRecForm() {
    document.getElementById('recModalTitle').textContent  = 'إضافة توصية';
    document.getElementById('recId').value                = '0';
    document.getElementById('recTitle').value             = '';
    document.getElementById('recStatus').value            = '';
    document.getElementById('recDisbursement').value      = '';
    document.getElementById('recSupportType').value       = '';
    document.getElementById('recSupportAmount').value     = '';
    document.getElementById('recNotes').value             = '';
}
function editRec(data) {
    document.getElementById('recModalTitle').textContent  = 'تعديل التوصية';
    document.getElementById('recId').value                = data.id;
    document.getElementById('recTitle').value             = data.title;
    document.getElementById('recStatus').value            = data.rec_status || '';
    document.getElementById('recDisbursement').value      = data.disbursement_status || '';
    document.getElementById('recSupportType').value       = data.support_type || '';
    document.getElementById('recSupportAmount').value     = data.support_amount || '';
    document.getElementById('recNotes').value             = data.rec_notes || '';
    new bootstrap.Modal(document.getElementById('recModal')).show();
}
function editMeeting(data) {
    document.getElementById('meetingEditTitle').value = data.title;
    document.getElementById('meetingEditDate').value  = data.meeting_date;
    document.getElementById('meetingEditNotes').value = data.notes || '';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
