<?php
// trainer/export_mis.php — Export all trainee data for this trainer
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole('trainer');

$trainer_id = $_SESSION['user_id'];

// Filename
$filename = "OTR_MIS_Data_" . date('Y-m-d') . ".csv";

// Headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// CSV Headers
fputcsv($output, [
    'Trainee Name', 
    'Employee ID', 
    'Department', 
    'Batch', 
    'DOJ',
    'Module Assigned',
    'Assignment Status',
    'Assigned Date',
    'Completion Date',
    'Induction Progress (%)',
    'Exam Score (%)',
    'Exam Status',
    'OTJ Stages Completed',
    'Total Man Hours'
]);

// Fetch Data
$stmt = $pdo->prepare("
    SELECT 
        u.full_name, u.employee_id, u.department, u.batch_number, u.doj,
        m.title as module_name,
        a.status as assignment_status, a.assigned_date, a.completion_date,
        a.id as assignment_id,
        u.id as trainee_id
    FROM assignments a
    JOIN users u ON a.trainee_id = u.id
    JOIN training_modules m ON a.module_id = m.id
    WHERE a.trainer_id = ?
    ORDER BY u.full_name ASC
");
$stmt->execute([$trainer_id]);
$trainees = $stmt->fetchAll();

foreach ($trainees as $t) {
    // 1. Induction Progress
    $stmt_ind = $pdo->prepare("SELECT COUNT(*) FROM trainee_checklist_progress WHERE trainee_id = ?");
    $stmt_ind->execute([$t['trainee_id']]);
    $done = $stmt_ind->fetchColumn();
    
    $total_ind = $pdo->query("SELECT COUNT(*) FROM induction_checklist")->fetchColumn();
    $ind_percent = $total_ind > 0 ? round(($done / $total_ind) * 100) : 0;
    
    // 2. Exam Results
    $stmt_ex = $pdo->prepare("
        SELECT er.score, er.status 
        FROM exam_results er 
        JOIN exams e ON er.exam_id = e.id 
        WHERE er.trainee_id = ? AND e.module_id = (SELECT module_id FROM assignments WHERE id = ?)
        ORDER BY er.exam_date DESC LIMIT 1
    ");
    $stmt_ex->execute([$t['trainee_id'], $t['assignment_id']]);
    $exam = $stmt_ex->fetch();
    
    // 3. OTJ Stages & Hours
    $stmt_otj = $pdo->prepare("SELECT COUNT(*), SUM(man_hours) FROM training_stages WHERE assignment_id = ?");
    $stmt_otj->execute([$t['assignment_id']]);
    $otj = $stmt_otj->fetch();

    fputcsv($output, [
        $t['full_name'],
        $t['employee_id'],
        $t['department'],
        $t['batch_number'] ?: '-',
        $t['doj'] ?: '-',
        $t['module_name'],
        ucfirst($t['assignment_status']),
        $t['assigned_date'],
        $t['completion_date'] ?: '-',
        $ind_percent . '%',
        ($exam ? $exam['score'] : '-') . '%',
        $exam ? ucfirst($exam['status']) : '-',
        $otj[0] ?: 0,
        $otj[1] ?: 0
    ]);
}

fclose($output);
exit();
