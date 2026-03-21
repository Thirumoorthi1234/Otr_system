<?php
// api/notifications.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll();
        
        $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $unread_stmt->execute([$user_id]);
        $unread_count = $unread_stmt->fetchColumn();
        
        echo json_encode([
            'notifications' => $notifications,
            'unread_count' => (int)$unread_count
        ]);
        break;

    case 'mark_read':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
        break;

    case 'mark_all_read':
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
        break;

    case 'unread_count':
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$user_id]);
        echo json_encode(['count' => (int)$stmt->fetchColumn()]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
