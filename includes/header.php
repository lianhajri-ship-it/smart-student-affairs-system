<?php
if (!isset($pdo)) { require_once __DIR__ . '/db.php'; }
if (!isset($_SESSION)) { session_start(); }

$siteName = getSetting('site_name', 'نظام شؤون الطلاب');
$siteLogo = getSetting('site_logo', '');
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . $siteName : $siteName;
$userType  = $_SESSION['user_type'] ?? '';
$userId    = (int)($_SESSION['user_id'] ?? 0);
$fullName  = $_SESSION['full_name'] ?? '';
$unreadNotif = $userId ? getUnreadNotifications($pdo, $userId) : 0;

$prefix = '/STD';

// Dashboard link per role
$dashLink = match($userType) {
    'student'          => "$prefix/student/dashboard.php",
    'admin'            => "$prefix/admin/dashboard.php",
    'manager'          => "$prefix/manager/dashboard.php",
    'academic_advisor' => "$prefix/advisor/dashboard.php",
    default            => "$prefix/login.php",
};

$hasSidebar = in_array($userType, ['admin', 'manager', 'student', 'academic_advisor']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   <!-- Google Fonts (Arabic) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $prefix ?>/assets/css/style.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/STD/assets/css/style.css') ?>">

    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body class="<?= $userType ?>-layout">

<!-- ══════════════════════════════════════════
     Top Navbar (logo + actions only)
═══════════════════════════════════════════ -->
<nav class="std-navbar navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid px-3 gap-2">

        <!-- Hamburger (mobile — shown for admin/manager) -->
        <?php if ($hasSidebar): ?>
        <button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()" aria-label="القائمة">
            <i class="bi bi-list"></i>
        </button>
        <?php endif; ?>

        <!-- Brand -->
        <a class="d-flex align-items-center gap-2 text-decoration-none" href="<?= $dashLink ?>">
            <div class="brand-icon">
                <?php if ($siteLogo): ?>
                    <img src="/STD/assets/uploads/<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" style="width:36px;height:36px;object-fit:contain;border-radius:6px;">
                <?php else: ?>
                    <i class="bi bi-mortarboard-fill"></i>
                <?php endif; ?>
            </div>
            <span class="brand-title d-none d-sm-block"><?= htmlspecialchars($siteName) ?></span>
        </a>

        <!-- Student quick-nav links (desktop only) -->
        <?php if ($userType === 'student'): ?>
        <ul class="navbar-nav flex-row gap-1 mx-auto d-none d-lg-flex">
            <?php
            $quickNav = [
                ['/student/services', 'bi-grid',            'الخدمات'],
                ['/student/guides',   'bi-book',            'الأدلة والنماذج'],
                ['/student/faq',      'bi-question-circle', 'الأسئلة الشائعة'],
            ];
            foreach ($quickNav as [$path, $icon, $label]):
                $active = strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
            ?>
            <li class="nav-item">
                <a class="nav-link nav-pill <?= $active ?>" href="<?= $prefix ?><?= $path ?>.php">
                    <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <span class="flex-grow-1"></span>
        <?php endif; ?>

        <!-- Right Actions -->
        <?php if ($userId > 0): ?>
        <ul class="navbar-nav flex-row align-items-center gap-2">

            <!-- Notifications -->
            <li class="nav-item dropdown">
                <a class="nav-link nav-icon-btn position-relative" href="#" data-bs-toggle="dropdown" aria-label="الإشعارات">
                    <i class="bi bi-bell-fill"></i>
                    <?php if ($unreadNotif > 0): ?>
                        <span class="notif-badge"><?= $unreadNotif ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0">
                    <div class="notif-header">
                        <span>الإشعارات</span>
                        <?php if ($unreadNotif > 0): ?>
                            <a href="<?= $prefix ?>/backend/mark_notifications_read.php" class="text-white small">تحديد الكل كمقروء</a>
                        <?php endif; ?>
                    </div>
                    <div class="notif-list">
                        <?php
                        $notifications = getLatestNotifications($pdo, $userId);
                        if (empty($notifications)):
                        ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-bell-slash fs-4 d-block mb-1"></i>
                                لا توجد إشعارات
                            </div>
                        <?php else: foreach ($notifications as $notif): ?>
                            <a href="<?= !empty($notif['link']) ? $notif['link'] : '#' ?>"
                               class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                                <div class="notif-icon"><i class="bi bi-info-circle-fill"></i></div>
                                <div class="notif-content">
                                    <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                                    <div class="notif-time"><?= formatDateTime($notif['created_at']) ?></div>
                                </div>
                            </a>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </li>

            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link user-menu-btn d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                    <img src="<?= getUserAvatar($_SESSION['profile_image'] ?? '') ?>" alt="" class="user-avatar-sm">
                    <span class="d-none d-lg-block"><?= htmlspecialchars(explode(' ', $fullName)[0] ?? $fullName) ?></span>
                    <i class="bi bi-chevron-down small d-none d-lg-inline"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><h6 class="dropdown-header"><?= htmlspecialchars($fullName) ?></h6></li>
                    <?php if ($userType === 'student'): ?>
                        <li><a class="dropdown-item" href="<?= $prefix ?>/student/profile.php"><i class="bi bi-person me-2"></i>الملف الشخصي</a></li>
                    <?php elseif ($userType === 'admin'): ?>
                        <li><a class="dropdown-item" href="<?= $prefix ?>/admin/profile.php"><i class="bi bi-person me-2"></i>الملف الشخصي</a></li>
                    <?php elseif ($userType === 'manager'): ?>
                        <li><a class="dropdown-item" href="<?= $prefix ?>/manager/profile.php"><i class="bi bi-person me-2"></i>الملف الشخصي</a></li>
                    <?php elseif ($userType === 'academic_advisor'): ?>
                        <li><a class="dropdown-item" href="<?= $prefix ?>/advisor/profile.php"><i class="bi bi-person me-2"></i>الملف الشخصي</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= $prefix ?>/backend/logout.php"><i class="bi bi-box-arrow-right me-2"></i>تسجيل الخروج</a></li>
                </ul>
            </li>

        </ul>
        <?php endif; ?>
    </div>

</nav>

<!-- Layout Wrapper (flex: sidebar + content) -->
<div class="layout-wrapper">

<?php if ($hasSidebar): ?>
<?php require_once __DIR__ . '/sidebar.php'; ?>
<?php endif; ?>

<div class="page-body">
<div class="page-content">
<!-- Flash Messages -->
<?php showFlash(); ?>
