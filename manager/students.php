<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$colleges = $pdo->query("SELECT * FROM colleges WHERE status='active' ORDER BY title")->fetchAll();
$degrees  = $pdo->query("SELECT * FROM degrees  WHERE status='active' ORDER BY title")->fetchAll();
$majors   = $pdo->query("SELECT * FROM majors   WHERE status='active' ORDER BY title")->fetchAll();

$advisorTypeId = (int)$pdo->query("SELECT id FROM user_types WHERE title='academic_advisor'")->fetchColumn();
$advisors = $pdo->prepare("SELECT id, full_name FROM users WHERE user_type_id=? AND status='active' ORDER BY full_name");
$advisors->execute([$advisorTypeId]);
$advisors = $advisors->fetchAll();

$search     = clean($_GET['q'] ?? '');
$collegeF   = (int)($_GET['college'] ?? 0);
$statusF    = clean($_GET['status'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));

$params = [1]; // user_type_id for student
$where  = ['u.user_type_id = ?'];
if (!empty($search)) {
    $where[]  = "(u.full_name LIKE ? OR u.email LIKE ? OR u.student_number LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($collegeF > 0) { $where[] = 'u.college_id=?'; $params[] = $collegeF; }
if (!empty($statusF)) { $where[] = 'u.status=?'; $params[] = $statusF; }

$query = "SELECT u.*, c.title as college_title, d.title as degree_title,
                 adv.full_name as advisor_name
          FROM users u
          LEFT JOIN colleges c   ON c.id   = u.college_id
          LEFT JOIN degrees  d   ON d.id   = u.degree_id
          LEFT JOIN users    adv ON adv.id = u.academic_advisor_id
          WHERE " . implode(' AND ', $where) . " ORDER BY u.created_at DESC";

$result   = paginate($pdo, $query, $params, $page, 15);
$students = $result['data'];

// بيانات الطالب للتعديل
$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=? AND user_type_id=1");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$csrfToken = generateCsrfToken();
$pageTitle = 'إدارة الطلاب';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col"><h1><i class="bi bi-person-badge-fill me-2"></i>إدارة الطلاب</h1><p>عرض وإدارة حسابات الطلاب</p></div>
            <div class="col-auto">
                <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#studentModal">
                    <i class="bi bi-plus-circle me-1"></i>إضافة طالب
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="std-card p-3 mb-4">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <div class="search-bar">
                    <input type="text" name="q" class="form-control" placeholder="بحث بالاسم أو البريد أو الرقم..." value="<?= htmlspecialchars($search) ?>">
                    <i class="bi bi-search search-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <select name="college" class="form-select">
                    <option value="0">كل الكليات</option>
                    <?php foreach ($colleges as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $collegeF==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <option value="active" <?= $statusF==='active'?'selected':'' ?>>نشط</option>
                    <option value="inactive" <?= $statusF==='inactive'?'selected':'' ?>>غير نشط</option>
                    <option value="suspended" <?= $statusF==='suspended'?'selected':'' ?>>موقوف</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">فلتر</button>
                <a href="?" class="btn btn-outline-secondary">إعادة</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead><tr><th>#</th><th>الاسم</th><th>رقم الطالب</th><th>الكلية</th><th>المرشد الأكاديمي</th><th>الحالة</th><th>تاريخ التسجيل</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td class="text-muted small"><?= $s['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= getUserAvatar($s['profile_image'] ?? '', $s['gender']) ?>" alt="" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                                <div>
                                    <div class="fw-600 small"><?= htmlspecialchars($s['full_name']) ?></div>
                                    <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($s['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?= htmlspecialchars($s['student_number'] ?? '—') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($s['college_title'] ?? '—') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($s['advisor_name'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-<?= $s['status']==='active'?'success':($s['status']==='suspended'?'danger':'secondary') ?>">
                                <?= $s['status']==='active'?'نشط':($s['status']==='suspended'?'موقوف':'غير نشط') ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= formatDate($s['created_at']) ?></td>
                        <td class="d-flex gap-1">
                            <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <?php if ($s['status']==='active'): ?>
                                <a href="/STD/backend/toggle_user_status.php?id=<?= $s['id'] ?>&action=suspend&token=<?= urlencode($csrfToken) ?>"
                                   class="btn btn-sm btn-outline-warning" data-confirm="إيقاف هذا الحساب؟"><i class="bi bi-pause-circle"></i></a>
                            <?php else: ?>
                                <a href="/STD/backend/toggle_user_status.php?id=<?= $s['id'] ?>&action=activate&token=<?= urlencode($csrfToken) ?>"
                                   class="btn btn-sm btn-outline-success" data-confirm="تفعيل هذا الحساب؟"><i class="bi bi-play-circle"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3"><?= renderPagination($result['current_page'], $result['total_pages'], '?q='.urlencode($search).'&college='.$collegeF.'&status='.urlencode($statusF)) ?></div>
</div>

<!-- Student Modal -->
<div class="modal fade <?= $edit?'show d-block':'' ?>" id="studentModal" tabindex="-1" <?= $edit?'style="background:rgba(0,0,0,.5);"':'' ?>>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit?'تعديل بيانات الطالب':'إضافة طالب جديد' ?></h5>
                <a href="/STD/manager/students.php" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST" action="/STD/backend/save_user.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                <input type="hidden" name="user_type" value="student">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم الكامل *</label>
                            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($edit['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الطالب</label>
                            <input type="text" name="student_number" class="form-control" value="<?= htmlspecialchars($edit['student_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني *</label>
                            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($edit['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الجوال</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($edit['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الهوية الوطنية</label>
                            <input type="text" name="national_id" class="form-control" value="<?= htmlspecialchars($edit['national_id'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الجنس</label>
                            <select name="gender" class="form-select">
                                <option value="male" <?= ($edit['gender']??'')==='male'?'selected':'' ?>>ذكر</option>
                                <option value="female" <?= ($edit['gender']??'')==='female'?'selected':'' ?>>أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الكلية</label>
                            <select name="college_id" class="form-select">
                                <option value="">اختر</option>
                                <?php foreach ($colleges as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($edit['college_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الدرجة العلمية</label>
                            <select name="degree_id" class="form-select">
                                <option value="">اختر</option>
                                <?php foreach ($degrees as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($edit['degree_id']??'')==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">التخصص</label>
                            <select name="major_id" class="form-select">
                                <option value="">اختر</option>
                                <?php foreach ($majors as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= ($edit['major_id']??'')==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">المشرف الأكاديمي</label>
                            <select name="academic_advisor_id" class="form-select">
                                <option value="">اختر</option>
                                <?php foreach ($advisors as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= ($edit['academic_advisor_id']??'')==$a['id']?'selected':'' ?>><?= htmlspecialchars($a['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($edit['status']??'active')==='active'?'selected':'' ?>>نشط</option>
                                <option value="inactive" <?= ($edit['status']??'')==='inactive'?'selected':'' ?>>غير نشط</option>
                                <option value="suspended" <?= ($edit['status']??'')==='suspended'?'selected':'' ?>>موقوف</option>
                            </select>
                        </div>
                        <?php if (!$edit): ?>
                        <div class="col-12">
                            <label class="form-label">كلمة المرور *</label>
                            <input type="password" name="password" class="form-control" required minlength="8" placeholder="8 أحرف على الأقل">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="/STD/manager/students.php" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
