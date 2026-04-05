<?php
// api/save_ojt_photo.php — Save OJT evidence photo (base64 from camera)
require_once '../includes/config.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'trainee') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$imageData    = $data['image'] ?? '';
$assignment_id = (int)($data['assignment_id'] ?? 0);
$caption      = trim($data['caption'] ?? 'OJT Activity Evidence');
$trainee_id   = $_SESSION['user_id'];

if (!$imageData || !$assignment_id) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

// Verify assignment belongs to this trainee
$stmt = $pdo->prepare("SELECT id FROM assignments WHERE id=? AND trainee_id=?");
$stmt->execute([$assignment_id, $trainee_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Assignment not found']);
    exit();
}

// Decode base64
$imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
$decodedImage = base64_decode($imageData);
if (!$decodedImage) {
    echo json_encode(['success' => false, 'error' => 'Invalid image data']);
    exit();
}

$uploadDir = __DIR__ . '/../assets/uploads/ojt_evidence/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

$filename  = 'ojt_' . $trainee_id . '_' . $assignment_id . '_' . time() . '.jpg';
$filepath  = $uploadDir . $filename;
$webPath   = 'assets/uploads/ojt_evidence/' . $filename;

if (!file_put_contents($filepath, $decodedImage)) {
    echo json_encode(['success' => false, 'error' => 'File write failed']);
    exit();
}

// Save record
$stmt = $pdo->prepare("INSERT INTO ojt_evidence (assignment_id, trainee_id, photo_path, caption) VALUES (?, ?, ?, ?)");
$stmt->execute([$assignment_id, $trainee_id, $webPath, $caption]);

echo json_encode(['success' => true, 'path' => $webPath, 'id' => $pdo->lastInsertId()]);
