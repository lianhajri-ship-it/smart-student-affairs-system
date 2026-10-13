<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// إذا كان المستخدم مسجلاً دخوله، وجهه
if (isLoggedIn()) {
    redirectByRole();
}

$siteName = getSetting('site_name', 'نظام شؤون الطلاب');
$uniName  = getSetting('university_name', 'الجامعة');
$siteLogo = getSetting('site_logo', '');
$error    = '';
$flash    = getFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | <?= htmlspecialchars($siteName) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        .login-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,.3);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #0d3f52, #1a5f7a);
            padding: 2.5rem 2rem;
            text-align: center;
            color: #fff;
        }
        .login-logo {
            width: 160px; height: 160px;
            background: rgba(255,255,255,.15);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
        }

        .login-logo img{
        width:130px !important;;
        height:130px !important;
        border-radius: 20px;
        }
        .login-title { font-size: 1.3rem; font-weight: 800; margin-bottom: .25rem; }
        .login-sub { font-size: .85rem; opacity: .8; }
        .login-body { padding: 2rem; }
        .login-tabs-wrap {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 1.5rem;
        }
        .login-tabs {
            flex-wrap: nowrap !important;
        }
        .login-tabs .nav-item {
            flex: 1;
        }
        .login-tabs .nav-link {
            border-radius: 8px !important;
            font-weight: 600;
            color: #6c757d;
            border: none !important;
            padding: .5rem .5rem;
            width: 100%;
            text-align: center;
            font-size: .82rem;
            white-space: nowrap;
        }
        .login-tabs .nav-link.active {
            background: linear-gradient(135deg, #1a5f7a, #159895) !important;
            color: #fff !important;
            box-shadow: 0 2px 8px rgba(26,95,122,.3);
        }
        .bg-shapes { position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
        }
        .shape-1 { width: 400px; height: 400px; top: -100px; right: -80px; }
        .shape-2 { width: 300px; height: 300px; bottom: -60px; left: -80px; }
        .shape-3 { width: 200px; height: 200px; top: 40%; left: 40%; }
        .login-card { position: relative; z-index: 1; }
    </style>
</head>
<body>
<div class="bg-shapes">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
</div>

<div class="login-card">
    <!-- Header -->
    <div class="login-header">
        <div class="login-logo">
            <?php if ($siteLogo): ?>
                <img src="/STD/assets/uploads/<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" style="width:60px;height:60px;object-fit:contain;">
            <?php else: ?>
                <i class="bi bi-mortarboard-fill"></i>
            <?php endif; ?>
        </div>
        <div class="login-title"><?= htmlspecialchars($siteName) ?></div>
        <div class="login-sub"><?= htmlspecialchars($uniName) ?></div>
    </div>

    <!-- Body -->
    <div class="login-body">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="login-tabs-wrap">
        <ul class="nav nav-pills login-tabs" id="loginTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#studentTab">
                    <i class="bi bi-person-badge me-1"></i>طالب
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#advisorTab">
                    <i class="bi bi-person-check me-1"></i>مرشد أكاديمي
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#adminTab">
                    <i class="bi bi-person-gear me-1"></i>إداري / مدير
                </button>
            </li>
        </ul>
        </div>

        <div class="tab-content">
            <!-- Student Login -->
            <div class="tab-pane fade show active" id="studentTab">
                <form method="POST" action="/STD/backend/login.php">
                    <input type="hidden" name="user_type" value="student">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope me-1"></i>البريد الإلكتروني </label>
                        <input type="text" name="identifier" class="form-control form-control-lg"
                               placeholder="أدخل بريدك الإلكتروني أو رقم طالب" required autocomplete="username">
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-lock me-1"></i>كلمة المرور</label>
                        <div class="input-group">
                            <input type="password" name="password" id="studentPass" class="form-control form-control-lg"
                                   placeholder="أدخل كلمة المرور" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('studentPass', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>تسجيل الدخول
                    </button>
                </form>
            </div>

            <!-- Advisor Login -->
            <div class="tab-pane fade" id="advisorTab">
                <form method="POST" action="/STD/backend/login.php">
                    <input type="hidden" name="user_type" value="admin">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope me-1"></i>البريد الإلكتروني</label>
                        <input type="email" name="identifier" class="form-control form-control-lg"
                               placeholder="أدخل بريدك الإلكتروني" required autocomplete="username">
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-lock me-1"></i>كلمة المرور</label>
                        <div class="input-group">
                            <input type="password" name="password" id="advisorPass" class="form-control form-control-lg"
                                   placeholder="أدخل كلمة المرور" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('advisorPass', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>تسجيل الدخول
                    </button>
                </form>
            </div>

            <!-- Admin Login -->
            <div class="tab-pane fade" id="adminTab">
                <form method="POST" action="/STD/backend/login.php">
                    <input type="hidden" name="user_type" value="admin">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope me-1"></i>البريد الإلكتروني</label>
                        <input type="email" name="identifier" class="form-control form-control-lg"
                               placeholder="أدخل بريدك الإلكتروني" required autocomplete="username">
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-lock me-1"></i>كلمة المرور</label>
                        <div class="input-group">
                            <input type="password" name="password" id="adminPass" class="form-control form-control-lg"
                                   placeholder="أدخل كلمة المرور" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('adminPass', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>تسجيل الدخول
                    </button>
                </form>
            </div>
        </div>


        <!-- Signup link -->
        <div class="text-center mt-3 pb-1">
            <span class="text-muted small">طالب جديد؟</span>
            <a href="/STD/signup.php" class="small fw-bold">إنشاء حساب</a>
        </div>
        <div class="text-center mt-2 pb-2">
            <a href="/STD/index.php" class="small text-muted"><i class="bi bi-house me-1"></i>العودة للرئيسية</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
