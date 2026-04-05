<?php
require_once 'includes/config.php';

try {
    $pdo->exec("ALTER TABLE training_modules ADD COLUMN curriculum_path VARCHAR(255) DEFAULT NULL;");
    echo "Success: curriculum_path column added to training_modules table.";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Info: curriculum_path column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
unlink(__FILE__); // Self-delete
