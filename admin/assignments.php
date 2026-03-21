<?php
// admin/assignments.php
require_once '../includes/layout.php';
checkRole('admin');

$message = '';

if (isset($_POST['assign'])) {
    $trainee_id = $_POST['trainee_id'];
    $trainer_id = $_POST['trainer_id'];
    $module_id = $_POST['module_id'];
    $date = $_POST['assigned_date'];
    
    // Check if already assigned
    $stmt = $pdo->prepare("SELECT id FROM assignments WHERE trainee_id = ? AND module_id = ?");
    $stmt->execute([$trainee_id, $module_id]);
    if ($stmt->fetch()) {
        $message = "Error: Trainee is already assigned to this module.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO assignments (trainee_id, trainer_id, module_id, assigned_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$trainee_id, $trainer_id, $module_id, $date]);
        $message = "Assignment created successfully!";
    }
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Assignment removed.";
}

renderHeader('Training Assignments');
renderSidebar('admin');
?>

<div class="grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- New Assignment -->
    <div class="card">
        <h3>New Assignment</h3>
        <form method="POST" style="margin-top: 20px;">
            <div class="form-group">
                <label class="form-label">Trainee</label>
                <select name="trainee_id" class="form-control" required>
                    <?php
                    $users = $pdo->query("SELECT id, full_name FROM users WHERE role = 'trainee'");
                    while ($u = $users->fetch()) echo "<option value='{$u['id']}'>{$u['full_name']}</option>";
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Assign to Trainer</label>
                <select name="trainer_id" class="form-control" required>
                    <?php
                    $users = $pdo->query("SELECT id, full_name FROM users WHERE role = 'trainer'");
                    while ($u = $users->fetch()) echo "<option value='{$u['id']}'>{$u['full_name']}</option>";
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Module</label>
                <select name="module_id" class="form-control" required>
                    <?php
                    $modules = $pdo->query("SELECT id, title FROM training_modules");
                    while ($m = $modules->fetch()) echo "<option value='{$m['id']}'>{$m['title']}</option>";
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Assignment Date</label>
                <input type="date" name="assigned_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <button type="submit" name="assign" class="btn btn-primary" style="width: 100%;">Create Assignment</button>
        </form>
    </div>

    <!-- Active Assignments -->
    <div class="card">
        <h3>Active Assignments</h3>
        <?php if ($message): ?>
            <div style="background: rgba(56, 161, 105, 0.1); color: #48BB78; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid rgba(56, 161, 105, 0.2);">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="table-container" style="margin-top: 20px;">
            <table>
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Trainer</th>
                        <th>Module</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT a.*, t.full_name as trainee_name, tr.full_name as trainer_name, m.title as module_name 
                        FROM assignments a 
                        JOIN users t ON a.trainee_id = t.id 
                        JOIN users tr ON a.trainer_id = tr.id 
                        JOIN training_modules m ON a.module_id = m.id
                        ORDER BY a.assigned_date DESC
                    ");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><strong><?php echo e($row['trainee_name']); ?></strong></td>
                        <td><?php echo e($row['trainer_name']); ?></td>
                        <td><?php echo e($row['module_name']); ?></td>
                        <td><span class="badge <?php echo $row['status'] == 'completed' ? 'badge-success' : ($row['status'] == 'in_progress' ? 'badge-warning' : ''); ?>"><?php echo strtoupper(str_replace('_', ' ', $row['status'])); ?></span></td>
                        <td>
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <a href="training_hub.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" style="font-size: 0.75rem; padding: 6px 12px;"><i class="fas fa-edit"></i> Manage Training</a>
                                <a href="assignments.php?delete=<?php echo $row['id']; ?>" class="btn" style="background: rgba(229, 62, 62, 0.1); color: var(--danger); font-size: 0.75rem; padding: 6px 12px;" onclick="return confirm('Remove assignment?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
