<?php
// index.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: " . $role . "/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Digital OTR System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-bg"></div>

    <div class="login-wrap">
        <div class="login-card">
            <div class="login-logo-plain">
                <img src="assets/img/profiles/logo.svg" alt="Syrma SGS">
            </div>
            
            <div style="text-align: center;">
                <h1 class="login-title">Digital OTR System</h1>
                <p class="login-subtitle">Enter your Employee ID and password to access your portal</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                        if ($_GET['error'] == 'invalid_credentials') echo "Invalid Employee ID or password.";
                        else echo "Access denied. Please try again.";
                    ?>
                </div>
            <?php endif; ?>
<!-- hii hello -->
            <form action="includes/auth.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Employee ID</label>
                    <input type="text" name="employee_id" class="form-control" placeholder="Enter your Employee ID" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 15px;">
                    <i class="fas fa-right-to-bracket"></i> SIGN IN TO PORTAL
                </button>
            </form>
        </div>
        <!-- Powered by badge flows natively below the card -->
        <div class="login-powered">
            <div class="pb-label">POWERED BY</div>
            <div class="pb-logo-wrap">
                <img src="assets/img/profiles/powered_by.svg" alt="Learnlike">
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
