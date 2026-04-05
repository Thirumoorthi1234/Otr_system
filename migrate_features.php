<?php
// migrate_features.php — Run all migration for 10-feature update
// Access once via browser: http://localhost/otr/migrate_features.php
require_once 'includes/config.php';

$steps = [];

function runSQL($pdo, $label, $sql) {
    global $steps;
    try {
        $pdo->exec($sql);
        $steps[] = ['ok', $label];
    } catch (PDOException $e) {
        $steps[] = ['warn', "$label: " . $e->getMessage()];
    }
}

// ─── Feature 1: OTP Login columns ───
runSQL($pdo, 'Add aadhar_number to users',
    "ALTER TABLE users ADD COLUMN aadhar_number VARCHAR(12) UNIQUE NULL AFTER employee_id");
runSQL($pdo, 'Add mobile_number to users',
    "ALTER TABLE users ADD COLUMN mobile_number VARCHAR(15) UNIQUE NULL AFTER aadhar_number");
runSQL($pdo, 'Add otp to users',
    "ALTER TABLE users ADD COLUMN otp VARCHAR(6) NULL AFTER mobile_number");
runSQL($pdo, 'Add otp_expires_at to users',
    "ALTER TABLE users ADD COLUMN otp_expires_at DATETIME NULL AFTER otp");
runSQL($pdo, 'Add status to users',
    "ALTER TABLE users ADD COLUMN status ENUM('active','inactive') DEFAULT 'active' AFTER otp_expires_at");
// Set all existing users to active
runSQL($pdo, 'Set all existing users active',
    "UPDATE users SET status='active' WHERE status IS NULL OR status=''");

// ─── Feature 4: OJT Evidence ───
runSQL($pdo, 'Create ojt_evidence table', "
    CREATE TABLE IF NOT EXISTS ojt_evidence (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assignment_id INT NOT NULL,
        trainee_id INT NOT NULL,
        photo_path VARCHAR(255),
        caption VARCHAR(255),
        captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
        FOREIGN KEY (trainee_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// ─── Feature 6: Refresher Training ───
runSQL($pdo, 'Create refresher_training table', "
    CREATE TABLE IF NOT EXISTS refresher_training (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trainee_id INT NOT NULL,
        module_id INT NOT NULL,
        due_date DATE NOT NULL,
        status ENUM('pending','in_progress','completed','overdue') DEFAULT 'pending',
        assigned_by INT,
        notes TEXT,
        completed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trainee_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (module_id) REFERENCES training_modules(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
    )
");

// ─── Feature 8: Exam Answer Logging ───
runSQL($pdo, 'Create exam_result_answers table', "
    CREATE TABLE IF NOT EXISTS exam_result_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        result_id INT NOT NULL,
        question_id INT NOT NULL,
        trainee_answer ENUM('A','B','C','D') NULL,
        is_correct TINYINT(1) DEFAULT 0,
        FOREIGN KEY (result_id) REFERENCES exam_results(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    )
");

// ─── Feature 10: AI Training Topics ───
runSQL($pdo, 'Insert AI Training Module', "
    INSERT IGNORE INTO training_modules (title, description, category, total_hours)
    VALUES 
    ('AI & Digital Skills for Manufacturing', 'Comprehensive AI literacy and digital tools training for shop floor operators and staff.', 'AI & Digital', 16),
    ('Predictive Maintenance with AI', 'Learn how AI enables early fault detection and predictive maintenance in electronics manufacturing.', 'AI & Digital', 8),
    ('Robotic Process Automation (RPA) Fundamentals', 'Introduction to RPA tools and how they automate repetitive tasks in quality and production.', 'AI & Digital', 8)
");

// ─── Feature 10: AI Induction Topics ───
// Get max day_number to add new day
$maxDay = $pdo->query("SELECT MAX(day_number) FROM induction_checklist")->fetchColumn();
$aiDay = max(4, (int)$maxDay + 1);

runSQL($pdo, 'Insert AI induction checklist topics', "
    INSERT IGNORE INTO induction_checklist (day_number, section_name, topic_name, estimated_hours, estimated_mins)
    VALUES 
    ($aiDay, 'AI & DIGITAL SKILLS', 'Introduction to Artificial Intelligence in Manufacturing', 1, 60),
    ($aiDay, 'AI & DIGITAL SKILLS', 'AI-Powered Quality Control & Visual Inspection', 1, 60),
    ($aiDay, 'AI & DIGITAL SKILLS', 'Machine Learning Basics for Operators', 1, 60),
    ($aiDay, 'AI & DIGITAL SKILLS', 'AI Productivity Tools (ChatGPT, Copilot, Gemini)', 1, 60),
    ($aiDay, 'AI & DIGITAL SKILLS', 'Data Ethics & Privacy in AI Systems', 0.5, 30),
    ($aiDay, 'AI & DIGITAL SKILLS', 'Predictive Maintenance using AI Sensors', 0.5, 30),
    ($aiDay, 'AI & DIGITAL SKILLS', 'Robotic Process Automation (RPA) Overview', 1, 60)
");

// Create uploads directory for OJT evidence
$dir = __DIR__ . '/assets/uploads/ojt_evidence';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
    $steps[] = ['ok', 'Created ojt_evidence upload directory'];
} else {
    $steps[] = ['ok', 'ojt_evidence upload directory already exists'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>OTR Migration — 10-Feature Update</title>
<style>
    body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; padding: 40px; }
    h1 { color: #38bdf8; margin-bottom: 30px; }
    .step { display: flex; align-items: center; gap: 12px; padding: 10px 16px; margin-bottom: 8px; border-radius: 8px; font-size: 0.9rem; }
    .ok  { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7; }
    .warn{ background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); color: #fcd34d; }
    .icon-ok   { color: #10b981; font-size: 1.1rem; }
    .icon-warn { color: #f59e0b; font-size: 1.1rem; }
    .done { margin-top: 30px; padding: 20px; background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.3); border-radius: 12px; color: #38bdf8; font-weight: 700; font-size: 1.1rem; }
</style>
</head>
<body>
<h1>🚀 OTR System — Feature Migration</h1>
<?php foreach ($steps as [$type, $msg]): ?>
<div class="step <?php echo $type; ?>">
    <span class="icon-<?php echo $type; ?>"><?php echo $type === 'ok' ? '✔' : '⚠'; ?></span>
    <?php echo htmlspecialchars($msg); ?>
</div>
<?php endforeach; ?>
<div class="done">
    ✅ Migration complete! <a href="index.php" style="color:#38bdf8;">Go to Login »</a>
    &nbsp;|&nbsp; <a href="admin/dashboard.php" style="color:#38bdf8;">Admin Dashboard »</a>
</div>
</body>
</html>
