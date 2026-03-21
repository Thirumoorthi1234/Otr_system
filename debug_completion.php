<?php
require_once 'includes/config.php';

echo "Database Completion Status Check:\n";

// Get all assignments
$stmt = $pdo->query("SELECT a.id, a.trainee_id, a.module_id, a.status, u.full_name 
                    FROM assignments a 
                    JOIN users u ON a.trainee_id = u.id");
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($assignments as $a) {
    echo "Trainee: {$a['full_name']} (ID: {$a['trainee_id']}), Module: {$a['module_id']}, Status: {$a['status']}\n";
    
    // Total exams for this module
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE module_id = ?");
    $stmt2->execute([$a['module_id']]);
    $total = $stmt2->fetchColumn();
    
    // Passed exams by this user
    $stmt3 = $pdo->prepare("SELECT COUNT(DISTINCT exam_id) FROM exam_results WHERE trainee_id = ? AND status = 'pass' AND exam_id IN (SELECT id FROM exams WHERE module_id = ?)");
    $stmt3->execute([$a['trainee_id'], $a['module_id']]);
    $passed = $stmt3->fetchColumn();
    
    // Feedback count
    $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE trainee_id = ?");
    $stmt4->execute([$a['trainee_id']]);
    $feedback = $stmt4->fetchColumn();
    
    echo "  - Exams Passed: $passed / $total\n";
    echo "  - Feedback submitted: $feedback\n";
    
    if ($a['status'] == 'completed' && ($passed < $total || $feedback == 0)) {
        echo "  - [ISSUE] Marked as completed but missing " . ($passed < $total ? "exams " : "") . ($feedback == 0 ? "feedback" : "") . "\n";
    }
}
?>
