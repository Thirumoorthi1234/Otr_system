<?php
// api/get_exam_answers.php — Return per-question answer breakdown for trainer review
require_once '../includes/config.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !in_array($_SESSION['role'], ['trainer', 'admin', 'management'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$result_id = (int)($_GET['result_id'] ?? 0);
if (!$result_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid result ID']);
    exit();
}

// Verify result exists
$result = $pdo->prepare("SELECT er.*, e.passing_score FROM exam_results er JOIN exams e ON er.exam_id = e.id WHERE er.id = ?");
$result->execute([$result_id]);
$result = $result->fetch();
if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Result not found']);
    exit();
}

// Get per-question answers with question details
$stmt = $pdo->prepare("
    SELECT era.*, 
           q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option
    FROM exam_result_answers era
    JOIN questions q ON era.question_id = q.id
    WHERE era.result_id = ?
    ORDER BY era.id ASC
");
$stmt->execute([$result_id]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$correct = count(array_filter($answers, fn($a) => $a['is_correct'] == 1));
$wrong   = count($answers) - $correct;

echo json_encode([
    'success' => true,
    'score'   => $result['score'],
    'correct' => $correct,
    'wrong'   => $wrong,
    'answers' => $answers
]);
