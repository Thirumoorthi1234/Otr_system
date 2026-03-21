<?php
/**
 * Seed Script: Adds 50 dummy users + full test data
 * 30 trainees (3 batches x 10), 10 trainers, 7 managers, 3 admins
 * + assignments, exam results, training stages, feedback, induction scores
 */
require_once __DIR__ . '/includes/config.php';

$defaultPass = password_hash('password', PASSWORD_DEFAULT);

// Names Pool
$firstNames = ['Arun','Priya','Karthik','Divya','Ravi','Meena','Sanjay','Lakshmi','Vikram','Nithya',
               'Ganesh','Anjali','Suresh','Deepa','Raj','Kavitha','Mohan','Saranya','Bala','Swetha',
               'Hari','Pooja','Naveen','Bhavani','Ashok','Revathi','Dinesh','Sudha','Manoj','Vanitha',
               'Kumar','Prema','Sathish','Gomathi','Venkat','Jayanthi','Prasad','Mythili','Anand','Padma',
               'Senthil','Suganya','Murugan','Indira','Ramesh','Thenmozhi','Selvam','Chitra','Vignesh','Usha'];

$lastNames = ['Kumar','Rajan','Shankar','Patel','Nair','Sundaram','Krishnan','Reddy','Iyer','Pillai',
              'Babu','Devi','Naidu','Sharma','Murugan','Singh','Rao','Gupta','Das','Menon'];

$departments = ['SMT Production','Mechanical Assembly','Quality Control','ESD & Safety','Packaging & Logistics'];
$qualifications = ['B.E Mechanical','B.Tech ECE','Diploma EEE','ITI Fitter','B.Sc Physics','M.Tech','Diploma Mech','B.E CSE','B.Tech IT','MBA'];

echo "Starting seed...\n";

// ─── Clear old test data (only dummy data, keep originals) ──
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("DELETE FROM users WHERE username LIKE 'admin_%' OR username LIKE 'manager_%' OR username LIKE 'trainer_%' OR username LIKE 'trainee_%'");
$pdo->exec("DELETE FROM exam_results");
$pdo->exec("DELETE FROM training_stages");
$pdo->exec("DELETE FROM assignments");
$pdo->exec("DELETE FROM feedback");
$pdo->exec("DELETE FROM induction_scores");
$pdo->exec("DELETE FROM trainee_checklist_progress");
echo "Cleanup done.\n";

$newUserIds = ['trainee' => [], 'trainer' => [], 'management' => [], 'admin' => []];
$userIndex = 0;

// ─── 3 ADMINS ──────────────────────────────────────────────
for ($i = 1; $i <= 3; $i++) {
    $fn = $firstNames[$userIndex] . ' ' . $lastNames[array_rand($lastNames)];
    $empId = 'TEST_ADM' . str_pad($i, 3, '0', STR_PAD_LEFT);
    $uname = 'admin_' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $dept = 'Administration';
    $qual = $qualifications[array_rand($qualifications)];
    $doj = date('Y-m-d', strtotime('-' . rand(365, 730) . ' days'));
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, employee_id, role, department, qualification, doj, email) VALUES (?, ?, ?, ?, 'admin', ?, ?, ?, ?)");
    $stmt->execute([$uname, $defaultPass, $fn, $empId, $dept, $qual, $doj, strtolower(str_replace(' ','.',$fn)).'@syrmasgs.com']);
    $newUserIds['admin'][] = $pdo->lastInsertId();
    $userIndex++;
}
echo "3 admins created.\n";

// ─── 7 MANAGERS ────────────────────────────────────────────
for ($i = 1; $i <= 7; $i++) {
    $fn = $firstNames[$userIndex] . ' ' . $lastNames[array_rand($lastNames)];
    $empId = 'TEST_MGR' . str_pad($i, 3, '0', STR_PAD_LEFT);
    $uname = 'manager_' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $dept = $departments[($i - 1) % count($departments)];
    $qual = $qualifications[array_rand($qualifications)];
    $doj = date('Y-m-d', strtotime('-' . rand(365, 1095) . ' days'));
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, employee_id, role, department, qualification, doj, email) VALUES (?, ?, ?, ?, 'management', ?, ?, ?, ?)");
    $stmt->execute([$uname, $defaultPass, $fn, $empId, $dept, $qual, $doj, strtolower(str_replace(' ','.',$fn)).'@syrmasgs.com']);
    $newUserIds['management'][] = $pdo->lastInsertId();
    $userIndex++;
}
echo "7 managers created.\n";

