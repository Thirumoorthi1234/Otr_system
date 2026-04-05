<?php
require_once 'includes/config.php';

echo "<h2>System Fix Utility</h2>";

try {
    // 1. Fix Database Column
    $pdo->exec("ALTER TABLE training_modules ADD COLUMN curriculum_path VARCHAR(255) DEFAULT NULL;");
    echo "<p style='color:green;'>✅ Success: 'curriculum_path' column added to training_modules.</p>";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "<p style='color:blue;'>ℹ️ Info: Column already exists.</p>";
    } else {
        echo "<p style='color:red;'>❌ DB Error: " . $e->getMessage() . "</p>";
    }
}

// 2. Clear Session just in case
session_start();
unset($_SESSION['error_msg']);

echo "<p>Checking paths...</p>";
$uploadDir = __DIR__ . '/assets/curriculum/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    echo "<p>✅ Assets directory created.</p>";
}

echo "<hr><p><b>Fix Complete!</b> Please go back to <a href='admin/modules.php'>Module Management</a> and try again.</p>";
?>
