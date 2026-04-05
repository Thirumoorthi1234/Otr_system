<?php
// trainee/submit_exam.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

checkRole(['trainee']);

$exam_id = $_POST['exam_id'];
$trainee_id = $_SESSION['user_id'];

// Get Questions to verify answers
$stmt = $pdo->prepare("SELECT id, correct_option FROM questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

$total = count($questions);
$correct = 0;

foreach ($questions as $q) {
    if (isset($_POST['q'.$q['id']]) && $_POST['q'.$q['id']] === $q['correct_option']) {
        $correct++;
    }
}

$score = $total > 0 ? round(($correct / $total) * 100) : 0;

// Fetch Exam Passing Criteria
$stmt = $pdo->prepare("SELECT passing_score FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$passing = $stmt->fetchColumn();

$status = $score >= $passing ? 'pass' : 'fail';

// Save Result
$stmt = $pdo->prepare("INSERT INTO exam_results (trainee_id, exam_id, score, max_score, status, exam_date) VALUES (?, ?, ?, 100, ?, NOW())");
$stmt->execute([$trainee_id, $exam_id, $score, $status]);
$result_id = $pdo->lastInsertId();

// ── Feature 8: Save per-question answers ────────────────────────────────────
try {
    $ansStmt = $pdo->prepare("INSERT INTO exam_result_answers (result_id, question_id, trainee_answer, is_correct) VALUES (?, ?, ?, ?)");
    foreach ($questions as $q) {
        $trainee_answer = $_POST['q' . $q['id']] ?? null;
        $is_correct     = ($trainee_answer !== null && $trainee_answer === $q['correct_option']) ? 1 : 0;
        $ansStmt->execute([$result_id, $q['id'], $trainee_answer, $is_correct]);
    }
} catch (Exception $e) {
    // Table may not exist yet — non-critical; migration needed
    error_log('exam_result_answers insert failed: ' . $e->getMessage());
}
// ────────────────────────────────────────────────────────────────────────────

// Get Module ID for this exam
$stmt = $pdo->prepare("SELECT module_id FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$module_id = $stmt->fetchColumn();

// --- Intelligent Module Completion Logic ---
if ($status == 'pass') {
    // 1. Count total exams for this module
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE module_id = ?");
    $stmt->execute([$module_id]);
    $total_exams = (int)$stmt->fetchColumn();

    // 2. Count distinct exams passed by this trainee for this module
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT exam_id) 
        FROM exam_results 
        WHERE trainee_id = ? AND status = 'pass' AND exam_id IN (SELECT id FROM exams WHERE module_id = ?)
    ");
    $stmt->execute([$trainee_id, $module_id]);
    $passed_exams = (int)$stmt->fetchColumn();

    // 3. Check if feedback is submitted
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE trainee_id = ?");
    $stmt->execute([$trainee_id]);
    $feedback_submitted = ($stmt->fetchColumn() > 0);

    // 4. Mark module as completed only if all exams are passed AND feedback is submitted
    if ($passed_exams >= $total_exams && $feedback_submitted) {
        $stmt = $pdo->prepare("UPDATE assignments SET status = 'completed', completion_date = CURDATE() WHERE trainee_id = ? AND module_id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE assignments SET status = 'in_progress' WHERE trainee_id = ? AND module_id = ?");
    }
    $stmt->execute([$trainee_id, $module_id]);
} else {
    // If failed, ensure it's at least in_progress
    $stmt = $pdo->prepare("UPDATE assignments SET status = 'in_progress' WHERE trainee_id = ? AND module_id = ? AND status = 'not_started'");
    $stmt->execute([$trainee_id, $module_id]);
}

header("Location: results.php?id=$result_id");
exit();
