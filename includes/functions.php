<?php
// includes/functions.php

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check role and redirect if unauthorized
 */
function checkRole($allowedRoles) {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: " . BASE_URL . "index.php?error=unauthorized");
        exit();
    }
}

/**
 * Sanitize output
 */
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Get active class for sidebar
 */
function isActive($page) {
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}

/**
 * Adds a notification for a user
 */
function addNotification($user_id, $title, $message, $type = 'info', $link = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $type, $link]);
}
?>
