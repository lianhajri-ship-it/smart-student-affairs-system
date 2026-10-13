<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// جلب نوع المستخدم الحالي
function getUserType(): string {
    return $_SESSION['user_type'] ?? '';
}

// جلب معرف المستخدم
function getUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

// هل المستخدم طالب؟
function isStudent(): bool {
    return getUserType() === 'student';
}

// هل المستخدم إداري؟
function isAdmin(): bool {
    return in_array(getUserType(), ['admin', 'manager']);
}

// هل المستخدم مدير؟
function isManager(): bool {
    return getUserType() === 'manager';
}

// هل المستخدم مرشد أكاديمي؟
function isAdvisor(): bool {
    return getUserType() === 'academic_advisor';
}

// إجبار تسجيل الدخول
function requireLogin(string $redirect = '/STD/login.php'): void {
    global $pdo;
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
    // Verify user is still active in DB (catches suspend/deactivate after login)
    if (isset($pdo)) {
        $st = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $st->execute([getUserId()]);
        $status = $st->fetchColumn();
        if ($status !== 'active') {
            session_unset();
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'حسابك موقوف أو غير نشط. يرجى التواصل مع الإدارة.'];
            header("Location: $redirect");
            exit;
        }
    }
}

// إجبار أن يكون المستخدم طالباً
function requireStudent(): void {
    requireLogin();
    if (!isStudent()) {
        header('Location: /STD/login.php');
        exit;
    }
}

// إجبار أن يكون المستخدم إدارياً
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /STD/login.php');
        exit;
    }
}

// إجبار أن يكون المستخدم مديراً
function requireManager(): void {
    requireLogin();
    if (!isManager()) {
        header('Location: /STD/login.php');
        exit;
    }
}

// إجبار أن يكون المستخدم مرشداً أكاديمياً
function requireAdvisor(): void {
    requireLogin();
    if (!isAdvisor()) {
        header('Location: /STD/login.php');
        exit;
    }
}

// تسجيل الدخول
function loginUser(array $user): void {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_type']  = $user['user_type'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['profile_image'] = $user['profile_image'] ?? '';
}

// تسجيل الخروج
function logoutUser(): void {
    session_unset();
    session_destroy();
}

// توجيه المستخدم بعد الدخول حسب دوره
function redirectByRole(): void {
    $type = getUserType();
    if ($type === 'student') {
        header('Location: /STD/student/dashboard.php');
    } elseif ($type === 'admin') {
        header('Location: /STD/admin/dashboard.php');
    } elseif ($type === 'manager') {
        header('Location: /STD/manager/dashboard.php');
    } elseif ($type === 'academic_advisor') {
        header('Location: /STD/advisor/dashboard.php');
    } else {
        header('Location: /STD/login.php');
    }
    exit;
}
