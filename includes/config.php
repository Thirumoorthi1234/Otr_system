<?php
// includes/config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'otr_system');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global constants
define('SITE_NAME', 'Digital OTR System');

// Language support
require_once 'language.php';

// Dynamic BASE_URL detection
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir = str_replace(basename($script_name), '', $script_name);
$dynamic_base = $protocol . "://" . $host . $base_dir;

// Strip role-based and system subdirectories so BASE_URL always points to /otr/
$subdirs = ['/admin/', '/trainee/', '/trainer/', '/management/', '/includes/'];
foreach ($subdirs as $dir) {
    if (strpos($dynamic_base, $dir) !== false) {
        $dynamic_base = substr($dynamic_base, 0, strpos($dynamic_base, $dir)) . '/';
        break;
    }
}

define('BASE_URL', $dynamic_base);
?>
