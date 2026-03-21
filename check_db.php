<?php
require_once 'includes/config.php';

function checkTable($pdo, $tableName) {
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        echo "Structure of '$tableName':\n";
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "'$tableName' table does not exist.\n";
    }
}

echo "Database Check:\n";
checkTable($pdo, 'notifications');
checkTable($pdo, 'users');
checkTable($pdo, 'assignments');
checkTable($pdo, 'exams');
checkTable($pdo, 'exam_results');
checkTable($pdo, 'feedback');
?>
