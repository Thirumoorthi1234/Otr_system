<?php
// migrate_v2.php - Add batch_number to users and camera_enabled to exams
require_once 'includes/config.php';

$migrations = [];

// 1. Add batch_number column to users table
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN batch_number VARCHAR(50) DEFAULT NULL AFTER category");
    $migrations[] = "✅ Added 'batch_number' column to users table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $migrations[] = "ℹ️ 'batch_number' column already exists in users table.";
    } else {
        $migrations[] = "❌ Error adding batch_number: " . $e->getMessage();
    }
}

// 2. Add camera_enabled column to exams table
try {
    $pdo->exec("ALTER TABLE exams ADD COLUMN camera_enabled TINYINT(1) DEFAULT 1 AFTER passing_score");
    $migrations[] = "✅ Added 'camera_enabled' column to exams table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $migrations[] = "ℹ️ 'camera_enabled' column already exists in exams table.";
    } else {
        $migrations[] = "❌ Error adding camera_enabled: " . $e->getMessage();
    }
}

echo "<h2>Migration Results</h2>";
echo "<ul>";
foreach ($migrations as $msg) {
    echo "<li style='margin: 10px 0; font-family: sans-serif;'>$msg</li>";
}
echo "</ul>";
echo "<br><a href='index.php'>← Back to Login</a>";
?>
