<?php
// api/delete_ojt_photo.php — Delete OJT Evidence
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$photo_id = $input['id'] ?? null;

if (!$photo_id) {
    echo json_encode(['success' => false, 'error' => 'Photo ID is required.']);
    exit;
}

// Get the photo path to delete from disk
$stmt = $pdo->prepare("SELECT photo_path, trainee_id FROM ojt_evidence WHERE id = ?");
$stmt->execute([$photo_id]);
$photo = $stmt->fetch();

if (!$photo) {
    echo json_encode(['success' => false, 'error' => 'Photo not found.']);
    exit;
}

// Security: Only the trainee who uploaded it (or an admin) can delete it
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $photo['trainee_id']) {
    echo json_encode(['success' => false, 'error' => 'Access Denied: You cannot delete this photo.']);
    exit;
}

// Delete file from disk
$absolutePath = __DIR__ . '/../' . $photo['photo_path'];
if (file_exists($absolutePath)) {
    unlink($absolutePath);
}

// Delete record from database
$stmt = $pdo->prepare("DELETE FROM ojt_evidence WHERE id = ?");
if ($stmt->execute([$photo_id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
