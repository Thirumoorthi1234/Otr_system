<?php
// index.php — Dual login: Standard (Employee ID + Password) & Trainee OTP
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/sms_config.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: " . $role . "/dashboard.php");
    exit();
}

$tab     = $_GET['tab']     ?? 'standard';
$step    = $_GET['step']    ?? 'send';
$dev_otp = $_GET['dev_otp'] ?? '';
$error   = $_GET['error']   ?? '';
$detail  = $_GET['detail']  ?? '';

// Mask mobile stored in session for display (e.g. 98XXXXXX76)
$maskedMobile = $_SESSION['otp_send_mobile'] ?? '';
$otpSentAt    = $_SESSION['otp_sent_at'] ?? 0;
$resendSecsLeft = max(0, 60 - (time() - $otpSentAt));
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Digital OTR System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&family=Noto+Sans+Devanagari:wght@400;600;700&family=Noto+Sans+Tamil:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="assets/img/profiles/favicon.svg">
    <style>
        /* ── Tab switcher ─────────────────────────────────── */
        .login-tabs { display:flex; background:#f1f5f9; border-radius:14px; padding:5px; margin-bottom:28px; gap:4px; }
        .login-tab-btn { flex:1; padding:11px 8px; border:none; background:transparent; border-radius:10px; font-weight:700; font-size:0.9rem; cursor:pointer; transition:all 0.25s; color:#64748b; display:flex; align-items:center; justify-content:center; gap:7px; }
        .login-tab-btn.active { background:#fff; color:var(--primary,#0b70b7); box-shadow:0 2px 8px rgba(0,0,0,0.08); }
        .login-tab-btn:hover:not(.active) { background:rgba(255,255,255,0.6); }

        /* ── Alerts ───────────────────────────────────────── */
        .alert { padding:12px 16px; border-radius:10px; margin-bottom:18px; font-weight:600; font-size:0.9rem; display:flex; align-items:center; gap:10px; }
        .alert-danger  { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .alert-warning { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
        .alert-success { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .alert-info    { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }

        /* ── Dev Mode OTP Banner ──────────────────────────── */
        .otp-dev-banner { background:linear-gradient(135deg,#fef3c7,#fde68a); border:2px dashed #f59e0b; border-radius:12px; padding:14px 18px; margin-bottom:20px; text-align:center; }
        .otp-dev-banner .otp-code { font-size:2.2rem; letter-spacing:10px; color:#92400e; font-family:'Courier New',monospace; display:block; font-weight:900; margin:6px 0; }
        .otp-dev-banner small { color:#78350f; font-size:0.78rem; font-weight:600; }

        /* ── OTP Input ────────────────────────────────────── */
        .otp-input { font-size:2rem; letter-spacing:12px; text-align:center; font-weight:800; font-family:'Outfit',sans-serif; border:2px solid #e2e8f0; border-radius:12px; padding:16px 14px; width:100%; box-sizing:border-box; transition:border-color 0.2s; background:#fafbff; }
        .otp-input:focus { border-color:var(--primary,#0b70b7); outline:none; box-shadow:0 0 0 4px rgba(11,112,183,0.1); }

        /* ── Identifier Input ─────────────────────────────── */
        .id-input { font-size:1.1rem; letter-spacing:3px; text-align:center; }

        /* ── Resend Timer ─────────────────────────────────── */
        .resend-area { text-align:center; margin-top:16px; }
        .resend-timer { color:#94a3b8; font-size:0.88rem; font-weight:600; }
        .resend-btn-link { color:var(--primary,#0b70b7); font-size:0.88rem; font-weight:700; text-decoration:none; cursor:pointer; background:none; border:none; padding:0; }
        .resend-btn-link:hover { text-decoration:underline; }

        /* ── Mobile info badge ────────────────────────────── */
        .mobile-badge { background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:10px 15px; display:flex; align-items:center; gap:10px; margin-bottom:18px; font-size:0.88rem; font-weight:700; color:#1d4ed8; }
        .mobile-badge i { font-size:1.2rem; }

        /* ── Lang switcher ────────────────────────────────── */
        .login-lang-switch { position:fixed; top:20px; right:20px; z-index:100; }
        .lang-btn { background:rgba(255,255,255,0.9); border:1px solid #ddd; padding:8px 15px; border-radius:8px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:8px; box-shadow:0 4px 6px rgba(0,0,0,0.05); transition:all 0.3s; }
        .lang-btn:hover { background:#fff; transform:translateY(-2px); }
        .lang-dropdown { position:absolute; top:100%; right:0; margin-top:5px; background:#fff; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.1); width:180px; display:none; overflow:hidden; border:1px solid #eee; }
        .lang-dropdown.open { display:block; }
        .lang-item { display:block; padding:12px 15px; text-decoration:none; color:#333; font-size:14px; border-bottom:1px solid #f5f5f5; transition:background 0.2s; }
        .lang-item:hover { background:#f8fafc; }
        .lang-item.active { color:var(--primary); font-weight:700; background:#f0f7ff; }

        /* ── SMS Provider notice ──────────────────────────── */
        .sms-notice { font-size:0.76rem; color:#94a3b8; text-align:center; margin-top:6px; }
    </style>
</head>
<body class="lang-<?php echo getCurrentLang(); ?>">

<!-- Language Switcher -->
<div class="login-lang-switch">
    <button class="lang-btn" onclick="event.stopPropagation(); document.getElementById('loginLangMenu').classList.toggle('open')">
        <i class="fas fa-language" style="font-size:1.2rem;color:var(--primary);"></i>
        <span><?php echo __('switch_language'); ?></span>
    </button>
    <div class="lang-dropdown" id="loginLangMenu">
        <?php global $available_languages; foreach ($available_languages as $code => $name): ?>
        <a href="?lang=<?php echo $code; ?>" class="lang-item <?php echo getCurrentLang() == $code ? 'active' : ''; ?>">
            <?php echo $name; ?>
            <?php if (getCurrentLang() == $code): ?><i class="fas fa-check" style="float:right;margin-top:3px;"></i><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="login-bg"></div>

<div class="login-wrap">
    <div class="login-card">
        <!-- Logo -->
        <div class="login-logo-plain">
            <img src="assets/img/profiles/logo.svg" alt="Syrma SGS">
        </div>

        <div style="text-align:center;">
            <h1 class="login-title"><?php echo __('login_title'); ?></h1>
            <p class="login-subtitle"><?php echo __('login_subtitle'); ?></p>
        </div>

        <!-- ── Alerts ── -->
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php
            switch ($error) {
                case 'invalid_credentials': echo __('invalid_credentials'); break;
                case 'not_found':   echo 'Aadhaar / Mobile number not found. Please contact HR.'; break;
                case 'no_mobile':   echo 'No mobile number linked to this account. Contact HR to update your profile.'; break;
                case 'invalid_otp': echo 'Invalid OTP. Please check and try again.'; break;
                case 'otp_expired': echo 'OTP has expired. Please request a new one.'; break;
                case 'session_expired': echo 'Session expired. Please start again.'; break;
                case 'sms_failed':  echo 'Could not send SMS. ' . htmlspecialchars(urldecode($detail)); break;
                case 'too_soon':    echo 'Please wait 60 seconds before requesting a new OTP.'; break;
                case 'inactive':    echo 'Your account is inactive. Contact your administrator.'; break;
                default: echo __('access_denied');
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['resent'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> New OTP sent successfully to your mobile.
        </div>
        <?php endif; ?>

        <!-- ── Tab Switcher ── -->
        <div class="login-tabs">
            <button class="login-tab-btn <?php echo $tab === 'standard' ? 'active' : ''; ?>" onclick="switchTab('standard')" id="tabBtnStandard">
                <i class="fas fa-id-badge"></i> Employee Login
            </button>
            <button class="login-tab-btn <?php echo $tab === 'otp' ? 'active' : ''; ?>" onclick="switchTab('otp')" id="tabBtnOtp">
                <i class="fas fa-mobile-alt"></i> Trainee OTP Login
            </button>
        </div>

        <!-- ══════════════════════════════════════════════════
             STANDARD LOGIN
        ══════════════════════════════════════════════════ -->
        <div id="tab-standard" style="<?php echo $tab !== 'standard' ? 'display:none;' : ''; ?>">
            <form action="includes/auth.php" method="POST">
                <div class="form-group">
                    <label class="form-label"><?php echo __('employee_id'); ?></label>
                    <input type="text" name="employee_id" class="form-control"
                           placeholder="<?php echo __('enter_employee_id'); ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __('password'); ?></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary" style="width:100%;margin-top:10px;padding:15px;">
                    <i class="fas fa-right-to-bracket"></i> <?php echo __('sign_in'); ?>
                </button>
            </form>
        </div>

        <!-- ══════════════════════════════════════════════════
             TRAINEE OTP LOGIN
        ══════════════════════════════════════════════════ -->
        <div id="tab-otp" style="<?php echo $tab !== 'otp' ? 'display:none;' : ''; ?>">

            <?php if ($step === 'send'): ?>
            <!-- ─── Step 1: Enter Aadhaar or Mobile ─── -->
            <div style="text-align:center; margin-bottom:22px;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#0369a1,#7c3aed);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;box-shadow:0 4px 15px rgba(3,105,161,0.25);">
                    <i class="fas fa-fingerprint" style="color:#fff;font-size:1.5rem;"></i>
                </div>
                <div style="font-weight:800;color:#0f172a;font-size:1.05rem;">Enter your Aadhaar or Mobile</div>
                <div style="color:#64748b;font-size:0.85rem;margin-top:4px;">An OTP will be sent to your registered mobile number</div>
            </div>

            <form action="includes/auth.php" method="POST">
                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-fingerprint" style="color:#7c3aed;"></i>
                        Aadhaar Number or Mobile Number
                    </label>
                    <input type="text" name="identifier" class="form-control id-input"
                           placeholder="Enter 12-digit Aadhaar or 10-digit Mobile"
                           maxlength="12" inputmode="numeric"
                           oninput="this.value=this.value.replace(/[^0-9]/,'')"
                           required autofocus>
                    <div class="sms-notice">
                        <i class="fas fa-shield-alt"></i>
                        If Aadhaar is entered, OTP goes to Aadhaar-linked mobile &nbsp;|&nbsp;
                        Powered by <?php echo SMS_PROVIDER !== 'mock' ? ucfirst(SMS_PROVIDER) : 'Dev Mode'; ?>
                    </div>
                </div>
                <button type="submit" name="send_otp" class="btn btn-primary"
                        style="width:100%;padding:15px;margin-top:10px;background:linear-gradient(135deg,#0369a1,#0b70b7);font-size:1rem;font-weight:800;">
                    <i class="fas fa-paper-plane"></i>&nbsp; Send OTP
                </button>
            </form>

            <?php elseif ($step === 'verify'): ?>
            <!-- ─── Step 2: Enter OTP ─── -->

            <?php if ($dev_otp): ?>
            <!-- Dev mode: show OTP on screen -->
            <div class="otp-dev-banner">
                <small><i class="fas fa-flask"></i> Development Mode — OTP (no SMS sent)</small>
                <span class="otp-code"><?php echo htmlspecialchars($dev_otp); ?></span>
                <small>Configure Fast2SMS API key to send real SMS</small>
            </div>
            <?php endif; ?>

            <!-- Sent-to notice -->
            <?php if ($maskedMobile): ?>
            <div class="mobile-badge">
                <i class="fas fa-sms"></i>
                <div>
                    OTP sent to <strong style="letter-spacing:2px;"><?php echo htmlspecialchars($maskedMobile); ?></strong>
                    <span style="font-size:0.75rem;font-weight:500;color:#3b82f6;display:block;">Valid for <?php echo OTP_EXPIRY_MINUTES; ?> minutes</span>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-sms"></i> OTP sent to your registered mobile. Valid for <?php echo OTP_EXPIRY_MINUTES; ?> minutes.
            </div>
            <?php endif; ?>

            <form action="includes/auth.php" method="POST" id="otpVerifyForm">
                <div class="form-group">
                    <label class="form-label" style="text-align:center;display:block;font-size:0.82rem;text-transform:uppercase;letter-spacing:1px;font-weight:800;color:#64748b;">
                        Enter <?php echo OTP_DIGITS; ?>-Digit OTP
                    </label>
                    <input type="text" name="otp_code" id="otpInput" class="otp-input"
                           maxlength="<?php echo OTP_DIGITS; ?>"
                           inputmode="numeric"
                           pattern="[0-9]{<?php echo OTP_DIGITS; ?>}"
                           placeholder="<?php echo str_repeat('● ', OTP_DIGITS); ?>"
                           autocomplete="one-time-code"
                           required autofocus>
                </div>
                <button type="submit" name="verify_otp" id="verifyBtn"
                        class="btn btn-primary"
                        style="width:100%;padding:15px;margin-top:10px;background:linear-gradient(135deg,#059669,#10b981);font-size:1rem;font-weight:800;">
                    <i class="fas fa-check-circle"></i>&nbsp; Verify & Login
                </button>
            </form>

            <!-- Resend OTP -->
            <div class="resend-area">
                <span id="resendTimerWrap" class="resend-timer" <?php echo $resendSecsLeft > 0 ? '' : 'style="display:none;"'; ?>>
                    <i class="fas fa-clock"></i> Resend OTP in <span id="resendCountdown"><?php echo $resendSecsLeft; ?></span>s
                </span>
                <form action="includes/auth.php" method="POST" id="resendForm"
                      style="display:inline; <?php echo $resendSecsLeft > 0 ? 'display:none;' : ''; ?>" id="resendFormWrap">
                    <button type="submit" name="resend_otp" class="resend-btn-link">
                        <i class="fas fa-redo-alt"></i> Resend OTP
                    </button>
                </form>
            </div>

            <div style="text-align:center;margin-top:14px;">
                <a href="index.php?tab=otp" style="color:#94a3b8;font-size:0.82rem;font-weight:600;text-decoration:none;">
                    <i class="fas fa-arrow-left"></i> Use a different number
                </a>
            </div>

            <?php endif; ?>
        </div><!-- /tab-otp -->
    </div><!-- /login-card -->

    <!-- Powered by -->
    <div class="login-powered">
        <div class="pb-label"><?php echo __('powered_by'); ?></div>
        <div class="pb-logo-wrap"><img src="assets/img/profiles/powered_by.svg" alt="Learnlike"></div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
// ── Tab switcher ─────────────────────────────────────────────
function switchTab(name) {
    document.getElementById('tab-standard').style.display = name === 'standard' ? '' : 'none';
    document.getElementById('tab-otp').style.display      = name === 'otp'      ? '' : 'none';
    document.getElementById('tabBtnStandard').classList.toggle('active', name === 'standard');
    document.getElementById('tabBtnOtp').classList.toggle('active', name === 'otp');
    const url = new URL(window.location);
    url.searchParams.set('tab', name);
    if (name === 'standard') { url.searchParams.delete('step'); url.searchParams.delete('dev_otp'); }
    window.history.replaceState({}, '', url);
}

// ── Auto-submit when OTP digits filled ───────────────────────
const otpInput = document.getElementById('otpInput');
if (otpInput) {
    otpInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, <?php echo OTP_DIGITS; ?>);
        if (this.value.length === <?php echo OTP_DIGITS; ?>) {
            // Slight delay so user can see what they typed
            setTimeout(() => document.getElementById('otpVerifyForm').submit(), 300);
        }
    });
}

// ── Resend countdown timer ────────────────────────────────────
let resendSecs = <?php echo $resendSecsLeft; ?>;
if (resendSecs > 0) {
    const timerWrap = document.getElementById('resendTimerWrap');
    const countEl   = document.getElementById('resendCountdown');
    const formWrap  = document.getElementById('resendFormWrap');
    const interval  = setInterval(() => {
        resendSecs--;
        if (countEl) countEl.textContent = resendSecs;
        if (resendSecs <= 0) {
            clearInterval(interval);
            if (timerWrap) timerWrap.style.display = 'none';
            if (formWrap)  formWrap.style.display  = 'inline';
        }
    }, 1000);
}

// ── Language dropdown close ───────────────────────────────────
document.querySelector('#loginLangMenu').addEventListener('click', e => e.stopPropagation());
document.addEventListener('click', () => document.getElementById('loginLangMenu').classList.remove('open'));
</script>
</body>
</html>
