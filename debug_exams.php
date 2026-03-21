<?php
require_once 'includes/config.php';
$stmt = $pdo->query("SELECT id, title, module_id FROM exams");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | Title: " . $row['title'] . " | Module ID: " . $row['module_id'] . "\n";
}
?>
