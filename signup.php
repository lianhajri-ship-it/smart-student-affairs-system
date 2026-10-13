<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    $dest = match($_SESSION['user_type'] ?? '') {
        'admin'   => '/STD/admin/dashboard.php',
        'manager' => '/STD/manager/dashboard.php',
        default   => '/STD/student/dashboard.php',
    };
    header("Location: $dest"); exit;
}

$colleges    = $pdo->query("SELECT * FROM colleges  WHERE status='active' ORDER BY title")->fetchAll();
$degrees     = $pdo->query("SELECT * FROM degrees   WHERE status='active' ORDER BY title")->fetchAll();
$userTypes   = $pdo->query("SELECT * FROM user_types WHERE title NOT IN ('admin','manager','academic_advisor') ORDER BY title")->fetchAll();

// Advisors for the dropdown (users with academic_advisor role)
$advisorTypeId = (int)$pdo->query("SELECT id FROM user_types WHERE title='academic_advisor'")->fetchColumn();
$advisors = $pdo->prepare("SELECT id, full_name, college_id FROM users WHERE user_type_id=? AND status='active' ORDER BY full_name");
$advisors->execute([$advisorTypeId]);
$advisors = $advisors->fetchAll();

$csrfToken   = generateCsrfToken();
$siteName    = getSetting('site_name', 'نظام شؤون الطلاب');
$uniName     = getSetting('university_name', 'الجامعة');
$siteLogo    = getSetting('site_logo', '');

