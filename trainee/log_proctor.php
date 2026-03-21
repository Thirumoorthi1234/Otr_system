<?php
// trainee/log_proctor.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
checkRole('trainee');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result_id = $_SESSION['current_result_id'] ?? null;
    $type = $_POST['type'] ?? 'other';
    $details = $_POST['details'] ?? '';

    if ($result_id) {
        $stmt = $pdo->prepare("INSERT INTO proctor_logs (result_id, activity_type, details) VALUES (?, ?, ?)");
        $stmt->execute([$result_id, $type, $details]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No active exam result found']);
    }
}
