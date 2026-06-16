<?php
/**
 * Migration: Create Skill Matrix tables
 * - skill_matrix_skills: Configurable skill definitions
 * - skill_matrix_entries: Per-trainee numeric scores (1-4)
 * - skill_matrix_reports: Report metadata (header info)
 */
require_once __DIR__ . '/includes/config.php';

echo "=== Skill Matrix Migration ===\n\n";

try {
    // 1. skill_matrix_reports — Report metadata
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS skill_matrix_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_title VARCHAR(255) NOT NULL DEFAULT 'Skill Matrix',
            report_date DATE NOT NULL,
            site_name VARCHAR(255) DEFAULT '',
            department VARCHAR(255) DEFAULT '',
            supervisor_name VARCHAR(255) DEFAULT '',
            trainer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "[OK] Created table: skill_matrix_reports\n";

    // 2. skill_matrix_skills — Skill definitions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS skill_matrix_skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NULL,
            skill_name VARCHAR(255) NOT NULL,
            category_group VARCHAR(100) NOT NULL COMMENT 'Process Knowledge or Operating Knowledge',
            sub_category VARCHAR(100) DEFAULT '' COMMENT 'Basic Process Knowledge, Basic Operating Skill, Knowledge/Awareness',
            required_level INT NOT NULL DEFAULT 4,
            sort_order INT NOT NULL DEFAULT 0,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES skill_matrix_reports(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "[OK] Created table: skill_matrix_skills\n";

    // 3. skill_matrix_entries — Per-trainee scores
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS skill_matrix_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            trainee_id INT NOT NULL,
            skill_id INT NOT NULL,
            report_id INT NOT NULL,
            score INT NULL COMMENT '1-4 or NULL for NA',
            scored_by INT NULL,
            scored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (trainee_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (skill_id) REFERENCES skill_matrix_skills(id) ON DELETE CASCADE,
            FOREIGN KEY (report_id) REFERENCES skill_matrix_reports(id) ON DELETE CASCADE,
            FOREIGN KEY (scored_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_entry (trainee_id, skill_id, report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "[OK] Created table: skill_matrix_entries\n";

    echo "\n=== Migration Complete ===\n";

} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
?>
