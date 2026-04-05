<?php
// admin/inactive_users.php
require_once '../includes/layout.php';
checkRole(['admin', 'management']);

$action = 'list';
$message = '';

if (isset($_GET['action']) && $_GET['action'] == 'active' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $message = __("user_activated_successfully");
}

renderHeader(__('inactive_members'));
renderSidebar($_SESSION['role']);
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3><?php echo __('inactive_members'); ?></h3>
    </div>

    <?php if ($message): ?>
        <div style="background: rgba(56, 161, 105, 0.1); color: #48BB78; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(56, 161, 105, 0.2);">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>



    <div class="table-container">
        <table style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 12px 15px;"><?php echo __('full_name'); ?></th>
                    <th style="text-align: left; padding: 12px 15px;"><?php echo __('username'); ?></th>
                    <th style="text-align: center; padding: 12px 15px;"><?php echo __('role'); ?></th>
                    <th style="text-align: center; padding: 12px 15px;"><?php echo __('emp_id'); ?></th>
                    <th style="text-align: left; padding: 12px 15px;"><?php echo __('department'); ?></th>
                    <th style="text-align: center; padding: 12px 15px;"><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM users WHERE status = 'inactive' ORDER BY created_at DESC");
                while ($user = $stmt->fetch()):
                ?>
                <tr class="main-row" style="border-bottom: 1px solid var(--border-color); background: var(--white);">
                    <td style="text-align: left; padding: 12px 15px;"><strong><?php echo e($user['full_name']); ?></strong></td>
                    <td style="text-align: left; color: var(--text-muted); padding: 12px 15px;"><?php echo e($user['username']); ?></td>
                    <td style="text-align: center; padding: 12px 15px;"><span class="badge" style="background: rgba(226, 232, 240, 0.8); color: #4a5568;"><?php echo __($user['role'] == 'management' ? 'manager' : $user['role']); ?></span></td>
                    <td style="text-align: center; font-family: 'Outfit', sans-serif; font-weight: 600; padding: 12px 15px;"><?php echo e($user['employee_id'] ?? '-'); ?></td>
                    <td style="text-align: left; font-size: 0.8rem; padding: 12px 15px;"><?php echo e($user['department'] ?? '-'); ?></td>
                    <td style="text-align: center; padding: 12px 15px;">
                        <a href="inactive_users.php?action=active&id=<?php echo $user['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('<?php echo __('activate_user_confirm'); ?>')"><i class="fas fa-check"></i> <?php echo __('activate'); ?></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>



<?php renderFooter(); ?>
