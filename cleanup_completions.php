<?php
require_once 'includes/config.php';

echo "Re-evaluating module completion for all trainees...\n";

$stmt = $pdo->query("SELECT id, trainee_id, module_id, status FROM assignments");
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($assignments as $a) {
    $tid = $a['trainee_id'];
    $mid = $a['module_id'];
    
    // Total exams for this module
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE module_id = ?");
    $stmt2->execute([$mid]);
    $total = (int)$stmt2->fetchColumn();
    
    // Passed exams by this user
    $stmt3 = $pdo->prepare("SELECT COUNT(DISTINCT exam_id) FROM exam_results WHERE trainee_id = ? AND status = 'pass' AND exam_id IN (SELECT id FROM exams WHERE module_id = ?)");
    $stmt3->execute([$tid, $mid]);
    $passed = (int)$stmt3->fetchColumn();
    
    // Feedback count
    $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE trainee_id = ?");
    $stmt4->execute([$tid]);
    $feedback = (int)$stmt4->fetchColumn();
    
    $new_status = ($passed >= $total && $total > 0 && $feedback > 0) ? 'completed' : 'in_progress';
    if ($total == 0 && $feedback > 0) {
        // Special case for modules without exams? Let's assume they need at least feedback
        //$new_status = 'completed';
    }
    
    if ($a['status'] != $new_status) {
        echo "Updating Trainee ID $tid, Module $mid: {$a['status']} -> $new_status\n";
        $stmt_upd = $pdo->prepare("UPDATE assignments SET status = ? WHERE id = ?");
        $stmt_upd->execute([$new_status, $a['id']]);
    }
}
echo "Cleanup complete.\n";
?>
