<?php
// settings.php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/layout.php';

if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    if (empty($full_name)) {
        $error_msg = "Full name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_id]);
            $_SESSION['full_name'] = $full_name; // Update session
            $success_msg = "Profile updated successfully!";
            addNotification($user_id, "Profile Updated", "Your profile information has been successfully updated.", "success");
        } catch (PDOException $e) {
            $error_msg = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_pw = $_POST['current_password'];
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];

    if ($new_pw !== $confirm_pw) {
        $error_msg = "New passwords do not match.";
    } elseif (strlen($new_pw) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (password_verify($current_pw, $user['password'])) {
            $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_pw, $user_id]);
            $success_msg = "Password changed successfully!";
            addNotification($user_id, "Security Alert", "Your password has been changed.", "warning");
        } else {
            $error_msg = "Incorrect current password.";
        }
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();

renderHeader("Settings");
renderSidebar($_SESSION['role']);
?>

<div class="animate-in">
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Profile Settings -->
        <div class="card">
            <h3 style="margin-bottom: 25px;"><i class="fas fa-user-edit" style="color: var(--brand-sky); margin-right: 10px;"></i> Edit Profile</h3>
            <form action="settings.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" placeholder="example@syrmasgs.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Username (Read-only)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly style="background: var(--bg-body); cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <div class="badge badge-info"><?php echo strtoupper($user_data['role']); ?></div>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary" style="margin-top: 10px;">
                    <i class="fas fa-save"></i> Save Profile Changes
                </button>
            </form>
        </div>

        <!-- Security Settings -->
        <div class="card">
            <h3 style="margin-bottom: 25px;"><i class="fas fa-shield-alt" style="color: var(--danger); margin-right: 10px;"></i> Security & Password</h3>
            <form action="settings.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div style="background: rgba(245,158,11,0.08); padding: 15px; border-radius: 12px; border: 1px solid rgba(245,158,11,0.25); margin-bottom: 20px;">
                    <div style="font-size: 0.8rem; color: #d97706; font-weight: 700; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-lightbulb"></i> Password Tip
                    </div>
                    <div style="font-size: 0.75rem; color: #d97706; margin-top: 5px;">
                        Use a combination of letters, numbers, and symbols for a stronger password.
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn btn-danger" style="width: 100%;">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
