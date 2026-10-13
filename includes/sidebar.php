<?php
// sidebar.php — included by header.php for admin/manager layouts
// $userType and $prefix are already set by header.php
$uri = $_SERVER['REQUEST_URI'];

function sidebarActive(string $path): string {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
}
?>
<aside class="std-sidebar" id="stdSidebar">
    <?php if ($userType === 'manager'): ?>
    <!-- ===== MANAGER SIDEBAR ===== -->
    <div class="sidebar-section-title">الرئيسية</div>
    <a href="<?= $prefix ?>/manager/dashboard.php"  class="sidebar-link <?= sidebarActive('/manager/dashboard') ?>">
        <i class="bi bi-speedometer2"></i> لوحة التحكم
    </a>

    <div class="sidebar-section-title">المستخدمون</div>
    <a href="<?= $prefix ?>/manager/students.php"   class="sidebar-link <?= sidebarActive('/manager/students') ?>">
        <i class="bi bi-person-badge"></i> الطلاب
    </a>
    <a href="<?= $prefix ?>/manager/admins.php"     class="sidebar-link <?= sidebarActive('/manager/admins') ?>">
        <i class="bi bi-person-gear"></i> الإداريون
    </a>
    <a href="<?= $prefix ?>/manager/advisors.php"   class="sidebar-link <?= sidebarActive('/manager/advisors') ?>">
        <i class="bi bi-person-check"></i> المشرفون الأكاديميون
    </a>

    <div class="sidebar-section-title">البنية الأكاديمية</div>
    <a href="<?= $prefix ?>/manager/colleges.php"   class="sidebar-link <?= sidebarActive('/manager/colleges') ?>">
        <i class="bi bi-building"></i> الكليات
    </a>
    <a href="<?= $prefix ?>/manager/degrees.php"    class="sidebar-link <?= sidebarActive('/manager/degrees') ?>">
        <i class="bi bi-award"></i> الدرجات العلمية
    </a>
    <a href="<?= $prefix ?>/manager/majors.php"     class="sidebar-link <?= sidebarActive('/manager/majors') ?>">
        <i class="bi bi-diagram-3"></i> التخصصات
    </a>
    <a href="<?= $prefix ?>/manager/user_types.php" class="sidebar-link <?= sidebarActive('/manager/user_types') ?>">
        <i class="bi bi-tags"></i> أنواع المستخدمين
    </a>
    <a href="<?= $prefix ?>/manager/departments.php" class="sidebar-link <?= sidebarActive('/manager/departments') ?>">
        <i class="bi bi-building-fill"></i> الأقسام
    </a>

    <div class="sidebar-section-title">الطلبات والخدمات</div>
    <a href="<?= $prefix ?>/manager/requests.php"   class="sidebar-link <?= sidebarActive('/manager/requests') ?>">
        <i class="bi bi-inbox"></i> الطلبات
    </a>
    <a href="<?= $prefix ?>/manager/services.php"   class="sidebar-link <?= sidebarActive('/manager/services') ?>">
        <i class="bi bi-grid"></i> الخدمات
    </a>
    <a href="<?= $prefix ?>/manager/meetings.php"   class="sidebar-link <?= sidebarActive('/manager/meetings') ?>">
        <i class="bi bi-journal-text"></i> محاضر الاجتماعات
    </a>

    <div class="sidebar-section-title">المحتوى</div>
    <a href="<?= $prefix ?>/manager/guides.php"     class="sidebar-link <?= sidebarActive('/manager/guides') ?>">
        <i class="bi bi-book"></i> الأدلة
    </a>
    <a href="<?= $prefix ?>/manager/forms.php"      class="sidebar-link <?= sidebarActive('/manager/forms') ?>">
        <i class="bi bi-file-earmark"></i> النماذج
    </a>
    <a href="<?= $prefix ?>/manager/faq.php"        class="sidebar-link <?= sidebarActive('/manager/faq') ?>">
        <i class="bi bi-question-circle"></i> الأسئلة الشائعة
    </a>
    <a href="<?= $prefix ?>/manager/knowledge_base.php" class="sidebar-link <?= sidebarActive('/manager/knowledge_base') ?>">
        <i class="bi bi-database"></i> قاعدة المعرفة
    </a>
    <a href="<?= $prefix ?>/manager/chatbot_reviews.php" class="sidebar-link <?= sidebarActive('/manager/chatbot_reviews') ?>">
        <i class="bi bi-chat-dots"></i> مراجعة المحادثات
    </a>
    <a href="<?= $prefix ?>/admin/unknown_questions.php" class="sidebar-link <?= sidebarActive('/admin/unknown_questions') ?>">
        <i class="bi bi-question-diamond-fill"></i> أسئلة غير مُجابة
        <?php
        $pendingUQ = $pdo->query("SELECT COUNT(*) FROM unknown_questions WHERE status='pending'")->fetchColumn();
        if ($pendingUQ > 0): ?>
            <span class="badge bg-danger ms-auto"><?= $pendingUQ ?></span>
        <?php endif; ?>
    </a>

    <div class="sidebar-section-title">النظام</div>
    <a href="<?= $prefix ?>/manager/reports.php"    class="sidebar-link <?= sidebarActive('/manager/reports') ?>">
        <i class="bi bi-bar-chart"></i> التقارير
    </a>
    <a href="<?= $prefix ?>/manager/settings.php"   class="sidebar-link <?= sidebarActive('/manager/settings') ?>">
        <i class="bi bi-gear"></i> الإعدادات
    </a>

    <?php elseif ($userType === 'admin'): ?>
    <!-- ===== ADMIN SIDEBAR ===== -->
    <div class="sidebar-section-title">الرئيسية</div>
    <a href="<?= $prefix ?>/admin/dashboard.php"    class="sidebar-link <?= sidebarActive('/admin/dashboard') ?>">
        <i class="bi bi-speedometer2"></i> لوحة التحكم
    </a>

    <div class="sidebar-section-title">الطلبات</div>
    <a href="<?= $prefix ?>/admin/requests.php"     class="sidebar-link <?= sidebarActive('/admin/requests') ?>">
        <i class="bi bi-inbox"></i> الطلبات الواردة
    </a>
    <a href="<?= $prefix ?>/manager/meetings.php"   class="sidebar-link <?= sidebarActive('/manager/meetings') ?>">
        <i class="bi bi-journal-text"></i> محاضر الاجتماعات
    </a>

    <div class="sidebar-section-title">المحتوى</div>
    <a href="<?= $prefix ?>/admin/services.php"     class="sidebar-link <?= sidebarActive('/admin/services') ?>">
        <i class="bi bi-grid"></i> الخدمات
    </a>
    <a href="<?= $prefix ?>/admin/guides.php"       class="sidebar-link <?= sidebarActive('/admin/guides') ?>">
        <i class="bi bi-book"></i> الأدلة
    </a>
    <a href="<?= $prefix ?>/admin/forms.php"        class="sidebar-link <?= sidebarActive('/admin/forms') ?>">
        <i class="bi bi-file-earmark"></i> النماذج
    </a>
    <a href="<?= $prefix ?>/admin/faq.php"          class="sidebar-link <?= sidebarActive('/admin/faq') ?>">
        <i class="bi bi-question-circle"></i> الأسئلة الشائعة
    </a>
    <a href="<?= $prefix ?>/admin/knowledge_base.php" class="sidebar-link <?= sidebarActive('/admin/knowledge_base') ?>">
        <i class="bi bi-database"></i> قاعدة المعرفة
    </a>
    <a href="<?= $prefix ?>/admin/chatbot_reviews.php" class="sidebar-link <?= sidebarActive('/admin/chatbot_reviews') ?>">
        <i class="bi bi-chat-dots"></i> مراجعة المحادثات
    </a>
    <a href="<?= $prefix ?>/admin/unknown_questions.php" class="sidebar-link <?= sidebarActive('/admin/unknown_questions') ?>">
        <i class="bi bi-question-diamond-fill"></i> أسئلة غير مُجابة
        <?php if (!isset($pendingUQ)):
            $pendingUQ = $pdo->query("SELECT COUNT(*) FROM unknown_questions WHERE status='pending'")->fetchColumn();
        endif;
        if ($pendingUQ > 0): ?>
            <span class="badge bg-danger ms-auto"><?= $pendingUQ ?></span>
        <?php endif; ?>
    </a>

    <?php elseif ($userType === 'student'): ?>
    <!-- ===== STUDENT SIDEBAR ===== -->
    <div class="sidebar-section-title">القائمة الرئيسية</div>
    <a href="<?= $prefix ?>/student/dashboard.php"   class="sidebar-link <?= sidebarActive('/student/dashboard') ?>">
        <i class="bi bi-speedometer2"></i> الرئيسية
    </a>
    <a href="<?= $prefix ?>/student/chatbot.php"     class="sidebar-link <?= sidebarActive('/student/chatbot') ?>">
        <i class="bi bi-robot"></i> المساعد الذكي
    </a>
    <a href="<?= $prefix ?>/student/my_requests.php" class="sidebar-link <?= sidebarActive('/student/my_requests') ?>">
        <i class="bi bi-inbox"></i> طلباتي
    </a>
    <a href="<?= $prefix ?>/student/new_request.php" class="sidebar-link <?= sidebarActive('/student/new_request') ?>">
        <i class="bi bi-send"></i> طلب جديد
    </a>

    <div class="sidebar-section-title">حسابي</div>
    <a href="<?= $prefix ?>/student/profile.php"     class="sidebar-link <?= sidebarActive('/student/profile') ?>">
        <i class="bi bi-person-circle"></i> الملف الشخصي
    </a>

    <?php elseif ($userType === 'academic_advisor'): ?>
    <!-- ===== ADVISOR SIDEBAR ===== -->
    <div class="sidebar-section-title">الرئيسية</div>
    <a href="<?= $prefix ?>/advisor/dashboard.php" class="sidebar-link <?= sidebarActive('/advisor/dashboard') ?>">
        <i class="bi bi-speedometer2"></i> لوحة التحكم
    </a>

    <div class="sidebar-section-title">الطلبات</div>
    <a href="<?= $prefix ?>/advisor/requests.php" class="sidebar-link <?= sidebarActive('/advisor/requests') ?>">
        <i class="bi bi-inbox"></i> الطلبات الواردة
    </a>
    <a href="<?= $prefix ?>/manager/meetings.php" class="sidebar-link <?= sidebarActive('/manager/meetings') ?>">
        <i class="bi bi-journal-text"></i> محاضر الاجتماعات
    </a>

    <div class="sidebar-section-title">طلابي</div>
    <a href="<?= $prefix ?>/advisor/students.php" class="sidebar-link <?= sidebarActive('/advisor/students') ?>">
        <i class="bi bi-people"></i> قائمة الطلاب
    </a>

    <?php endif; ?>
</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
