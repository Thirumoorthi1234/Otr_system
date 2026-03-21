<?php
// update_admin_empid.php - Set employee_id for admin if not set
require_once 'includes/config.php';

// Update admin user: set employee_id = username for users who don't have an employee_id
$stmt = $pdo->prepare("UPDATE users SET employee_id = username WHERE employee_id IS NULL OR employee_id = ''");
$result = $stmt->execute();
$affected = $stmt->rowCount();

echo "<h2>Update Complete</h2>";
echo "<p style='font-family: sans-serif;'>Updated {$affected} user(s) — set employee_id = username for users who were missing an employee_id.</p>";
echo "<br><a href='index.php'>← Back to Login</a>";
?>