// ─── 10 TRAINERS ───────────────────────────────────────────
for ($i = 1; $i <= 10; $i++) {
    $fn = $firstNames[$userIndex] . ' ' . $lastNames[array_rand($lastNames)];
    $empId = 'TEST_TRN' . str_pad($i, 3, '0', STR_PAD_LEFT);
    $uname = 'trainer_' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $dept = $departments[($i - 1) % count($departments)];
    $qual = $qualifications[array_rand($qualifications)];
    $doj = date('Y-m-d', strtotime('-' . rand(365, 1460) . ' days'));
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, employee_id, role, department, qualification, doj, email) VALUES (?, ?, ?, ?, 'trainer', ?, ?, ?, ?)");
    $stmt->execute([$uname, $defaultPass, $fn, $empId, $dept, $qual, $doj, strtolower(str_replace(' ','.',$fn)).'@syrmasgs.com']);
    $newUserIds['trainer'][] = $pdo->lastInsertId();
    $userIndex++;
}
echo "10 trainers created.\n";

// ─── 30 TRAINEES (3 batches x 10) ─────────────────────────
$batches = ['BATCH-2026-A', 'BATCH-2026-B', 'BATCH-2026-C'];
for ($b = 0; $b < 3; $b++) {
    for ($i = 1; $i <= 10; $i++) {
        $fn = $firstNames[$userIndex] . ' ' . $lastNames[array_rand($lastNames)];
        $empId = 'TEST_SGS' . str_pad(($b * 10) + $i, 3, '0', STR_PAD_LEFT);
        $uname = 'trainee_' . $batches[$b] . '_' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $dept = $departments[array_rand($departments)];
        $qual = $qualifications[array_rand($qualifications)];
        $doj = date('Y-m-d', strtotime('-' . rand(30, 180) . ' days'));
        $batchNum = $batches[$b];
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, employee_id, role, department, qualification, doj, email, batch_number) VALUES (?, ?, ?, ?, 'trainee', ?, ?, ?, ?, ?)");
        $stmt->execute([$uname, $defaultPass, $fn, $empId, $dept, $qual, $doj, strtolower(str_replace(' ','.',$fn)).'@syrmasgs.com', $batchNum]);
        $newUserIds['trainee'][] = $pdo->lastInsertId();
        $userIndex++;
    }
}
echo "30 trainees created (3 batches x 10).\n";

// ─── ASSIGNMENTS ───────────────────────────────────────────
// Existing modules: 1 (Induction), 2 (Practical-SDC), 3 (OTJ Shop Floor)
$modules = [1, 2, 3];
$statuses = ['completed', 'completed', 'in_progress', 'completed', 'in_progress'];
$assignmentIds = [];

