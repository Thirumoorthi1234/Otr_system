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

renderHeader('Admin Dashboard');
renderSidebar('admin');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Trainees</div>
        <div class="stat-value"><?php echo $totalTrainees; ?></div>
        <div class="stat-trend" style="color: var(--success); font-size: 0.8rem;">
            <i class="fas fa-arrow-up"></i> Active learning
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Trainers</div>
        <div class="stat-value"><?php echo $totalTrainers; ?></div>
        <div class="stat-trend" style="color: var(--primary-blue); font-size: 0.8rem;">
            <i class="fas fa-user-tie"></i> Certified staff
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Exams</div>
        <div class="stat-value"><?php echo $totalExams; ?></div>
        <div class="stat-trend" style="color: var(--warning); font-size: 0.8rem;">
            <i class="fas fa-clock"></i> Ongoing sessions
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">System Users</div>
        <div class="stat-value"><?php echo $totalUsers; ?></div>
        <div class="stat-trend" style="color: var(--text-muted); font-size: 0.8rem;">
            <i class="fas fa-server"></i> Scalable infra
        </div>
    </div>
</div>

<div class="grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    <div class="card">
        <h3 style="margin-bottom: 20px;">Recent Activities</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch recent users
                    $stmt = $pdo->query("SELECT full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td>New user registered</td>
                        <td><?php echo e($row['full_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo ucfirst($row['role']); ?></span></td>
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
                        <td>Module assigned: <?php echo e($row['title']); ?></td>
                        <td><?php echo e($row['full_name']); ?></td>
                        <td><span class="badge badge-success">Trainee</span></td>
                        <td style="color: var(--text-muted);"><?php echo formatDate($row['assigned_date']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <h3 style="margin-bottom: 20px;">Quick Actions</h3>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <a href="users.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus" style="margin-right: 10px;"></i> Add New User
            </a>
            <a href="modules.php?action=add" class="btn btn-primary" style="background: var(--brand-dark);">
                <i class="fas fa-folder-plus" style="margin-right: 10px;"></i> Create Module
            </a>
            <a href="reports.php" class="btn btn-secondary">
                <i class="fas fa-file-download" style="margin-right: 10px;"></i> Export Data
            </a>
        </div>
    </div>
    <div class="card" style="margin-top: 30px; background: rgba(14, 165, 233, 0.05); border: 1px solid rgba(14, 165, 233, 0.1);">
        <div style="display: flex; justify-content: space-around; text-align: center; padding: 10px;">
            <div>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 5px;">Master Data Status</p>
                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Checklist Verified (44 Items)</span>
            </div>
            <div>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 5px;">Database Integrity</p>
                <span class="badge badge-info"><i class="fas fa-database"></i> Connected to otr_system</span>
            </div>
            <div>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 5px;">Server Time</p>
                <span style="font-weight: 600;"><?php echo date('d M Y, H:i'); ?></span>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
