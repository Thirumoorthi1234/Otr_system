<?php
// trainer/progress.php
require_once '../includes/layout.php';
checkRole(['trainer', 'admin']);

$assignment_id = $_GET['assignment_id'] ?? null;

// If no ID, show a selection list
if (!$assignment_id) {
    renderHeader('Training Progress');
    renderSidebar('trainer');
    ?>
    <div class="card" style="max-width: 600px; margin: 40px auto;">
        <h3 style="margin-bottom: 20px;">Select Trainee to Update Progress</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Trainee Name</th>
                        <th>Module</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $trainer_id = $_SESSION['user_id'];
                    $stmt = $pdo->prepare("
                        SELECT a.id, u.full_name, m.title as module_name 
                        FROM assignments a 
                        JOIN users u ON a.trainee_id = u.id 
                        JOIN training_modules m ON a.module_id = m.id 
                        WHERE a.trainer_id = ? AND a.status != 'completed'
                    ");
                    $stmt->execute([$trainer_id]);
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><strong><?php echo e($row['full_name']); ?></strong></td>
                        <td><?php echo e($row['module_name']); ?></td>
                        <td><a href="progress.php?assignment_id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 5px 12px; font-size: 0.8rem;">Select</a></td>
                    </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </div>
    <?php
    renderFooter();
    exit();
}

// Fetch Assignment Details
$stmt = $pdo->prepare("
    SELECT a.*, t.full_name as trainee_name, t.employee_id as trainee_eno, t.department as trainee_dept,
           tr.full_name as trainer_name, m.title as module_name
    FROM assignments a
    JOIN users t ON a.trainee_id = t.id
    JOIN users tr ON a.trainer_id = tr.id
    JOIN training_modules m ON a.module_id = m.id
    WHERE a.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

if (!$assignment) die("Assignment not found.");

// Trainers can only manage their own assignments (unless admin)
if ($_SESSION['role'] !== 'admin' && $assignment['trainer_id'] != $_SESSION['user_id']) {
    die("Unauthorized access to this training record.");
}

$message = '';

// Handle Stage Certification
if (isset($_POST['save_stage'])) {
    $type = $_POST['type'];
    $stage_name = $_POST['stage_name'];
    $man_hours = $_POST['man_hours'];
    $date = $_POST['certified_date'];
    $remarks = $_POST['remarks'];
    
    $stmt = $pdo->prepare("INSERT INTO training_stages (assignment_id, type, stage_name, man_hours, certified_date, remarks, trainer_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$assignment_id, $type, $stage_name, $man_hours, $date, $remarks, $_SESSION['user_id']]);
    $message = "Training stage certified successfully!";
}

renderHeader('Trainee Management');
renderSidebar('trainer');
?>

<div style="margin-bottom: 30px;">
    <div class="card" style="display: flex; justify-content: space-between; align-items: center; border-left: 5px solid var(--primary-blue);">
        <div>
            <h2 style="margin:0;"><?php echo e($assignment['trainee_name']); ?></h2>
            <p style="margin:5px 0 0; color: var(--text-muted);">
                <?php echo e($assignment['module_name']); ?> | Assigned on <?php echo formatDate($assignment['assigned_date']); ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="trainees.php" class="btn" style="background:#edf2f7; color: #4A5568;">Back to List</a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div style="background: rgba(56, 161, 105, 0.1); color: #38A169; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid rgba(56, 161, 105, 0.2);">
        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    <!-- History -->
    <div class="card">
        <h3 style="margin-bottom: 20px;">Certified Training Stages</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Stage</th>
                        <th>Type</th>
                        <th>Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM training_stages WHERE assignment_id = ? ORDER BY certified_date DESC");
                    $stmt->execute([$assignment_id]);
                    while ($st = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><?php echo formatDate($st['certified_date']); ?></td>
                        <td><strong><?php echo e($st['stage_name']); ?></strong></td>
                        <td><span class="badge badge-info"><?php echo strtoupper($st['type']); ?></span></td>
                        <td><?php echo $st['man_hours']; ?> hrs</td>
                    </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </div>

    <!-- Certification Form -->
    <div class="card">
        <h3 style="margin-bottom: 20px;">Certify New Stage</h3>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Training Type</label>
                <select name="type" class="form-control" required>
                    <option value="sdc">Practical (SDC)</option>
                    <option value="otj">OTJ (Shop Floor)</option>
                    <option value="recertification">Recertification</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Stage Name</label>
                <input type="text" name="stage_name" class="form-control" required placeholder="e.g. Soldering">
            </div>
            <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label class="form-label">Man Hours</label>
                    <input type="number" step="0.5" name="man_hours" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" name="certified_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Performance notes..."></textarea>
            </div>
            <button type="submit" name="save_stage" class="btn btn-primary" style="width: 100%; padding: 12px;">
                <i class="fas fa-certificate"></i> Certify Stage
            </button>
        </form>
    </div>
</div>

<?php renderFooter(); ?>
