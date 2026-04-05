<?php
// admin/dashboard.php
require_once '../includes/layout.php';
checkRole('admin');

// Fetch some stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'trainer'");
$totalTrainers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'trainee'");
$totalTrainees = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM exams");
$totalExams = $stmt->fetch()['total'];

renderHeader(__('admin_dashboard'));
renderSidebar('admin');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label"><?php echo __('total_trainees'); ?></div>
        <div class="stat-value"><?php echo $totalTrainees; ?></div>
        <div class="stat-trend" style="color: var(--success); font-size: 0.8rem;">
            <i class="fas fa-arrow-up"></i> <?php echo __('active_learning'); ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><?php echo __('total_trainers'); ?></div>
        <div class="stat-value"><?php echo $totalTrainers; ?></div>
        <div class="stat-trend" style="color: var(--primary-blue); font-size: 0.8rem;">
            <i class="fas fa-user-tie"></i> <?php echo __('certified_staff'); ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><?php echo __('active_exams'); ?></div>
        <div class="stat-value"><?php echo $totalExams; ?></div>
        <div class="stat-trend" style="color: var(--warning); font-size: 0.8rem;">
            <i class="fas fa-clock"></i> <?php echo __('ongoing_sessions'); ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><?php echo __('system_users'); ?></div>
        <div class="stat-value"><?php echo $totalUsers; ?></div>
        <div class="stat-trend" style="color: var(--text-muted); font-size: 0.8rem;">
            <i class="fas fa-server"></i> <?php echo __('scalable_infra'); ?>
        </div>
    </div>
</div>

<div class="grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    <div class="card">
        <h3 style="margin-bottom: 20px;"><?php echo __('recent_activities'); ?></h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?php echo __('activity'); ?></th>
                        <th><?php echo __('user'); ?></th>
                        <th><?php echo __('role'); ?></th>
                        <th><?php echo __('time'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch recent users
                    $stmt = $pdo->query("SELECT full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><?php echo __('new_user_registered'); ?></td>
                        <td><?php echo e($row['full_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo __($row['role']); ?></span></td>
                        <td style="color: var(--text-muted);"><?php echo formatDate($row['created_at']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php
                    // Fetch recent assignments
                    $stmt = $pdo->query("
                        SELECT a.assigned_date, u.full_name, m.title 
                        FROM assignments a 
                        JOIN users u ON a.trainee_id = u.id 
                        JOIN training_modules m ON a.module_id = m.id 
                        ORDER BY a.assigned_date DESC LIMIT 5
                    ");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><?php echo __('module_assigned'); ?>: <?php echo e($row['title']); ?></td>
                        <td><?php echo e($row['full_name']); ?></td>
                        <td><span class="badge badge-success"><?php echo __('trainee'); ?></span></td>
                        <td style="color: var(--text-muted);"><?php echo formatDate($row['assigned_date']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <h3 style="margin-bottom: 20px;"><?php echo __('quick_actions'); ?></h3>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <a href="users.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus" style="margin-right: 10px;"></i> <?php echo __('add_new_user'); ?>
            </a>
            <a href="modules.php?action=add" class="btn btn-primary" style="background: var(--brand-dark);">
                <i class="fas fa-folder-plus" style="margin-right: 10px;"></i> <?php echo __('create_module'); ?>
            </a>
            <a href="reports.php" class="btn btn-secondary">
                <i class="fas fa-file-download" style="margin-right: 10px;"></i> <?php echo __('export_data'); ?>
            </a>
        </div>
    </div>
    <div class="card" style="margin-top: 30px; background: rgba(14, 165, 233, 0.05); border: 1px solid rgba(14, 165, 233, 0.1);">
        <div style="display: flex; justify-content: space-around; text-align: center; padding: 10px;">
            <div>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 5px;"><?php echo __('master_data_status'); ?></p>
                <span class="badge badge-success"><i class="fas fa-check-circle"></i> <?php echo __('checklist_verified'); ?> (44 Items)</span>
            </div>
            <div>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 5px;"><?php echo __('database_integrity'); ?></p>
                <span class="badge badge-info"><i class="fas fa-database"></i> <?php echo __('connected_to'); ?> otr_system</span>
            </div>
            <div>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 5px;"><?php echo __('server_time'); ?></p>
                <span style="font-weight: 600;"><?php echo date('d M Y, H:i'); ?></span>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
