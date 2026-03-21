<?php
// Shared profile page
require_once 'includes/layout.php';
$role = $_SESSION['role'];

// Determine path to redirect back if needed
$back_path = $role . "/dashboard.php";

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

renderHeader('User Profile');
renderSidebar($role);
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <div class="user-avatar" style="width: 100px; height: 100px; margin: 0 auto 20px; overflow: hidden; border-radius: 50%;">
            <?php if (!empty($user['photo_path'])): ?>
                <img src="<?php echo BASE_URL . $user['photo_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <i class="fas fa-user" style="font-size: 3rem;"></i>
            <?php endif; ?>
        </div>
        <h3><?php echo e($user['full_name']); ?></h3>
        <p style="color: var(--text-muted);"><?php echo strtoupper($user['role']); ?></p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; border-top: 1px solid var(--border-color); padding-top: 30px;">
        <div>
            <label class="form-label" style="font-size: 0.8rem;">USERNAME</label>
            <p><strong><?php echo e($user['username']); ?></strong></p>
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem;">EMPLOYEE ID</label>
            <p><strong><?php echo e($user['employee_id'] ?? '-'); ?></strong></p>
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem;">DEPARTMENT</label>
            <p><strong><?php echo e($user['department'] ?? 'Operations'); ?></strong></p>
        </div>
        <div>
            <label class="form-label" style="font-size: 0.8rem;">LAST UPDATE</label>
            <p><strong><?php echo formatDate($user['updated_at']); ?></strong></p>
        </div>
    </div>

    <div style="margin-top: 40px; text-align: center;">
        <p style="font-size: 0.85rem; color: var(--text-muted);">Contact your administrator to change your profile information.</p>
    </div>
</div>

<?php renderFooter(); ?>
