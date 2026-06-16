<?php
// api/approve_ojt_evidence.php — Trainer approves or rejects OJT evidence
require_once '../includes/config.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !in_array($_SESSION['role'], ['trainer', 'admin', 'management'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data   = json_decode(file_get_contents('php://input'), true);
$ev_id  = (int)($data['id'] ?? 0);
$action = $data['action'] ?? '';
$note   = trim($data['note'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$ev_id || !in_array($action, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

// Verify the evidence belongs to a trainee assigned to this trainer
// (or user is admin/management, who can approve anything)
if ($_SESSION['role'] === 'trainer') {
    $check = $pdo->prepare("
        SELECT oe.id FROM ojt_evidence oe
        JOIN assignments a ON oe.assignment_id = a.id
        WHERE oe.id = ? AND a.trainer_id = ?
    ");
    $check->execute([$ev_id, $user_id]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Evidence not found or not your trainee']);
        exit();
    }
}

// Update the evidence record
$stmt = $pdo->prepare("
    UPDATE ojt_evidence
    SET approved = ?, approved_by = ?, approved_at = NOW(), trainer_note = ?
    WHERE id = ?
");
$stmt->execute([$action, $user_id, $note ?: null, $ev_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Record not found']);
    exit();
}

// Fetch trainee info for notification
$infoStmt = $pdo->prepare("
    SELECT oe.trainee_id, m.title as module_name 
    FROM ojt_evidence oe 
    JOIN assignments a ON oe.assignment_id = a.id 
    JOIN training_modules m ON a.module_id = m.id 
    WHERE oe.id = ?
");
$infoStmt->execute([$ev_id]);
if ($info = $infoStmt->fetch()) {
    $statusText = $action === 'approved' ? 'Approved' : 'Rejected';
    $type = $action === 'approved' ? 'success' : 'warning';
    $msg = "Your OJT evidence for '{$info['module_name']}' was {$statusText}.";
    if ($note) $msg .= " Note: $note";
    addNotification($info['trainee_id'], "Evidence $statusText", $msg, $type, "trainee/ojt_camera.php");
}

echo json_encode(['success' => true, 'action' => $action]);
