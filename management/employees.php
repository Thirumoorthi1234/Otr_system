<?php
// management/employees.php
require_once '../includes/layout.php';
checkRole('management');

// Count by role
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainee'");
$totalTrainees = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
$totalTrainers = $stmt->fetch()['count'];

$totalEmployees = $totalTrainees + $totalTrainers;

// Filter
$filter = $_GET['filter'] ?? 'all';

renderHeader('All Employees');
renderSidebar('management');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Employees</div>
        <div class="stat-value"><?php echo $totalEmployees; ?></div>
        <div class="stat-trend" style="color: var(--primary-blue);"><i class="fas fa-users"></i> Trainee + Trainer</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Trainees</div>
        <div class="stat-value"><?php echo $totalTrainees; ?></div>
        <div class="stat-trend" style="color: var(--success);"><i class="fas fa-user-graduate"></i> Active learners</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Trainers</div>
        <div class="stat-value"><?php echo $totalTrainers; ?></div>
        <div class="stat-trend" style="color: var(--warning);"><i class="fas fa-user-tie"></i> Certified staff</div>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <h3><i class="fas fa-users" style="margin-right: 10px; color: var(--primary-blue);"></i>Employee Directory</h3>
        <div style="display: flex; gap: 5px; background: #F8FAFC; padding: 5px; border-radius: 12px; border: 1px solid var(--border-color);">
            <a href="employees.php?filter=all" class="btn" style="background: <?php echo $filter == 'all' ? 'var(--primary-blue)' : 'transparent'; ?>; color: <?php echo $filter == 'all' ? 'white' : 'var(--text-muted)'; ?>; font-size: 0.8rem; border-radius: 8px;">All</a>
            <a href="employees.php?filter=trainee" class="btn" style="background: <?php echo $filter == 'trainee' ? 'var(--primary-blue)' : 'transparent'; ?>; color: <?php echo $filter == 'trainee' ? 'white' : 'var(--text-muted)'; ?>; font-size: 0.8rem; border-radius: 8px;">Trainees</a>
            <a href="employees.php?filter=trainer" class="btn" style="background: <?php echo $filter == 'trainer' ? 'var(--primary-blue)' : 'transparent'; ?>; color: <?php echo $filter == 'trainer' ? 'white' : 'var(--text-muted)'; ?>; font-size: 0.8rem; border-radius: 8px;">Trainers</a>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Employee ID</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Qualification</th>
                    <th>Batch No.</th>
                    <th>Date of Joining</th>
                    <th>Date of Leaving</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($filter == 'all') {
                    $stmt = $pdo->query("SELECT * FROM users WHERE role IN ('trainee', 'trainer') ORDER BY role, full_name");
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? ORDER BY full_name");
                    $stmt->execute([$filter]);
                }
                while ($emp = $stmt->fetch()):
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="user-avatar" style="width: 32px; height: 32px; border-radius: 10px; font-size: 0.75rem;">
                                <?php if (!empty($emp['photo_path'])): ?>
                                    <img src="<?php echo BASE_URL . $emp['photo_path']; ?>" alt="Avatar">
                                <?php else: ?>
                                    <i class="fas fa-user" style="color: white; font-size: 0.7rem;"></i>
                                <?php endif; ?>
                            </div>
                            <strong><?php echo e($emp['full_name']); ?></strong>
                        </div>
                    </td>
                    <td><?php echo e($emp['employee_id'] ?? '-'); ?></td>
                    <td>
                        <span class="badge <?php echo $emp['role'] == 'trainee' ? 'badge-info' : 'badge-success'; ?>">
                            <?php echo ucfirst($emp['role']); ?>
                        </span>
                    </td>
                    <td><?php echo e($emp['department'] ?? '-'); ?></td>
                    <td><?php echo e($emp['qualification'] ?? '-'); ?></td>
                    <td><?php echo $emp['batch_number'] ?? '-'; ?></td>
                    <td><?php echo (!empty($emp['doj']) && $emp['doj'] !== '0000-00-00') ? date('d M Y', strtotime($emp['doj'])) : '-'; ?></td>
                    <td><?php echo (!empty($emp['dol']) && $emp['dol'] !== '0000-00-00') ? date('d M Y', strtotime($emp['dol'])) : '-'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>