$pageTitle = 'تسجيل حساب جديد';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle . ' | ' . $siteName) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/STD/assets/css/style.css">
    <style>
          body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d3f52 0%, #1a5f7a 50%, #159895 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .signup-wrap {
            max-width: 720px;
            margin: 2rem auto;
            padding: 0 1rem;
            width: 100%;
        }
        .signup-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(37,74,124,.12);
            overflow: hidden;
        }
        .signup-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary) 60%, var(--accent));
            padding: 2rem;
            text-align: center;
            color: #fff;
        }
        .signup-header .logo-icon {
            width: 160px; height: 160px;
            background: rgba(255,255,255,.18);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 1rem;
        }


        .logo-icon img{
              width:130px !important;;
        height:130px !important;
        border-radius: 20px;

        }
        .signup-header h2 { font-size: 1.3rem; font-weight: 800; margin: 0; }
        .signup-header p  { opacity: .85; font-size: .88rem; margin: .3rem 0 0; }
        .signup-body { padding: 2rem; }
        .section-title {
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .06em;
            color: var(--primary);
            text-transform: uppercase;
            margin: 1.5rem 0 .75rem;
            padding-bottom: .35rem;
            border-bottom: 2px solid rgba(37,74,124,.12);
        }
        .section-title:first-of-type { margin-top: 0; }
        .form-control:disabled, .form-select:disabled { background: #f5f7fa; }
        .step-dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,.4);
            margin: 0 3px;
        }
        .step-dot.active { background: #fff; }
    </style>
</head>
<body class="guest-layout">

<div class="signup-wrap">
    <div class="signup-card">

        <!-- Header -->
        <div class="signup-header">
            <div class="logo-icon">
                <?php if ($siteLogo): ?>
                    <img src="/STD/assets/uploads/<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" style="width:48px;height:48px;object-fit:contain;">
                <?php else: ?>
                    <i class="bi bi-mortarboard-fill"></i>
                <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($siteName) ?></h2>
            <p><?= htmlspecialchars($uniName) ?> — تسجيل حساب طالب جديد</p>
        </div>

        <!-- Flash -->
        <?php
        $flash = getFlash();
        if ($flash):
            $cls = $flash['type'] === 'success' ? 'success' : 'danger';
        ?>
        <div class="alert alert-<?= $cls ?> rounded-0 mb-0">
            <i class="bi bi-<?= $cls === 'success' ? 'check-circle' : 'exclamation-circle' ?>-fill me-2"></i>
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="signup-body">
            <form method="POST" action="/STD/backend/register.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <!-- Personal Info -->
                <div class="section-title"><i class="bi bi-person me-1"></i>البيانات الشخصية</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"> رقم الهوية <span class="text-danger">*</span></label>
                        <input type="text" name="national_id" class="form-control" required
                               value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الجنس <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">— اختر —</option>
                            <option value="male"   <?= ($_POST['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>ذكر</option>
                            <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم الجوال</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <!-- Academic Info -->
                <div class="section-title"><i class="bi bi-mortarboard me-1"></i>البيانات الأكاديمية</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الرقم الجامعي</label>
                        <input type="text" name="student_number" class="form-control"
                               value="<?= htmlspecialchars($_POST['student_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الكلية <span class="text-danger">*</span></label>
                        <select name="college_id" id="collegeSelect" class="form-select" required
                                onchange="loadMajors(); loadAdvisors()">
                            <option value="">— اختر الكلية —</option>
                            <?php foreach ($colleges as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($_POST['college_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['title']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الدرجة العلمية <span class="text-danger">*</span></label>
                        <select name="degree_id" id="degreeSelect" class="form-select" required
                                onchange="loadMajors()">
                            <option value="">— اختر الدرجة —</option>
                            <?php foreach ($degrees as $d): ?>
                            <option value="<?= $d['id'] ?>"
                                <?= ($_POST['degree_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['title']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التخصص</label>
                        <select name="major_id" id="majorSelect" class="form-select" disabled>
                            <option value="">— اختر الكلية والدرجة أولاً —</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">المرشد الأكاديمي</label>
                        <select name="academic_advisor_id" id="advisorSelect" class="form-select">
                            <option value="">— اختر المرشد الأكاديمي —</option>
                            <?php foreach ($advisors as $adv): ?>
                            <option value="<?= $adv['id'] ?>"
                                data-college="<?= $adv['college_id'] ?? '' ?>"
                                <?= ($_POST['academic_advisor_id'] ?? '') == $adv['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($adv['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">اختر الكلية أولاً لتصفية المرشدين المتاحين</div>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="section-title"><i class="bi bi-shield-lock me-1"></i>بيانات الحساب</div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="passField" class="form-control" required minlength="8">
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePass('passField', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">8 أحرف على الأقل</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_confirm" id="passConfirm" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePass('passConfirm', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-person-check me-2"></i>إنشاء الحساب
                    </button>
                </div>

                <div class="text-center mt-3">
                    <span class="text-muted small">لديك حساب بالفعل؟</span>
                    <a href="/STD/login.php" class="small fw-bold">تسجيل الدخول</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- majors data embedded for JS cascade -->
<script>
const ALL_MAJORS = <?= json_encode(
    $pdo->query("SELECT id, title AS name, college_id, degree_id FROM majors WHERE status='active' ORDER BY title")
        ->fetchAll(PDO::FETCH_ASSOC)
) ?>;
const ALL_ADVISORS = <?= json_encode(
    array_map(fn($a) => ['id' => $a['id'], 'name' => $a['full_name'], 'college_id' => $a['college_id']], $advisors)
) ?>;

const savedMajor = <?= (int)($_POST['major_id'] ?? 0) ?>;

function loadMajors() {
    var cid = document.getElementById('collegeSelect').value;
    var did = document.getElementById('degreeSelect').value;
    var sel = document.getElementById('majorSelect');

    if (!cid && !did) {
        sel.innerHTML = '<option value="">— اختر الكلية والدرجة أولاً —</option>';
        sel.disabled = true;
        return;
    }

    var filtered = ALL_MAJORS.filter(function(m) {
        var matchC = !cid || String(m.college_id) === String(cid);
        var matchD = !did || String(m.degree_id)  === String(did);
        return matchC && matchD;
    });

    if (filtered.length === 0) {
        sel.innerHTML = '<option value="">لا توجد تخصصات لهذا الاختيار</option>';
        sel.disabled = true;
        return;
    }

    sel.disabled = false;
    sel.innerHTML = '<option value="">— اختر التخصص —</option>';
    filtered.forEach(function(m) {
        var opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = m.name;
        if (savedMajor && String(m.id) === String(savedMajor)) opt.selected = true;
        sel.appendChild(opt);
    });
}

function loadAdvisors() {
    var cid = document.getElementById('collegeSelect').value;
    var sel = document.getElementById('advisorSelect');
    var saved = <?= (int)($_POST['academic_advisor_id'] ?? 0) ?>;

    sel.innerHTML = '<option value="">— اختر المرشد الأكاديمي —</option>';
    ALL_ADVISORS.forEach(function(a) {
        if (cid && a.college_id && String(a.college_id) !== String(cid)) return;
        var opt = document.createElement('option');
        opt.value = a.id;
        opt.textContent = a.name;
        if (saved && String(a.id) === String(saved)) opt.selected = true;
        sel.appendChild(opt);
    });
}

function togglePass(id, btn) {
    var f = document.getElementById(id);
    if (f.type === 'password') {
        f.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        f.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

// Restore cascaded selects on page reload (validation fail)
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('collegeSelect').value ||
        document.getElementById('degreeSelect').value) {
        loadMajors();
    }
    loadAdvisors();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
