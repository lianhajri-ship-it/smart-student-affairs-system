<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdvisor();

$advisorId = getUserId();
$requestId = (int)($_GET['id'] ?? 0);
if (!$requestId) { header('Location: /STD/advisor/requests.php'); exit; }

$stmt = $pdo->prepare("
    SELECT mr.*,
           u.full_name as student_name, u.email as student_email, u.phone as student_phone,
           u.student_number, c.title as college_title, m.title as major_title,
           s.title as service_title, s.icon as service_icon, s.description as service_desc,
           s.conditions as service_conditions, s.required_documents as service_docs,
           s.steps as service_steps, dep.title as service_dept
    FROM requests mr
    JOIN users u ON u.id = mr.student_id
    LEFT JOIN colleges c ON c.id = u.college_id
    LEFT JOIN majors m ON m.id = u.major_id
    LEFT JOIN services s ON s.id = mr.service_id
    LEFT JOIN departments dep ON dep.id = s.department_id
    WHERE mr.id = ? AND mr.assigned_to = ?
");
$stmt->execute([$requestId, $advisorId]);
$request = $stmt->fetch();

if (!$request) { header('Location: /STD/advisor/requests.php'); exit; }

$stmt = $pdo->prepare("
    SELECT rr.*, u.full_name, u.profile_image, ut.title as user_type
    FROM request_replies rr
    JOIN users u ON u.id = rr.user_id
    JOIN user_types ut ON ut.id = u.user_type_id
    WHERE rr.request_id = ? ORDER BY rr.created_at ASC
");
$stmt->execute([$requestId]);
$replies = $stmt->fetchAll();

$csrfToken = generateCsrfToken();
$pageTitle = 'تفاصيل الطلب #' . $requestId;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <nav class="modern-breadcrumb" aria-label="breadcrumb">
        <a href="/STD/advisor/dashboard.php" class="bc-home" title="لوحة التحكم"><i class="bi bi-house-fill"></i></a>
        <i class="bi bi-chevron-left bc-sep"></i>
        <a href="/STD/advisor/requests.php" class="bc-item"><i class="bi bi-inbox me-1"></i>الطلبات الواردة</a>
        <i class="bi bi-chevron-left bc-sep"></i>
        <span class="bc-item active"><i class="bi bi-file-text me-1"></i>طلب #<?= $requestId ?></span>
    </nav>

    <div class="row g-4">
        <!-- Main -->
        <div class="col-lg-8">
            <!-- Request Card -->
            <div class="std-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($request['title']) ?></h4>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($request['service_title']): ?>
                            <span class="badge bg-primary-soft text-primary"><?= htmlspecialchars($request['service_title']) ?></span>
                            <?php endif; ?>
                            <?= priorityLabel($request['priority']) ?>
                            <?= requestStatusLabel($request['status']) ?>
                        </div>
                    </div>
                    <span class="text-muted small"><?= formatDateTime($request['created_at']) ?></span>
                </div>
                <div class="p-3 bg-light rounded-3">
                    <p class="mb-0" style="white-space:pre-wrap;"><?= htmlspecialchars($request['description']) ?></p>
                </div>
                <?php if ($request['attachment']): ?>
                <div class="mt-3">
                    <a href="/STD/assets/uploads/<?= htmlspecialchars($request['attachment']) ?>" target="_blank"
                       class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip me-1"></i>عرض المرفق</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Service Info Panel -->
            <?php if ($request['service_title']): ?>
            <div class="std-card p-4 mb-4 border-start border-primary border-3">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary fs-5">
                        <i class="bi <?= htmlspecialchars($request['service_icon'] ?? 'bi-gear') ?>"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($request['service_title']) ?></h6>
                        <?php if ($request['service_dept']): ?>
                            <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($request['service_dept']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($request['service_desc']): ?>
                    <p class="small text-muted mb-3"><?= nl2br(htmlspecialchars($request['service_desc'])) ?></p>
                <?php endif; ?>
                <div class="row g-2">
                    <?php if ($request['service_conditions']): ?>
                    <div class="col-md-4">
                        <div class="p-2 bg-warning bg-opacity-10 rounded-3">
                            <p class="fw-bold text-warning-emphasis small mb-1"><i class="bi bi-shield-check me-1"></i>شروط الاستفادة</p>
                            <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($request['service_conditions'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($request['service_docs']): ?>
                    <div class="col-md-4">
                        <div class="p-2 bg-info bg-opacity-10 rounded-3">
                            <p class="fw-bold text-info-emphasis small mb-1"><i class="bi bi-paperclip me-1"></i>المستندات المطلوبة</p>
                            <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($request['service_docs'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($request['service_steps']): ?>
                    <div class="col-md-4">
                        <div class="p-2 bg-success bg-opacity-10 rounded-3">
                            <p class="fw-bold text-success-emphasis small mb-1"><i class="bi bi-list-ol me-1"></i>خطوات التنفيذ</p>
                            <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($request['service_steps'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Replies -->
            <div class="std-card p-4 mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-chat-left-text me-2 text-primary"></i>الردود (<?= count($replies) ?>)</h5>
                <?php if (empty($replies)): ?>
                    <div class="text-center py-4 text-muted"><i class="bi bi-chat-dots fs-2 d-block mb-2 opacity-25"></i>لا توجد ردود</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                    <?php foreach ($replies as $reply): $isStaff = $reply['user_type'] !== 'student'; ?>
                        <div class="d-flex gap-3 <?= $isStaff ? 'flex-row-reverse' : '' ?>">
                            <img src="<?= getUserAvatar($reply['profile_image'] ?? '') ?>" alt=""
                                 class="rounded-circle flex-shrink-0" style="width:42px;height:42px;object-fit:cover;">
                            <div class="flex-grow-1 <?= $isStaff ? 'text-end' : '' ?>">
                                <div class="d-flex align-items-center gap-2 mb-1 <?= $isStaff ? 'justify-content-end' : '' ?>">
                                    <strong class="small"><?= htmlspecialchars($reply['full_name']) ?></strong>
                                    <span class="badge <?= $isStaff ? 'bg-primary' : 'bg-secondary' ?> small">
                                        <?= $isStaff ? 'مرشد/إداري' : 'طالب' ?>
                                    </span>
                                    <span class="text-muted" style="font-size:.72rem;"><?= formatDateTime($reply['created_at']) ?></span>
                                </div>
                                <div class="p-3 rounded-3 d-inline-block text-start" style="max-width:90%;
                                    <?= $isStaff ? 'background:linear-gradient(135deg,#1a5f7a,#159895);color:#fff;' : 'background:#f8f9fa;border:1px solid #e9ecef;' ?>">
                                    <p class="mb-0 small" style="white-space:pre-wrap;"><?= htmlspecialchars($reply['reply_message']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reply Form -->
            <?php if (!in_array($request['status'], ['closed'])): ?>
            <div class="std-card p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-reply me-2 text-primary"></i>إضافة رد</h5>
                <form method="POST" action="/STD/backend/save_reply.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="request_id" value="<?= $requestId ?>">
                    <input type="hidden" name="redirect" value="advisor">
                    <div class="mb-3">
                        <textarea name="reply_message" class="form-control auto-resize" rows="4"
                                  placeholder="اكتب ردك..." required></textarea>
                    </div>
                    <div class="mb-3"><input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>إرسال الرد</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Update Status -->
            <div class="std-card p-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-primary"></i>تحديث الحالة</h6>
                <form method="POST" action="/STD/backend/update_request_status.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="request_id" value="<?= $requestId ?>">
                    <input type="hidden" name="redirect" value="advisor">
                    <div class="mb-3">
                        <label class="form-label small">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach (['new'=>'جديد','under_review'=>'تحت المراجعة','replied'=>'تم الرد','completed'=>'مكتمل','rejected'=>'مرفوض','closed'=>'مغلق'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= $request['status']===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-sm"><i class="bi bi-save me-1"></i>حفظ</button>
                </form>
            </div>

            <!-- Student Info -->
            <div class="std-card p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-person me-2 text-primary"></i>معلومات الطالب</h6>
                <div class="d-flex flex-column gap-2 small">
                    <div><span class="text-muted">الاسم:</span> <strong><?= htmlspecialchars($request['student_name']) ?></strong></div>
                    <div><span class="text-muted">رقم الطالب:</span> <?= htmlspecialchars($request['student_number'] ?? '—') ?></div>
                    <div><span class="text-muted">البريد:</span> <?= htmlspecialchars($request['student_email']) ?></div>
                    <div><span class="text-muted">الجوال:</span> <?= htmlspecialchars($request['student_phone'] ?? '—') ?></div>
                    <div><span class="text-muted">الكلية:</span> <?= htmlspecialchars($request['college_title'] ?? '—') ?></div>
                    <?php if ($request['major_title']): ?>
                    <div><span class="text-muted">التخصص:</span> <?= htmlspecialchars($request['major_title']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
