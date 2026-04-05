<?php
// includes/auth.php — Authentication: Standard login + OTP login
require_once 'config.php';
require_once 'functions.php';
require_once 'sms_helper.php';

// ═══════════════════════════════════════════════════════════
// 1. STANDARD LOGIN (Employee ID + Password)
// ═══════════════════════════════════════════════════════════
if (isset($_POST['login'])) {
    $employee_id = trim($_POST['employee_id']);
    $password    = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (isset($user['status']) && $user['status'] === 'inactive') {
            header("Location: " . BASE_URL . "index.php?error=inactive");
            exit();
        }
        _startSession($user);
        _redirectByRole($user['role']);
    } else {
        header("Location: " . BASE_URL . "index.php?error=invalid_credentials");
        exit();
    }
}

// ═══════════════════════════════════════════════════════════
// 2. OTP — SEND (Step 1: Trainee enters Aadhaar or Mobile)
// ═══════════════════════════════════════════════════════════
if (isset($_POST['send_otp'])) {
    $identifier = preg_replace('/[\s\-]/', '', trim($_POST['identifier'] ?? ''));

    if (empty($identifier)) {
        header("Location: " . BASE_URL . "index.php?tab=otp&error=empty_identifier");
        exit();
    }

    // Find trainee by Aadhaar OR Mobile (only active trainees)
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE role = 'trainee'
          AND status = 'active'
          AND (aadhar_number = ? OR mobile_number = ?)
        LIMIT 1
    ");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: " . BASE_URL . "index.php?tab=otp&error=not_found");
        exit();
    }

    // Determine which mobile to send OTP to:
    // If Aadhaar was entered → send to the Aadhaar-linked mobile stored in DB
    // If mobile was entered  → send to that mobile directly
    $sendToMobile = $user['mobile_number'] ?? null;

    if (empty($sendToMobile)) {
        header("Location: " . BASE_URL . "index.php?tab=otp&error=no_mobile");
        exit();
    }

    // Generate OTP
    $otp     = str_pad(random_int(0, (int)str_repeat('9', OTP_DIGITS)), OTP_DIGITS, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

    // Save OTP in DB
    $pdo->prepare("UPDATE users SET otp = ?, otp_expires_at = ? WHERE id = ?")
        ->execute([$otp, $expires, $user['id']]);

    // Send SMS
    $smsResult = sendOtpSMS($sendToMobile, $otp);

    if (!$smsResult['success']) {
        // SMS failed — tell the user
        $errMsg = urlencode('SMS failed: ' . $smsResult['message']);
        header("Location: " . BASE_URL . "index.php?tab=otp&error=sms_failed&detail={$errMsg}");
        exit();
    }

    // Store user_id in session for verification step
    $_SESSION['otp_user_id']     = $user['id'];
    $_SESSION['otp_send_mobile'] = preg_replace('/(\d{2})\d{6}(\d{2})/', '$1XXXXXX$2', $sendToMobile); // masked
    $_SESSION['otp_sent_at']     = time();

    // Build redirect — in dev mode, pass OTP in URL for on-screen display
    $redirectUrl = BASE_URL . "index.php?tab=otp&step=verify";
    if (!empty($smsResult['dev_otp'])) {
        $redirectUrl .= "&dev_otp=" . urlencode($smsResult['dev_otp']);
    }
    header("Location: $redirectUrl");
    exit();
}

// ═══════════════════════════════════════════════════════════
// 3. OTP — VERIFY (Step 2: Trainee enters the received OTP)
// ═══════════════════════════════════════════════════════════
if (isset($_POST['verify_otp'])) {
    $entered_otp = preg_replace('/\D/', '', trim($_POST['otp_code'] ?? ''));
    $user_id     = (int)($_SESSION['otp_user_id'] ?? 0);

    // Session expired
    if (!$user_id) {
        header("Location: " . BASE_URL . "index.php?tab=otp&error=session_expired");
        exit();
    }

    // Retrieve user — check OTP matches AND is not expired
    // Use PHP time comparison (avoids MySQL timezone issues)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND otp = ?");
    $stmt->execute([$user_id, $entered_otp]);
    $user = $stmt->fetch();

    if (!$user) {
        // Wrong OTP
        header("Location: " . BASE_URL . "index.php?tab=otp&step=verify&error=invalid_otp");
        exit();
    }

    // Check expiry in PHP (avoids MySQL timezone mismatch)
    $expiresAt = strtotime($user['otp_expires_at']);
    if (!$expiresAt || time() > $expiresAt) {
        // OTP expired — clear it
        $pdo->prepare("UPDATE users SET otp = NULL, otp_expires_at = NULL WHERE id = ?")->execute([$user_id]);
        header("Location: " . BASE_URL . "index.php?tab=otp&error=otp_expired");
        exit();
    }

    // ✅ OTP verified — clear OTP from DB, start session
    $pdo->prepare("UPDATE users SET otp = NULL, otp_expires_at = NULL WHERE id = ?")
        ->execute([$user['id']]);

    // Clear OTP session vars
    unset($_SESSION['otp_user_id'], $_SESSION['otp_send_mobile'], $_SESSION['otp_sent_at']);

    _startSession($user);
    header("Location: " . BASE_URL . "trainee/dashboard.php");
    exit();
}

// ═══════════════════════════════════════════════════════════
// 4. RESEND OTP
// ═══════════════════════════════════════════════════════════
if (isset($_POST['resend_otp'])) {
    $user_id = (int)($_SESSION['otp_user_id'] ?? 0);
    if (!$user_id) {
        header("Location: " . BASE_URL . "index.php?tab=otp&error=session_expired");
        exit();
    }

    // Rate limit: don't resend within 60 seconds
    $sentAt = $_SESSION['otp_sent_at'] ?? 0;
    if ((time() - $sentAt) < 60) {
        header("Location: " . BASE_URL . "index.php?tab=otp&step=verify&error=too_soon");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || empty($user['mobile_number'])) {
        header("Location: " . BASE_URL . "index.php?tab=otp&error=no_mobile");
        exit();
    }

    $otp     = str_pad(random_int(0, (int)str_repeat('9', OTP_DIGITS)), OTP_DIGITS, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
    $pdo->prepare("UPDATE users SET otp = ?, otp_expires_at = ? WHERE id = ?")->execute([$otp, $expires, $user_id]);

    $smsResult = sendOtpSMS($user['mobile_number'], $otp);
    $_SESSION['otp_sent_at'] = time();

    $redirectUrl = BASE_URL . "index.php?tab=otp&step=verify&resent=1";
    if (!empty($smsResult['dev_otp'])) {
        $redirectUrl .= "&dev_otp=" . urlencode($smsResult['dev_otp']);
    }
    header("Location: $redirectUrl");
    exit();
}

// ═══════════════════════════════════════════════════════════
// 5. LOGOUT
// ═══════════════════════════════════════════════════════════
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// ═══════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════
function _startSession(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['full_name']    = $user['full_name'];
    $_SESSION['role']         = $user['role'];
    $_SESSION['photo_path']   = $user['photo_path'] ?? '';
    $_SESSION['employee_id']  = $user['employee_id'];
    $_SESSION['batch_number'] = $user['batch_number'] ?? null;
}

function _redirectByRole(string $role): void {
    $map = [
        'admin'      => 'admin/dashboard.php',
        'trainer'    => 'trainer/dashboard.php',
        'trainee'    => 'trainee/dashboard.php',
        'management' => 'management/dashboard.php',
    ];
    header("Location: " . BASE_URL . ($map[$role] ?? 'index.php'));
    exit();
}
?>
