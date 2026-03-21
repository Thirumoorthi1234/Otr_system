<?php
// api/lock_assignment.php
require_once '../includes/config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $trainee_id = $_SESSION['user_id'] ?? null;
    $exam_id = $data['exam_id'] ?? null;

    if ($trainee_id && $exam_id) {
        // Find the corresponding assignment for this trainee and exam (via module_id)
        $stmt = $pdo->prepare("
            UPDATE assignments a
            JOIN exams e ON a.module_id = e.module_id
            SET a.is_locked = 1, a.proctor_alerts = 3
            WHERE a.trainee_id = ? AND e.id = ?
        ");
        $stmt->execute([$trainee_id, $exam_id]);
        
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false]);
