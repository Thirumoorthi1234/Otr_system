<?php
// migrate.php
require_once 'c:/xampp2/htdocs/otr/includes/config.php';

$sql = file_get_contents('c:/xampp2/htdocs/otr/schema.sql');

try {
    // Remove comments
    $sql = preg_replace('/--.*?\n/', '', $sql);
    
    // Split into individual queries
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query) {
            $pdo->exec($query);
        }
    }
    echo "Migration completed successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
