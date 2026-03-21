<?php
// api/unlock_assignment.php
require_once '../includes/config.php';
session_start();

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'trainer' && $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $assignment_id = $data['assignment_id'] ?? null;

    if ($assignment_id) {
        $stmt = $pdo->prepare("UPDATE assignments SET is_locked = 0, proctor_alerts = 0 WHERE id = ?");
        $stmt->execute([$assignment_id]);
        
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false]);
