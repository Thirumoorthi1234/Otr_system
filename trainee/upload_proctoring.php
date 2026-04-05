<?php
// trainee/upload_proctoring.php — Asynchronous background capture upload
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole('trainee');

$trainee_id = $_SESSION['user_id'];
$data       = json_decode(file_get_contents('php://input'), true);

if (!isset($data['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No image data']);
    exit();
}

$img = $data['image'];
$img = str_replace('data:image/jpeg;base64,', '', $img);
$img = str_replace(' ', '+', $img);
$fileData = base64_decode($img);

// Create directory if not exists
$dir = '../uploads/proctoring/' . $trainee_id . '/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$fileName = time() . '.jpg';
$filePath = $dir . $fileName;

if (file_put_contents($filePath, $fileData)) {
    // Save to DB
    $dbPath = 'uploads/proctoring/' . $trainee_id . '/' . $fileName;
    $stmt = $pdo->prepare("INSERT INTO session_proctoring (trainee_id, photo_path) VALUES (?, ?)");
    $stmt->execute([$trainee_id, $dbPath]);
    
    echo json_encode(['success' => true, 'path' => $dbPath]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}
