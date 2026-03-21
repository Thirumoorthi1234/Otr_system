<?php
// includes/auth.php
require_once 'config.php';
require_once 'functions.php';

if (isset($_POST['login'])) {
    $employee_id = $_POST['employee_id'];
    $password = $_POST['password'];
    
    // Login by Employee ID instead of username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['photo_path'] = $user['photo_path'];
        $_SESSION['employee_id'] = $user['employee_id'];
        $_SESSION['batch_number'] = $user['batch_number'] ?? null;
        
        switch ($user['role']) {
            case 'admin':
                header("Location: " . BASE_URL . "admin/dashboard.php");
                break;
            case 'trainer':
                header("Location: " . BASE_URL . "trainer/dashboard.php");
                break;
            case 'trainee':
                header("Location: " . BASE_URL . "trainee/dashboard.php");
                break;
            case 'management':
                header("Location: " . BASE_URL . "management/dashboard.php");
                break;
        }
        exit();
    } else {
        header("Location: " . BASE_URL . "index.php?error=invalid_credentials");
        exit();
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>
