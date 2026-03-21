<?php
require_once 'c:/xampp2/htdocs/otr/includes/config.php';
try {
    // 1. Make assignment_id nullable (though it is already Key: MUL, let's ensure Null: YES)
    // Actually, schema_output.txt said Null: NO for assignment_id.
    $pdo->exec("ALTER TABLE feedback MODIFY assignment_id INT NULL");
    
    // 2. Make rating_skill nullable (schema_output.txt said Null: NO)
    $pdo->exec("ALTER TABLE feedback MODIFY rating_skill ENUM('A','B','C','D') NULL");
    
    echo "Database schema updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
