<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /STD/manager/settings.php'); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header('Location: /STD/manager/settings.php'); exit;
}

$stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()");

$action = $_POST['action'] ?? '';

// ── Logo upload ──────────────────────────────────────────────────────────
if ($action === 'upload_logo') {
    // Remove logo
    if (!empty($_POST['remove_logo'])) {
        $old = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='site_logo'")->fetchColumn();
        if ($old) {
            $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/STD/assets/uploads/' . $old;
            if (file_exists($oldPath)) @unlink($oldPath);
        }
        $stmt->execute(['site_logo', '']);
        setFlash('success', 'تم حذف الشعار');
        header('Location: /STD/manager/settings.php'); exit;
    }

    // Upload new logo
    if (!empty($_FILES['site_logo']['name'])) {
        $upload = uploadFile($_FILES['site_logo'], 'logos', ['png','jpg','jpeg','svg','webp']);
        if ($upload['success']) {
            // Delete old file
            $old = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='site_logo'")->fetchColumn();
            if ($old) {
                $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/STD/assets/uploads/' . $old;
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            $stmt->execute(['site_logo', $upload['path']]);
            logActivity($pdo, getUserId(), 'upload_logo', 'رفع شعار النظام');
            setFlash('success', 'تم رفع الشعار بنجاح');
        } else {
            setFlash('error', 'فشل رفع الشعار: ' . ($upload['error'] ?? ''));
        }
    } else {
        setFlash('info', 'لم يتم اختيار ملف');
    }
    header('Location: /STD/manager/settings.php'); exit;
}

// ── General + AI settings ────────────────────────────────────────────────
$settingKeys = [
    'site_name', 'site_name_en', 'university_name', 'support_email', 'support_phone',
    'maintenance_mode', 'gemini_api_key', 'gemini_model', 'gemini_embedding_model',
    'chatbot_enabled', 'chunk_size', 'max_chunks_retrieve'
];

foreach ($settingKeys as $key) {
    if (isset($_POST[$key])) {
        $val = trim($_POST[$key]);
        $stmt->execute([$key, $val]);
    }
}

logActivity($pdo, getUserId(), 'save_settings', 'تحديث إعدادات النظام');
setFlash('success', 'تم حفظ الإعدادات بنجاح');
header('Location: /STD/manager/settings.php');
exit;
