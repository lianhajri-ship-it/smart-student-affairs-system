<?php
require_once __DIR__ . '/../includes/db.php';

$pdo->exec("
CREATE TABLE IF NOT EXISTS unknown_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    confidence_score DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
    status ENUM('pending','reviewed','added_to_kb') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL DEFAULT NULL,
    reviewed_at DATETIME NULL DEFAULT NULL,
    notified TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (conversation_id) REFERENCES chatbot_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "Table unknown_questions created successfully.";
