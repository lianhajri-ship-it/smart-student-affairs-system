<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) { redirectByRole(); }

$siteName = getSetting('site_name', 'نظام عمادة شؤون الطلاب');
$siteLogo = getSetting('site_logo', '');
$uniName  = getSetting('university_name', 'جامعة بيشة');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($uniName) ?> — نظام عمادة شؤون الطلاب</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Tajawal', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            margin: 0;
        }

        /* ── Navbar ───────────────────────────────────────── */
        .lp-nav {
            position: sticky;
            top: 0;
            z-index: 999;
            background: rgba(10, 40, 60, .96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,.08);
            padding: .7rem 0;
        }
        .lp-nav .brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: #fff;
            text-decoration: none;
        }
        .lp-nav .brand-logo {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #1a5f7a, #159895);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: #fff;
            overflow: hidden;
        }
        .lp-nav .brand-logo img { width: 100%; height: 100%; object-fit: cover; }
        .lp-nav .brand-name { font-weight: 800; font-size: 1.05rem; line-height: 1.2; }
        .lp-nav .brand-sub  { font-size: .72rem; opacity: .65; }
        .lp-nav .nav-links a {
            color: rgba(255,255,255,.78);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            padding: .35rem .85rem;
            border-radius: 8px;
            transition: background .2s, color .2s;
        }
        .lp-nav .nav-links a:hover { background: rgba(255,255,255,.1); color: #fff; }
        .lp-nav .btn-login {
            background: linear-gradient(135deg, #1a5f7a, #159895);
            color: #fff !important;
            border-radius: 10px;
            font-weight: 700;
            padding: .45rem 1.2rem;
            font-size: .88rem;
        }
        .lp-nav .btn-login:hover { opacity: .88; }

        /* ── Hero ─────────────────────────────────────────── */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: linear-gradient(150deg, #071e2c 0%, #0d3f52 40%, #1a5f7a 75%, #159895 100%);
        }
        .hero-particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            animation: float 8s infinite ease-in-out;
        }
        .particle:nth-child(1)  { width:380px;height:380px; top:-80px;  right:-60px;  animation-delay:0s;   }
        .particle:nth-child(2)  { width:260px;height:260px; bottom:-40px;left:-60px;  animation-delay:2s;   }
        .particle:nth-child(3)  { width:180px;height:180px; top:40%;    left:20%;     animation-delay:4s;   }
        .particle:nth-child(4)  { width:120px;height:120px; top:20%;    left:55%;     animation-delay:1s;   }
        .particle:nth-child(5)  { width:80px; height:80px;  bottom:20%; right:30%;    animation-delay:3s;   }
        @keyframes float {
            0%,100% { transform: translateY(0) scale(1); }
            50%      { transform: translateY(-20px) scale(1.04); }
        }
        .hero-content { position: relative; z-index: 2; }
        .hero-uni-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            color: #c8eaf0;
            border-radius: 50px;
            padding: .35rem 1rem;
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .hero-title {
            font-size: clamp(2rem, 5vw, 3.6rem);
            font-weight: 900;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 1rem;
        }
        .hero-title .accent { color: #4ecdc4; }
        .hero-subtitle {
            font-size: clamp(.95rem, 2vw, 1.2rem);
            color: rgba(255,255,255,.78);
            max-width: 560px;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .hero-actions .btn-hero-primary {
            background: linear-gradient(135deg, #159895, #0fd6d0);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: .8rem 2rem;
            font-size: 1rem;
            font-weight: 700;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 8px 24px rgba(21,152,149,.4);
        }
        .hero-actions .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(21,152,149,.5); color:#fff; }
        .hero-actions .btn-hero-outline {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,.35);
            border-radius: 14px;
            padding: .75rem 1.8rem;
            font-size: 1rem;
            font-weight: 600;
            transition: background .2s, border-color .2s;
        }
        .hero-actions .btn-hero-outline:hover { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.6); color:#fff; }
        .hero-visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-ring {
            width: 340px; height: 340px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,.1);
            display: flex; align-items: center; justify-content: center;
            position: relative;
            animation: rotate-slow 20s linear infinite;
        }
        .hero-ring::before {
            content: '';
            position: absolute;
            width: 90%; height: 90%;
            border-radius: 50%;
            border: 2px dashed rgba(78,205,196,.3);
        }
        @keyframes rotate-slow { to { transform: rotate(360deg); } }
        .hero-icon-center {
            width: 160px; height: 160px;
            background: linear-gradient(135deg, rgba(26,95,122,.8), rgba(21,152,149,.8));
            border-radius: 40px;
            display: flex; align-items: center; justify-content: center;
            font-size: 4rem; color: #fff;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            animation: float 5s ease-in-out infinite;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,.2);
        }
        .orbit-item {
            position: absolute;
            width: 56px; height: 56px;
            background: rgba(255,255,255,.12);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: #fff;
            border: 1px solid rgba(255,255,255,.2);
            animation: counter-rotate 20s linear infinite;
        }
        @keyframes counter-rotate { to { transform: rotate(-360deg); } }
        .orbit-1 { top: 10%; right: 50%; }
        .orbit-2 { bottom: 10%; right: 50%; }
        .orbit-3 { top: 50%; right: 5%; }
        .orbit-4 { top: 50%; left: 5%; }

        /* Hero stats strip */
        .hero-stats-strip {
            background: rgba(255,255,255,.08);
            border-top: 1px solid rgba(255,255,255,.1);
            padding: 1.2rem 0;
        }
        .hero-stats-strip .stat-item { text-align: center; color: #fff; }
        .hero-stats-strip .stat-num  { font-size: 1.8rem; font-weight: 900; color: #4ecdc4; }
        .hero-stats-strip .stat-lbl  { font-size: .78rem; opacity: .7; }

        /* ── Section commons ──────────────────────────────── */
        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: linear-gradient(135deg, #e8f4f8, #e0f5f5);
            color: #1a5f7a;
            border-radius: 50px;
            padding: .3rem .9rem;
            font-size: .78rem;
            font-weight: 700;
            margin-bottom: .75rem;
        }
        .section-title {
            font-size: clamp(1.6rem, 3.5vw, 2.4rem);
            font-weight: 800;
            color: #0d3f52;
            line-height: 1.3;
        }
        .section-sub {
            color: #64748b;
            font-size: 1rem;
            max-width: 580px;
            line-height: 1.7;
        }

        /* ── Features ─────────────────────────────────────── */
        #features { background: #fff; padding: 80px 0; }
        .feat-card {
            background: #fff;
            border: 1.5px solid #e2ecf0;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            transition: transform .25s, box-shadow .25s, border-color .25s;
            height: 100%;
        }
        .feat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(26,95,122,.1);
            border-color: #159895;
        }
        .feat-icon {
            width: 60px; height: 60px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 1rem;
        }
        .feat-title { font-size: 1.05rem; font-weight: 700; color: #0d3f52; margin-bottom: .4rem; }
        .feat-desc  { font-size: .85rem; color: #64748b; line-height: 1.65; }

        /* ── About University ─────────────────────────────── */
        #about { background: linear-gradient(135deg, #f0f7fa, #e8f4f8); padding: 80px 0; }
        .about-card {
            background: #fff;
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(26,95,122,.08);
            height: 100%;
        }
        .about-icon-wrap {
            width: 72px; height: 72px;
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.2rem;
        }
        .about-card h4 { font-size: 1.25rem; font-weight: 800; color: #0d3f52; margin-bottom: .6rem; }
        .about-card p  { font-size: .9rem; color: #5a7080; line-height: 1.8; margin: 0; }

        /* Uni hero block */
        .uni-showcase {
            background: linear-gradient(135deg, #0d3f52, #1a5f7a);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .uni-showcase::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
            top: -80px; left: -80px;
        }
        .uni-showcase h3 { font-size: 1.7rem; font-weight: 900; margin-bottom: .5rem; }
        .uni-showcase p  { opacity: .8; line-height: 1.8; font-size: .95rem; }
        .uni-info-item {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .8rem 0;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .uni-info-item:last-child { border-bottom: none; }
        .uni-info-icon {
            width: 38px; height: 38px;
            background: rgba(255,255,255,.1);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .uni-info-label  { font-size: .72rem; opacity: .65; }
        .uni-info-value  { font-size: .9rem; font-weight: 600; }

        /* ── Team ─────────────────────────────────────────── */
        #team { background: #fff; padding: 80px 0; }
        .team-card {
            background: linear-gradient(135deg, #f0f7fa, #e8f8f5);
            border-radius: 16px;
            padding: 1.2rem 1rem;
            text-align: center;
            transition: transform .2s, box-shadow .2s;
            border: 1.5px solid #d8eef4;
        }
        .team-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(26,95,122,.1); }
        .team-avatar {
            width: 60px; height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a5f7a, #159895);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: #fff;
            margin: 0 auto .8rem;
        }
        .team-name { font-size: .9rem; font-weight: 700; color: #0d3f52; }
        .team-role { font-size: .75rem; color: #64748b; }

        /* ── Footer ───────────────────────────────────────── */
        .lp-footer {
            background: #071e2c;
            padding: 3rem 0 1.5rem;
            color: rgba(255,255,255,.7);
        }
        .lp-footer .foot-brand { font-size: 1.1rem; font-weight: 800; color: #fff; margin-bottom: .4rem; }
        .lp-footer .foot-desc  { font-size: .82rem; opacity: .6; line-height: 1.7; max-width: 340px; }
        .lp-footer .foot-title { font-size: .82rem; font-weight: 700; color: #4ecdc4; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .75rem; }
        .lp-footer .foot-link  { display: block; font-size: .82rem; color: rgba(255,255,255,.55); text-decoration: none; padding: .2rem 0; transition: color .2s; }
        .lp-footer .foot-link:hover { color: #4ecdc4; }
        .lp-footer .foot-divider {
            border-top: 1px solid rgba(255,255,255,.08);
            padding-top: 1.5rem;
            margin-top: 2rem;
        }
        .lp-footer .copy-text { font-size: .78rem; color: rgba(255,255,255,.4); }
        .lp-footer .team-names { font-size: .72rem; color: rgba(255,255,255,.35); line-height: 1.9; }
        .lp-footer .dev-note   { font-size: .78rem; color: rgba(255,255,255,.45); }

        /* ── Responsive ───────────────────────────────────── */
        @media(max-width:768px) {
            .hero-ring { width: 220px; height: 220px; }
            .hero-icon-center { width: 110px; height: 110px; font-size: 2.5rem; }
            .orbit-item { width: 42px; height: 42px; font-size: 1rem; }
        }
        @media(max-width:576px) {
            .hero-visual { display: none; }
        }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════ NAVBAR -->
<nav class="lp-nav">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <!-- Brand -->
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

            <!-- Nav links (hidden on mobile) -->
            <div class="nav-links d-none d-md-flex align-items-center gap-1">
                <a href="#features">الخدمات</a>
                <a href="#about">عن العمادة</a>
                <a href="/STD/about.php">عن المنصة</a>
            </div>

            <!-- Actions -->
            <div class="d-flex align-items-center gap-2">
                <a href="/STD/login.php" class="nav-links">
                    <span class="btn-login d-inline-block">
                        <i class="bi bi-box-arrow-in-right me-1"></i>تسجيل الدخول
                    </span>
                </a>
                <a href="/STD/signup.php" class="d-none d-sm-inline-flex align-items-center btn btn-outline-light btn-sm" style="border-radius:10px;font-weight:600;">
                    <i class="bi bi-person-plus me-1"></i>إنشاء حساب
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════ HERO -->
<section class="hero">
    <div class="hero-particles">
        <div class="particle"></div><div class="particle"></div>
        <div class="particle"></div><div class="particle"></div><div class="particle"></div>
    </div>

    <div class="container py-5">
        <div class="row align-items-center g-5">
            <!-- Text -->
            <div class="col-lg-6 hero-content">
                <div class="hero-uni-badge">
                    <i class="bi bi-building-fill"></i>
                    <?= htmlspecialchars($uniName) ?>
                </div>
                <h1 class="hero-title">
                    نظام إدارة<br>
                    <span class="accent">شؤون الطلاب</span><br>
                    الذكي
                </h1>
                <p class="hero-subtitle">
                    منصة رقمية متكاملة تُقدِّم خدمات عمادة شؤون الطلاب في
                    <?= htmlspecialchars($uniName) ?> بكفاءة عالية، مع دعم الذكاء الاصطناعي
                    للإجابة على استفساراتكم فوراً.
                </p>
                <div class="hero-actions d-flex flex-wrap gap-3">
                    <a href="/STD/login.php" class="btn btn-hero-primary">
                        <i class="bi bi-box-arrow-in-right me-2"></i>ابدأ الآن
                    </a>
                    <a href="#features" class="btn btn-hero-outline">
                        <i class="bi bi-play-circle me-2"></i>استعرض الخدمات
                    </a>
                </div>
            </div>

            <!-- Visual -->
            <div class="col-lg-6 hero-visual d-flex justify-content-center">
                <div class="hero-ring">
                    <div class="hero-icon-center">
                        <?php if ($siteLogo): ?>
                            <img src="/STD/assets/uploads/<?= htmlspecialchars($siteLogo) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:30px;">
                        <?php else: ?>
                            <i class="bi bi-mortarboard-fill"></i>
                        <?php endif; ?>
                    </div>
                    <div class="orbit-item orbit-1"><i class="bi bi-inbox-fill"></i></div>
                    <div class="orbit-item orbit-2"><i class="bi bi-robot"></i></div>
                    <div class="orbit-item orbit-3"><i class="bi bi-grid-fill"></i></div>
                    <div class="orbit-item orbit-4"><i class="bi bi-journal-text"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats strip -->
    <div class="hero-stats-strip w-100 position-absolute bottom-0 start-0">
        <div class="container">
            <div class="row g-3">
                <?php
                $heroStats = [
                    [$pdo->query("SELECT COUNT(*) FROM services WHERE status='published'")->fetchColumn(), 'خدمة إلكترونية', 'bi-grid'],
                    [$pdo->query("SELECT COUNT(*) FROM users WHERE user_type_id=1")->fetchColumn(), 'طالب مسجل', 'bi-person-badge'],
                    [$pdo->query("SELECT COUNT(*) FROM chatbot_conversations")->fetchColumn(), 'محادثة ذكية', 'bi-robot'],
                    [$pdo->query("SELECT COUNT(*) FROM knowledge_base WHERE status='published'")->fetchColumn(), 'مقالة معرفية', 'bi-database'],
                ];
                foreach ($heroStats as [$num, $label, $icon]): ?>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-num"><?= number_format($num) ?>+</div>
                    <div class="stat-lbl"><i class="bi <?= $icon ?> me-1"></i><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════ FEATURES -->
<section id="features">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-tag"><i class="bi bi-stars"></i>ما يقدمه النظام</div>
            <h2 class="section-title">خدمات متكاملة بين يديك</h2>
            <p class="section-sub mx-auto mt-2">
                كل ما يحتاجه الطالب من خدمات عمادة شؤون الطلاب في مكان واحد، رقمياً وبكل سهولة.
            </p>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['bi-inbox-fill',          '#1a5f7a', '#e8f4f8', 'الطلبات الإلكترونية',     'قدِّم طلباتك الرسمية إلكترونياً وتابع حالتها لحظة بلحظة دون الحاجة للحضور الشخصي.'],
                ['bi-robot',               '#159895', '#e8f8f5', 'المساعد الذكي (AI)',       'شات بوت مدعوم بالذكاء الاصطناعي يُجيب على استفساراتك الطلابية فوراً على مدار الساعة.'],
                ['bi-grid-fill',           '#2980b9', '#ebf5fb', 'الخدمات الطلابية',        'استعرض جميع خدمات العمادة من إجازات ودعم ومنح وغيرها في دليل منظم وشامل.'],
                ['bi-file-earmark-text',   '#8e44ad', '#f5eef8', 'الأدلة والنماذج',         'تحميل الأدلة الإرشادية ونماذج الطلبات الرسمية المعتمدة مباشرةً من المنصة.'],
                ['bi-person-check-fill',   '#27ae60', '#eafaf1', 'التواصل مع المرشد',       'تواصل مع مرشدك الأكاديمي مباشرةً وتلقَّ الردود والمتابعة على طلباتك.'],
                ['bi-journal-text',        '#e67e22', '#fef9e7', 'محاضر الاجتماعات',        'متابعة توصيات اجتماعات لجنة شؤون الطلاب وحالة تنفيذها وصرفها.'],
            ];
            foreach ($features as [$icon, $color, $bg, $title, $desc]): ?>
            <div class="col-md-6 col-lg-4">
                <div class="feat-card">
                    <div class="feat-icon" style="background:<?= $bg ?>;color:<?= $color ?>;">
                        <i class="bi <?= $icon ?>"></i>
                    </div>
                    <div class="feat-title"><?= $title ?></div>
                    <div class="feat-desc"><?= $desc ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════ ABOUT -->
<section id="about">
    <div class="container">
        <div class="row g-5 align-items-start">
            <!-- Left: Uni showcase -->
            <div class="col-lg-5">
                <div class="uni-showcase">
                    <div class="section-tag" style="background:rgba(255,255,255,.12);color:#c8eaf0;"><i class="bi bi-building-fill"></i>جامعة بيشة</div>
                    <h3><?= htmlspecialchars($uniName) ?></h3>
                    <p>
                        جامعة حكومية سعودية تأسست عام 2006م في مدينة بيشة بمنطقة عسير،
                        وتضم عدداً من الكليات المتخصصة التي تخدم آلاف الطلاب والطالبات.
                    </p>
                    <div class="mt-3">
                        <div class="uni-info-item">
                            <div class="uni-info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <div class="uni-info-label">الموقع</div>
                                <div class="uni-info-value">مدينة بيشة — منطقة عسير، المملكة العربية السعودية</div>
                            </div>
                        </div>
                        <div class="uni-info-item">
                            <div class="uni-info-icon"><i class="bi bi-calendar3"></i></div>
                            <div>
                                <div class="uni-info-label">سنة التأسيس</div>
                                <div class="uni-info-value">1427هـ / 2006م</div>
                            </div>
                        </div>
                        <div class="uni-info-item">
                            <div class="uni-info-icon"><i class="bi bi-bank2"></i></div>
                            <div>
                                <div class="uni-info-label">الكليات</div>
                                <div class="uni-info-value">كليات علمية وإنسانية متنوعة تشمل الحاسبات والطب والهندسة وغيرها</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Dean + System cards -->
            <div class="col-lg-7">
                <div class="section-tag"><i class="bi bi-shield-check"></i>عمادة شؤون الطلاب</div>
                <h2 class="section-title mb-3">في خدمة الطالب دائماً</h2>
                <p class="section-sub mb-4">
                    تعمل عمادة شؤون الطلاب على تقديم خدمات متميزة تُعزِّز التجربة الأكاديمية
                    وتدعم احتياجات الطلاب بكفاءة ورعاية متكاملة.
                </p>
                <div class="row g-3">
                    <?php
                    $deanCards = [
                        ['bi-heart-fill',        '#e74c3c','#fdedec', 'دعم الطلاب',         'تقديم المساعدات المالية والاجتماعية والنفسية لكل من يحتاجها.'],
                        ['bi-mortarboard-fill',  '#2980b9','#ebf5fb', 'التوجيه الأكاديمي',  'مرشدون أكاديميون متخصصون لمتابعة المسار الدراسي لكل طالب.'],
                        ['bi-shield-fill',       '#27ae60','#eafaf1', 'حقوق الطالب',        'ضمان حقوق الطلاب وتقديم بيئة تعليمية آمنة ومحفِّزة.'],
                        ['bi-activity',          '#8e44ad','#f5eef8', 'الأنشطة الطلابية',   'دعم الأنشطة الثقافية والرياضية والاجتماعية داخل الحرم الجامعي.'],
                    ];
                    foreach ($deanCards as [$icon, $color, $bg, $title, $desc]): ?>
                    <div class="col-sm-6">
                        <div class="about-card">
                            <div class="about-icon-wrap" style="background:<?= $bg ?>;color:<?= $color ?>;">
                                <i class="bi <?= $icon ?>"></i>
                            </div>
                            <h4><?= $title ?></h4>
                            <p><?= $desc ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════ TEAM -->
<section id="team">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-tag"><i class="bi bi-people-fill"></i>فريق التطوير</div>
            <h2 class="section-title">مشروع التخرج</h2>
            <p class="section-sub mx-auto mt-2">
                طالبات كلية الحاسبات وتقنية المعلومات — تخصص الذكاء الاصطناعي
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
</section>

<!-- ══════════════════════════════════════════════════════════ CTA BANNER -->
<section style="background:linear-gradient(135deg,#0d3f52,#1a5f7a);padding:60px 0;text-align:center;">
    <div class="container">
        <h2 style="color:#fff;font-size:2rem;font-weight:900;margin-bottom:.75rem;">
            هل أنت طالب في <?= htmlspecialchars($uniName) ?>؟
        </h2>
        <p style="color:rgba(255,255,255,.75);font-size:1rem;margin-bottom:2rem;">
            سجّل الدخول الآن واستفد من جميع خدمات عمادة شؤون الطلاب رقمياً.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="/STD/login.php" class="btn btn-hero-primary" style="background:linear-gradient(135deg,#159895,#0fd6d0);color:#fff;border:none;border-radius:14px;padding:.8rem 2.2rem;font-weight:700;font-size:1rem;box-shadow:0 8px 24px rgba(21,152,149,.4);">
                <i class="bi bi-box-arrow-in-right me-2"></i>تسجيل الدخول
            </a>
            <a href="/STD/signup.php" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:2px solid rgba(255,255,255,.35);border-radius:14px;padding:.75rem 2rem;font-weight:600;font-size:1rem;">
                <i class="bi bi-person-plus me-2"></i>إنشاء حساب جديد
            </a>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════ FOOTER -->
<footer class="lp-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand col -->
            <div class="col-md-5">
                <div class="foot-brand"><?= htmlspecialchars($uniName) ?></div>
                <div class="foot-desc">
                    عمادة شؤون الطلاب — منصة رقمية لتقديم خدمات الطلاب بكفاءة
                    مع دعم الذكاء الاصطناعي.
                </div>
            </div>
            <!-- Links -->
            <div class="col-6 col-md-3">
                <div class="foot-title">روابط سريعة</div>
                <a href="/STD/login.php"  class="foot-link"><i class="bi bi-box-arrow-in-right me-2"></i>تسجيل الدخول</a>
                <a href="/STD/signup.php" class="foot-link"><i class="bi bi-person-plus me-2"></i>إنشاء حساب</a>
                <a href="#features"       class="foot-link"><i class="bi bi-grid me-2"></i>الخدمات</a>
                <a href="#about"          class="foot-link"><i class="bi bi-building me-2"></i>عن العمادة</a>
                <a href="/STD/about.php"  class="foot-link"><i class="bi bi-info-circle me-2"></i>عن المنصة</a>
            </div>
            <!-- System info -->
            <div class="col-6 col-md-4">
                <div class="foot-title">عن النظام</div>
                <div class="foot-link" style="cursor:default;line-height:1.8;">
                    تم تطوير هذا النظام بواسطة مشروع التخرج للطالبات في كلية الحاسبات
                    وتقنية المعلومات — تخصص الذكاء الاصطناعي
                </div>
            </div>
        </div>

        <!-- Divider + copyright -->
        <div class="foot-divider">
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <div class="copy-text">
                        &copy; <?= date('Y') ?> — جميع الحقوق محفوظة لـ <?= htmlspecialchars($uniName) ?>
                    </div>
                    <div class="dev-note mt-1">
                        تم التطوير بواسطة طالبات الذكاء الاصطناعي — كلية الحاسبات وتقنية المعلومات
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="team-names">
                        لينا مشرع الهاجري · نوال فلاح القحطاني · لارا وهيطان · ياسمين حمد · نوت عبدالله<br>
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
