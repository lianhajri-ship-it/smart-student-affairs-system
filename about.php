<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$siteName = getSetting('site_name', 'نظام عمادة شؤون الطلاب');
$siteLogo = getSetting('site_logo', '');
$uniName  = getSetting('university_name', 'جامعة بيشة');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عن المنصة | <?= htmlspecialchars($uniName) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tajawal', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; }

        /* Navbar — same as landing */
        .lp-nav {
            position: sticky; top: 0; z-index: 999;
            background: rgba(10,40,60,.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,.08);
            padding: .7rem 0;
        }
        .lp-nav .brand { display: flex; align-items: center; gap: .75rem; color: #fff; text-decoration: none; }
        .lp-nav .brand-logo {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #1a5f7a, #159895);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: #fff; overflow: hidden;
        }
        .lp-nav .brand-logo img { width:100%; height:100%; object-fit:cover; }
        .lp-nav .brand-name { font-weight: 800; font-size: 1.05rem; line-height: 1.2; }
        .lp-nav .brand-sub  { font-size: .72rem; opacity: .65; }
        .lp-nav a.nav-l { color: rgba(255,255,255,.75); text-decoration:none; font-size:.9rem; font-weight:500; padding:.35rem .85rem; border-radius:8px; transition:.2s; }
        .lp-nav a.nav-l:hover { background:rgba(255,255,255,.1); color:#fff; }
        .lp-nav .btn-login { background:linear-gradient(135deg,#1a5f7a,#159895); color:#fff!important; border-radius:10px; font-weight:700; padding:.45rem 1.2rem; font-size:.88rem; }

        /* Page hero */
        .page-hero {
            background: linear-gradient(150deg, #071e2c 0%, #0d3f52 50%, #1a5f7a 100%);
            padding: 80px 0 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .page-hero::before {
            content:'';
            position:absolute;
            width:400px;height:400px;border-radius:50%;
            background:rgba(255,255,255,.04);
            top:-100px;right:-80px;
        }
        .page-hero::after {
            content:'';
            position:absolute;
            width:300px;height:300px;border-radius:50%;
            background:rgba(255,255,255,.03);
            bottom:-80px;left:-60px;
        }
        .page-hero .badge-tag {
            display:inline-flex;align-items:center;gap:.4rem;
            background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
            color:#c8eaf0;border-radius:50px;padding:.3rem .9rem;font-size:.8rem;font-weight:600;
            margin-bottom:1rem;
        }
        .page-hero h1 { font-size:clamp(1.8rem,4vw,3rem); font-weight:900; color:#fff; margin-bottom:.75rem; }
        .page-hero p  { color:rgba(255,255,255,.72); font-size:1rem; max-width:520px; margin:0 auto; line-height:1.7; }

        /* Sections */
        .section-tag {
            display:inline-flex;align-items:center;gap:.4rem;
            background:linear-gradient(135deg,#e8f4f8,#e0f5f5);
            color:#1a5f7a;border-radius:50px;padding:.3rem .9rem;
            font-size:.78rem;font-weight:700;margin-bottom:.75rem;
        }
        .section-title { font-size:clamp(1.5rem,3vw,2.2rem); font-weight:800; color:#0d3f52; }
        .section-sub   { color:#64748b; line-height:1.75; }

        .content-card {
            background:#fff;border-radius:20px;
            box-shadow:0 4px 24px rgba(26,95,122,.07);
            border:1.5px solid #e2ecf0;
            padding:2rem;
        }

        /* Goal cards */
        .goal-card {
            display:flex;gap:1rem;align-items:flex-start;
            background:#f8fbfd;border-radius:16px;padding:1.25rem;
            border:1.5px solid #e2ecf0;
            transition:transform .2s,box-shadow .2s;
        }
        .goal-card:hover { transform:translateY(-3px); box-shadow:0 10px 30px rgba(26,95,122,.1); }
        .goal-num {
            width:42px;height:42px;border-radius:12px;flex-shrink:0;
            background:linear-gradient(135deg,#1a5f7a,#159895);
            color:#fff;display:flex;align-items:center;justify-content:center;
            font-weight:900;font-size:1.1rem;
        }
        .goal-card h6 { font-weight:700;color:#0d3f52;margin-bottom:.3rem; }
        .goal-card p  { font-size:.85rem;color:#64748b;margin:0;line-height:1.65; }

        /* Team */
        .team-card {
            background:linear-gradient(135deg,#f0f7fa,#e8f8f5);
            border-radius:16px;padding:1.2rem 1rem;text-align:center;
            transition:transform .2s,box-shadow .2s;
            border:1.5px solid #d8eef4;
        }
        .team-card:hover { transform:translateY(-4px); box-shadow:0 12px 30px rgba(26,95,122,.1); }
        .team-avatar {
            width:60px;height:60px;border-radius:50%;
            background:linear-gradient(135deg,#1a5f7a,#159895);
            display:flex;align-items:center;justify-content:center;
            font-size:1.5rem;color:#fff;margin:0 auto .8rem;
        }
        .team-name { font-size:.9rem;font-weight:700;color:#0d3f52; }
        .team-role { font-size:.75rem;color:#64748b; }

        /* Tech stack */
        .tech-badge {
            display:inline-flex;align-items:center;gap:.4rem;
            background:#fff;border:1.5px solid #e2ecf0;
            border-radius:10px;padding:.5rem 1rem;
            font-size:.85rem;font-weight:600;color:#1a5f7a;
            margin:.3rem;
        }

        /* Footer */
        .lp-footer { background:#071e2c; padding:3rem 0 1.5rem; color:rgba(255,255,255,.7); }
        .lp-footer .foot-brand { font-size:1.1rem;font-weight:800;color:#fff;margin-bottom:.4rem; }
        .lp-footer .foot-desc  { font-size:.82rem;opacity:.6;line-height:1.7;max-width:340px; }
        .lp-footer .foot-title { font-size:.82rem;font-weight:700;color:#4ecdc4;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.75rem; }
        .lp-footer .foot-link  { display:block;font-size:.82rem;color:rgba(255,255,255,.55);text-decoration:none;padding:.2rem 0;transition:color .2s; }
        .lp-footer .foot-link:hover { color:#4ecdc4; }
        .lp-footer .foot-divider { border-top:1px solid rgba(255,255,255,.08);padding-top:1.5rem;margin-top:2rem; }
        .lp-footer .copy-text  { font-size:.78rem;color:rgba(255,255,255,.4); }
        .lp-footer .team-names { font-size:.72rem;color:rgba(255,255,255,.35);line-height:1.9; }
        .lp-footer .dev-note   { font-size:.78rem;color:rgba(255,255,255,.45); }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="lp-nav">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="/STD/index.php" class="brand">
                <div class="brand-logo">
                    <?php if ($siteLogo): ?>
                        <img src="/STD/assets/uploads/<?= htmlspecialchars($siteLogo) ?>" alt="">
                    <?php else: ?>
                        <i class="bi bi-mortarboard-fill"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="brand-name"><?= htmlspecialchars($uniName) ?></div>
                    <div class="brand-sub">عمادة شؤون الطلاب</div>
                </div>
            </a>
            <div class="d-none d-md-flex align-items-center gap-1">
                <a href="/STD/index.php#features" class="nav-l">الخدمات</a>
                <a href="/STD/index.php#about"    class="nav-l">عن العمادة</a>
                <a href="/STD/about.php"          class="nav-l" style="color:#4ecdc4;">عن المنصة</a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="/STD/login.php" class="btn-login d-inline-block text-decoration-none">
                    <i class="bi bi-box-arrow-in-right me-1"></i>تسجيل الدخول
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container position-relative" style="z-index:2;">
        <div class="badge-tag"><i class="bi bi-info-circle-fill"></i>عن المنصة</div>
        <h1>نظام إدارة شؤون الطلاب الذكي</h1>
        <p>
            منصة أكاديمية رقمية طورتها طالبات الذكاء الاصطناعي في <?= htmlspecialchars($uniName) ?>
            لخدمة مجتمع الجامعة وتيسير خدمات عمادة شؤون الطلاب.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">

        <!-- About System -->
        <div class="col-12">
            <div class="content-card">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <div class="section-tag"><i class="bi bi-info-circle"></i>عن النظام</div>
                        <h2 class="section-title mb-3">ما هو هذا النظام؟</h2>
                        <p class="section-sub mb-3">
                            نظام <strong>إدارة شؤون الطلاب الذكي</strong> هو منصة رقمية متكاملة تربط الطالب بعمادة
                            شؤون الطلاب في <?= htmlspecialchars($uniName) ?> بشكل إلكتروني سهل وسريع.
                        </p>
                        <p class="section-sub mb-3">
                            يُتيح النظام تقديم الطلبات الرسمية إلكترونياً، ومتابعة حالتها في الوقت الفعلي،
                            والتواصل مع المرشد الأكاديمي، والاطلاع على الخدمات والأدلة والنماذج المتاحة.
                        </p>
                        <p class="section-sub">
                            يتميز النظام بمساعد ذكاء اصطناعي متقدم يُجيب على استفسارات الطلاب فوراً
                            استناداً إلى قاعدة معرفية شاملة بخدمات العمادة.
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <div class="p-4 rounded-4 text-center" style="background:linear-gradient(135deg,#0d3f52,#1a5f7a);">
                            <i class="bi bi-robot" style="font-size:5rem;color:#4ecdc4;display:block;margin-bottom:1rem;"></i>
                            <div style="color:#fff;font-weight:800;font-size:1.2rem;">مساعد ذكي على مدار الساعة</div>
                            <div style="color:rgba(255,255,255,.65);font-size:.85rem;margin-top:.5rem;">يُجيب على أسئلتك في ثوانٍ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- University -->
        <div class="col-lg-6">
            <div class="content-card h-100">
                <div class="section-tag"><i class="bi bi-building-fill"></i>الجامعة</div>
                <h3 class="section-title mb-3"><?= htmlspecialchars($uniName) ?></h3>
                <p class="section-sub mb-3">
                    جامعة حكومية سعودية تقع في مدينة بيشة بمنطقة عسير، تأسست عام 1427هـ / 2006م
                    لتُسهم في دعم مسيرة التعليم العالي في المملكة العربية السعودية.
                </p>
                <p class="section-sub mb-3">
                    تضم الجامعة كليات متنوعة تشمل العلوم الطبية والهندسية والإنسانية والحوسبة،
                    وتسعى دائماً نحو التميز الأكاديمي وخدمة المجتمع.
                </p>
                <div class="d-flex flex-column gap-2 mt-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span class="small">بيئة تعليمية محفزة ومتطورة</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span class="small">كوادر أكاديمية متخصصة</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span class="small">خدمات طلابية شاملة ومتنوعة</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span class="small">رؤية 2030 — دعم التحول الرقمي</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dean of Students -->
        <div class="col-lg-6">
            <div class="content-card h-100">
                <div class="section-tag"><i class="bi bi-shield-check"></i>العمادة</div>
                <h3 class="section-title mb-3">عمادة شؤون الطلاب</h3>
                <p class="section-sub mb-3">
                    تُعدّ عمادة شؤون الطلاب الجهة الرئيسية المعنية بمتابعة شؤون الطلاب
                    والطالبات خارج الإطار الأكاديمي وتشمل مهامها:
                </p>
                <div class="d-flex flex-column gap-2">
                    <?php
                    $deanMissions = [
                        ['bi-cash-stack',        'تقديم المساعدات المالية والإعانات للطلاب المحتاجين'],
                        ['bi-person-check',      'الإشراف على برامج الإرشاد الأكاديمي'],
                        ['bi-trophy',            'دعم الأنشطة الطلابية والمسابقات والفعاليات'],
                        ['bi-heart-pulse',       'تقديم الخدمات الصحية والنفسية للطلاب'],
                        ['bi-house-heart',       'الإشراف على السكن الجامعي وخدماته'],
                        ['bi-award',             'رعاية الطلاب المتميزين وتكريمهم'],
                    ];
                    foreach ($deanMissions as [$icon, $text]): ?>
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light">
                        <div style="width:34px;height:34px;background:linear-gradient(135deg,#e8f4f8,#e0f5f5);color:#1a5f7a;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi <?= $icon ?>"></i>
                        </div>
                        <span class="small"><?= $text ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Goals -->
        <div class="col-12">
            <div class="text-center mb-4">
                <div class="section-tag"><i class="bi bi-bullseye"></i>أهداف النظام</div>
                <h2 class="section-title">لماذا طُوِّر هذا النظام؟</h2>
            </div>
            <div class="row g-3">
                <?php
                $goals = [
                    ['تحويل الخدمات الورقية إلى إلكترونية',    'إلغاء الحاجة للمراجعة الشخصية وتقديم الطلبات عبر المنصة الرقمية في أي وقت ومن أي مكان.'],
                    ['توظيف الذكاء الاصطناعي في الخدمة',       'بناء مساعد ذكي يعتمد على قاعدة معرفية شاملة للإجابة الفورية على استفسارات الطلاب.'],
                    ['تعزيز التواصل بين الطالب والمرشد',        'إتاحة قناة تواصل رسمية ومنظمة بين الطالب ومرشده الأكاديمي لمتابعة طلباته.'],
                    ['شفافية ومتابعة لحظية',                    'إمكانية متابعة حالة الطلبات في الوقت الفعلي مع إشعارات تلقائية عند التحديث.'],
                    ['رقمنة محاضر اللجان',                      'إدارة توصيات اجتماعات العمادة رقمياً وتتبع حالة التنفيذ والصرف لكل توصية.'],
                    ['دعم قرار الإدارة بالبيانات',              'لوحات تحليلية وتقارير شاملة تُعين إدارة العمادة على اتخاذ قرارات مبنية على البيانات.'],
                ];
                foreach ($goals as $i => [$title, $desc]): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="goal-card">
                        <div class="goal-num"><?= $i + 1 ?></div>
                        <div>
                            <h6><?= $title ?></h6>
                            <p><?= $desc ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tech Stack -->
        <div class="col-12">
            <div class="content-card text-center">
                <div class="section-tag mx-auto"><i class="bi bi-cpu-fill"></i>التقنيات المستخدمة</div>
                <h2 class="section-title mb-4">بُني بأحدث التقنيات</h2>
                <div>
                    <?php
                    $techs = [
                        ['bi-filetype-php',    'PHP 8',         'خادم التطبيق'],
                        ['bi-database-fill',   'MySQL',         'قاعدة البيانات'],
                        ['bi-grid-1x2',        'Bootstrap 5',   'واجهة المستخدم'],
                        ['bi-robot',           'Gemini AI',     'الذكاء الاصطناعي'],
                        ['bi-search',          'Vector Search', 'البحث الدلالي'],
                        ['bi-lock-fill',       'PDO / CSRF',    'الأمان'],
                    ];
                    foreach ($techs as [$icon, $name, $desc]): ?>
                    <div class="tech-badge">
                        <i class="bi <?= $icon ?> text-primary"></i>
                        <span><?= $name ?> <span style="font-weight:400;color:#64748b;font-size:.78rem;">— <?= $desc ?></span></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Team -->
        <div class="col-12">
            <div class="text-center mb-4">
                <div class="section-tag"><i class="bi bi-people-fill"></i>فريق التطوير</div>
                <h2 class="section-title">الطالبات المطورات</h2>
                <p class="section-sub mx-auto mt-2">
                    مشروع تخرج — كلية الحاسبات وتقنية المعلومات، تخصص الذكاء الاصطناعي
                    <br><?= htmlspecialchars($uniName) ?>
                </p>
            </div>
            <div class="row g-3 justify-content-center">
                <?php
                $team = [
                    'لينا مشرع الهاجري','نوال فلاح القحطاني','لارا وهيطان','ياسمين حمد','نوت عبدالله',
                    'ريما سعد القرني','جود عبدالله القرني','أفنان محمد القرني','رباب ظفير الشمراني','منار سعد الشهراني',
                ];
                foreach ($team as $name): ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="team-card">
                        <div class="team-avatar"><i class="bi bi-person-fill"></i></div>
                        <div class="team-name"><?= htmlspecialchars($name) ?></div>
                        <div class="team-role">طالبة — ذكاء اصطناعي</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<!-- Footer -->
<footer class="lp-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="foot-brand"><?= htmlspecialchars($uniName) ?></div>
                <div class="foot-desc">عمادة شؤون الطلاب — منصة رقمية لتقديم خدمات الطلاب بكفاءة مع دعم الذكاء الاصطناعي.</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="foot-title">روابط سريعة</div>
                <a href="/STD/index.php"  class="foot-link"><i class="bi bi-house me-2"></i>الرئيسية</a>
                <a href="/STD/login.php"  class="foot-link"><i class="bi bi-box-arrow-in-right me-2"></i>تسجيل الدخول</a>
                <a href="/STD/signup.php" class="foot-link"><i class="bi bi-person-plus me-2"></i>إنشاء حساب</a>
            </div>
            <div class="col-6 col-md-4">
                <div class="foot-title">عن النظام</div>
                <div class="foot-link" style="cursor:default;line-height:1.8;">
                    مشروع تخرج — كلية الحاسبات وتقنية المعلومات، تخصص الذكاء الاصطناعي
                </div>
            </div>
        </div>
        <div class="foot-divider">
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <div class="copy-text">&copy; <?= date('Y') ?> — جميع الحقوق محفوظة لـ <?= htmlspecialchars($uniName) ?></div>
                    <div class="dev-note mt-1">تم التطوير بواسطة طالبات الذكاء الاصطناعي — كلية الحاسبات وتقنية المعلومات</div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="team-names">
                        لينا مشرع الهاجري · نوال فلاح · لارا وهيطان · ياسمين حمد · نوت عبدالله<br>
                        ريما سعد القرني · جود عبدالله القرني · أفنان محمد القرني · رباب ظفير الشمراني · منار سعد الشهراني
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