foreach ($newUserIds['trainee'] as $idx => $traineeId) {
    // Each trainee gets assigned to one of the trainers
    $trainerId = $newUserIds['trainer'][$idx % count($newUserIds['trainer'])];
    $moduleId = $modules[$idx % count($modules)];
    $status = $statuses[$idx % count($statuses)];
    
    $assignedDate = date('Y-m-d', strtotime('-' . rand(60, 150) . ' days'));
    $completionDate = $status === 'completed' ? date('Y-m-d', strtotime($assignedDate . ' + ' . rand(20, 60) . ' days')) : null;
    
    $stmt = $pdo->prepare("INSERT INTO assignments (trainee_id, trainer_id, module_id, status, assigned_date, completion_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$traineeId, $trainerId, $moduleId, $status, $assignedDate, $completionDate]);
    $assignmentIds[] = ['id' => $pdo->lastInsertId(), 'trainee_id' => $traineeId, 'trainer_id' => $trainerId, 'module_id' => $moduleId, 'status' => $status];
}
echo count($assignmentIds) . " assignments created.\n";

// ─── TRAINING STAGES (OTJ + SDC) ──────────────────────────
$stageNames = [
    'otj' => ['Machine Operation Basics', 'Component Identification', 'Soldering Practice', 'Assembly Line Work', 'Quality Inspection OTJ'],
    'sdc' => ['Safety Fundamentals', 'ESD Awareness', '5S Methodology', 'Lean Manufacturing', 'PPE Usage Training']
];

foreach ($assignmentIds as $a) {
    // 2-4 stages per assignment
    $numStages = rand(2, 4);
    for ($s = 0; $s < $numStages; $s++) {
        $type = array_rand($stageNames);
        $stageName = $stageNames[$type][array_rand($stageNames[$type])];
        $hours = rand(4, 20);
        $certDate = date('Y-m-d', strtotime('-' . rand(10, 90) . ' days'));
        $remarks = ['Good progress', 'Needs more practice', 'Competency achieved', 'Satisfactory performance', 'Excellent understanding'][array_rand([0,1,2,3,4])];
        
        $stmt = $pdo->prepare("INSERT INTO training_stages (assignment_id, type, stage_name, man_hours, certified_date, remarks, trainer_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$a['id'], $type, $stageName, $hours, $certDate, $remarks, $a['trainer_id']]);
    }
}
echo "Training stages created.\n";

// ─── EXAM RESULTS ──────────────────────────────────────────
// Existing exams: 1 (5S Test), 2 (Safety Test), 3 (PPE Test), 4 (ESD Test)
$examIds = [1, 2, 3, 4];

foreach ($newUserIds['trainee'] as $traineeId) {
    // Each trainee takes 2-4 random exams
    $numExams = rand(2, 4);
    $takenExams = array_rand(array_flip($examIds), min($numExams, count($examIds)));
    if (!is_array($takenExams)) $takenExams = [$takenExams];
    
    foreach ($takenExams as $examId) {
        $score = rand(35, 100);
        $passingScore = 70;
        $status = $score >= $passingScore ? 'pass' : 'fail';
        $examDate = date('Y-m-d H:i:s', strtotime('-' . rand(5, 90) . ' days'));
        
        $stmt = $pdo->prepare("INSERT INTO exam_results (trainee_id, exam_id, score, max_score, status, exam_date) VALUES (?, ?, ?, 100, ?, ?)");
        $stmt->execute([$traineeId, $examId, $score, $status, $examDate]);
    }
}
echo "Exam results created.\n";

// ─── INDUCTION SCORES ──────────────────────────────────────
foreach ($newUserIds['trainee'] as $idx => $traineeId) {
    $trainerId = $newUserIds['trainer'][$idx % count($newUserIds['trainer'])];
    $stmt = $pdo->prepare("INSERT INTO induction_scores (trainee_id, topic_1_score, topic_2_score, topic_3_score, topic_4_score, topic_5_score, topic_6_score, trainer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $traineeId,
        rand(50, 100), rand(50, 100), rand(50, 100),
        rand(50, 100), rand(50, 100), rand(50, 100),
        $trainerId
    ]);
}
echo "Induction scores created.\n";

// ─── FEEDBACK ──────────────────────────────────────────────
$ratings = ['A', 'B', 'C', 'D'];
$comments = [
    'Training was very informative and well-structured.',
    'The practical sessions were extremely helpful.',
    'Could improve on time management during sessions.',
    'Excellent trainer with deep knowledge.',
    'More hands-on practice would be beneficial.',
    'Good coverage of safety protocols.',
    'The ESD training was particularly useful.',
    'Well-organized training schedule.',
    'Interactive and engaging sessions.',
    'Some topics could be covered in more depth.'
];

foreach ($newUserIds['trainee'] as $idx => $traineeId) {
    if (rand(0, 1) == 1) continue; // ~50% give feedback
    
    $aInfo = $assignmentIds[$idx] ?? null;
    $aId = $aInfo ? $aInfo['id'] : null;
    
    $stmt = $pdo->prepare("INSERT INTO feedback (assignment_id, trainee_id, rating_overall, rating_learning_skill, rating_learning_knowledge, rating_learning_attitude, rating_explanation, rating_improvement, rating_time, comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $aId, $traineeId,
        $ratings[rand(0, 2)], $ratings[rand(0, 2)], $ratings[rand(0, 2)],
        $ratings[rand(0, 2)], $ratings[rand(0, 2)], $ratings[rand(0, 2)],
        $ratings[rand(0, 2)], $comments[array_rand($comments)]
    ]);
}
echo "Feedback created.\n";

// ─── INDUCTION CHECKLIST PROGRESS ──────────────────────────
$checklistItems = $pdo->query("SELECT id FROM induction_checklist")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($checklistItems)) {
    foreach ($newUserIds['trainee'] as $idx => $traineeId) {
        $trainerId = $newUserIds['trainer'][$idx % count($newUserIds['trainer'])];
        $completePct = rand(40, 100); // percentage of checklist items completed
        $numToComplete = intval(count($checklistItems) * $completePct / 100);
        $itemsToComplete = array_slice($checklistItems, 0, $numToComplete);
        
        foreach ($itemsToComplete as $clId) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO trainee_checklist_progress (trainee_id, checklist_id, is_done, trainer_id, completed_at) VALUES (?, ?, 1, ?, NOW())");
            $stmt->execute([$traineeId, $clId, $trainerId]);
        }
    }
    echo "Checklist progress created.\n";
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// Final count
echo "\n=== FINAL COUNTS ===\n";
foreach (['users','assignments','training_modules','exams','exam_results','training_stages','feedback','induction_scores'] as $t) {
    $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo "$t: $c\n";
}

echo "\n✅ Seed complete! All 50 users created with full test data.\n";
echo "Login credentials: username/password (e.g., trainer_01/password, trainee_BATCH-2026-A_01/password, manager_01/password, admin_01/password)\n";
