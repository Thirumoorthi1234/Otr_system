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

/**
 * Generate a role-based employee ID automatically
 */
function generateEmployeeId($pdo, $role) {
    $prefixes = [
        'admin'      => 'ADM',
        'management' => 'MGT',
        'trainer'    => 'TRN',
        'trainee'    => 'SGS'
    ];
    $prefix = $prefixes[$role] ?? 'EMP';
    
    // Find the latest numeric ID with this prefix
    // We use a regex or string manipulation to find the highest number
    $stmt = $pdo->prepare("SELECT employee_id FROM users WHERE employee_id LIKE ? ORDER BY LENGTH(employee_id) DESC, employee_id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastId = $stmt->fetchColumn();
    
    if ($lastId) {
        // Extract the numeric part (everything after the prefix)
        $numPart = substr($lastId, strlen($prefix));
        if (is_numeric($numPart)) {
            $newNum = (int)$numPart + 1;
        } else {
            // Fallback if the format was different
            $newNum = 1;
        }
    } else {
        $newNum = 1;
    }
    
    return $prefix . str_pad($newNum, 3, '0', STR_PAD_LEFT);
}

?>
