<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

$userId = getUserId();

$stmt = $pdo->prepare("
    SELECT u.*, ut.title as user_type, c.title as college_title, d.title as degree_title,
           m.title as major_title, adv.full_name as advisor_name
    FROM users u
    JOIN user_types ut ON ut.id = u.user_type_id
    LEFT JOIN colleges c ON c.id = u.college_id
    LEFT JOIN degrees d ON d.id = u.degree_id
    LEFT JOIN majors m ON m.id = u.major_id
    LEFT JOIN users adv ON adv.id = u.academic_advisor_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$colleges  = $pdo->query("SELECT * FROM colleges WHERE status='active' ORDER BY title")->fetchAll();
$degrees   = $pdo->query("SELECT * FROM degrees  WHERE status='active' ORDER BY title")->fetchAll();
$majors    = $pdo->query("SELECT * FROM majors   WHERE status='active' ORDER BY title")->fetchAll();

$advisorTypeId = (int)$pdo->query("SELECT id FROM user_types WHERE title='academic_advisor'")->fetchColumn();
$advisors = $pdo->prepare("SELECT id, full_name FROM users WHERE user_type_id=? AND status='active' ORDER BY full_name");
$advisors->execute([$advisorTypeId]);
$advisors = $advisors->fetchAll();

$csrfToken = generateCsrfToken();
$pageTitle = 'الملف الشخصي';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <h1><i class="bi bi-person-circle me-2"></i>الملف الشخصي</h1>
        <p>إدارة بياناتك الشخصية</p>
    </div>

    <div class="row g-4">
        <!-- Profile Card -->
        <div class="col-lg-4">
            <div class="std-card p-4 text-center">
                <div class="profile-avatar-wrap">
                    <img src="<?= getUserAvatar($user['profile_image'] ?? '', $user['gender']) ?>"
                         alt="صورتي" class="profile-avatar" id="avatarPreview">
                    <label for="avatarInput" class="profile-avatar-edit" title="تغيير الصورة">
                        <i class="bi bi-camera-fill"></i>
                    </label>
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name']) ?></h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <?php if ($user['student_number']): ?>
                    <span class="badge bg-primary-soft text-primary">رقم الطالب: <?= htmlspecialchars($user['student_number']) ?></span>
                <?php endif; ?>

                <hr>
                <div class="text-start">
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted"><i class="bi bi-building me-1"></i>الكلية</span>
                        <span class="fw-600"><?= htmlspecialchars($user['college_title'] ?? '—') ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted"><i class="bi bi-mortarboard me-1"></i>الدرجة</span>
                        <span class="fw-600"><?= htmlspecialchars($user['degree_title'] ?? '—') ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted"><i class="bi bi-journal-bookmark me-1"></i>التخصص</span>
                        <span class="fw-600"><?= htmlspecialchars($user['major_title'] ?? '—') ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted"><i class="bi bi-person-check me-1"></i>المرشد</span>
                        <span class="fw-600"><?= htmlspecialchars($user['advisor_name'] ?? '—') ?></span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted"><i class="bi bi-calendar me-1"></i>تاريخ التسجيل</span>
                        <span class="fw-600"><?= formatDate($user['created_at']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="col-lg-8">
            <!-- Personal Info -->
            <div class="std-card p-4 mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-person me-2 text-primary"></i>البيانات الشخصية</h5>
                <form method="POST" action="/STD/backend/update_profile.php" enctype="multipart/form-data" id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="file" id="avatarInput" name="profile_image" class="d-none" accept="image/*" data-preview="avatarPreview">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم الكامل</label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?= htmlspecialchars($user['full_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الجوال</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                            <div class="form-text">لا يمكن تغيير البريد الإلكتروني</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الميلاد</label>
                            <input type="date" name="date_of_birth" class="form-control"
                                   value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الكلية</label>
                            <select name="college_id" class="form-select">
                                <option value="">اختر الكلية</option>
                                <?php foreach ($colleges as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $user['college_id'] == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الدرجة العلمية</label>
                            <select name="degree_id" class="form-select">
                                <option value="">اختر الدرجة</option>
                                <?php foreach ($degrees as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= $user['degree_id'] == $d['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">التخصص</label>
                            <select name="major_id" class="form-select">
                                <option value="">اختر التخصص</option>
                                <?php foreach ($majors as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $user['major_id'] == $m['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">المرشد الأكاديمي</label>
                            <select name="academic_advisor_id" class="form-select">
                                <option value="">اختر المرشد</option>
                                <?php foreach ($advisors as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= $user['academic_advisor_id'] == $a['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="action" value="update_info" class="btn btn-primary px-5">
                                <i class="bi bi-save me-2"></i>حفظ التغييرات
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="std-card p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2 text-primary"></i>تغيير كلمة المرور</h5>
                <form method="POST" action="/STD/backend/update_profile.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">كلمة المرور الحالية</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">كلمة المرور الجديدة</label>
                            <input type="password" name="new_password" class="form-control" required minlength="8">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تأكيد كلمة المرور</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="action" value="change_password" class="btn btn-outline-primary px-5">
                                <i class="bi bi-lock me-2"></i>تغيير كلمة المرور
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
