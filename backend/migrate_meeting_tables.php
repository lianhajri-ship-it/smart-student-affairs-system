<?php
// Run once: creates meeting_minutes and meeting_recommendations tables
require_once __DIR__ . '/../includes/db.php';

$pdo->exec("
CREATE TABLE IF NOT EXISTS meeting_minutes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    meeting_date DATE NOT NULL,
    notes TEXT,
    status ENUM('complete','incomplete') NOT NULL DEFAULT 'incomplete',
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS meeting_recommendations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    rec_status ENUM('approved','rejected','other','notes') NULL DEFAULT NULL,
    rec_notes TEXT,
    support_type ENUM('loan','prize','fixed_grant','marriage_grant','other') NULL DEFAULT NULL,
    support_amount DECIMAL(10,2) NULL DEFAULT NULL,
    disbursement_status ENUM('disbursed','not_disbursed') NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meeting_minutes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "Tables created successfully.";
