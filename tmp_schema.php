<?php
$db = new PDO('mysql:host=localhost;dbname=otr_system', 'root', '');
try {
    $db->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
    echo "Added status column.\n";
} catch (Exception $e) {
    echo "Column might exist: " . $e->getMessage() . "\n";
}
?>
