<?php
// admin/exams.php
require_once '../includes/layout.php';
checkRole(['admin', 'management']);

$action = $_GET['action'] ?? 'list';
$message = '';

if (isset($_POST['save_exam'])) {
    $title = $_POST['title'];
    $module_id = $_POST['module_id'];
    $duration = $_POST['duration'];
    $passing = $_POST['passing_score'];
    $camera_enabled = isset($_POST['camera_enabled']) ? 1 : 0;
    
    if ($_POST['exam_id']) {
        $stmt = $pdo->prepare("UPDATE exams SET title=?, module_id=?, duration_minutes=?, passing_score=?, camera_enabled=? WHERE id=?");
        $stmt->execute([$title, $module_id, $duration, $passing, $camera_enabled, $_POST['exam_id']]);
        $message = "Exam updated!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO exams (title, module_id, duration_minutes, passing_score, camera_enabled, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $module_id, $duration, $passing, $camera_enabled, $_SESSION['user_id']]);
        $message = "Exam created!";
    }
    $action = 'list';
}

renderHeader('Exam Management');
renderSidebar($_SESSION['role']);
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3><?php echo $action == 'add' ? 'Create Exam' : 'Online Exams'; ?></h3>
        <?php if ($action == 'list'): ?>
            <a href="exams.php?action=add" class="btn btn-primary">Create Exam</a>
        <?php else: ?>
            <a href="exams.php" class="btn" style="background: #4A5568; color: white;">Back</a>
        <?php endif; ?>
    </div>

    <?php if ($action == 'list'): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">Exam Title</th>
                        <th style="text-align: left;">Module</th>
                        <th style="text-align: center;">Duration</th>
                        <th style="text-align: center;">Pass %</th>
                        <th style="text-align: center;">Questions</th>
                        <th style="text-align: center;">Camera</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT e.*, m.title as module_name, (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as q_count FROM exams e JOIN training_modules m ON e.module_id = m.id");
                    while ($e = $stmt->fetch()):
                    ?>
                    <tr>
                        <td style="text-align: left;"><strong><?php echo e($e['title']); ?></strong></td>
                        <td style="text-align: left; color: var(--text-muted);"><?php echo e($e['module_name']); ?></td>
                        <td style="text-align: center;"><?php echo e($e['duration_minutes']); ?> min</td>
                        <td style="text-align: center;"><?php echo e($e['passing_score']); ?>%</td>
                        <td style="text-align: center;"><span class="badge badge-warning" style="background: #FFF5F1; color: #F59E0B;"><?php echo $e['q_count']; ?> Qs</span></td>
                        <td style="text-align: center;">
                            <span class="badge <?php echo $e['camera_enabled'] ? 'badge-success' : 'badge-danger'; ?>" style="<?php echo $e['camera_enabled'] ? 'background:#E6FFFA; color:#069669;' : 'background:#FFF5F5; color:#E53E3E;'; ?>">
                                <i class="fas <?php echo $e['camera_enabled'] ? 'fa-video' : 'fa-video-slash'; ?>"></i>
                                <?php echo $e['camera_enabled'] ? 'ON' : 'OFF'; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <a href="questions.php?exam_id=<?php echo $e['id']; ?>" style="color: var(--primary-blue); margin-right: 15px;" title="Manage Questions"><i class="fas fa-question-circle"></i></a>
                            <a href="exams.php?action=edit&id=<?php echo $e['id']; ?>" style="color: var(--text-muted);"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: 
        $e = ['id' => '', 'title' => '', 'module_id' => '', 'duration_minutes' => '30', 'passing_score' => '70', 'camera_enabled' => 0];
        if ($action == 'edit' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $e = $stmt->fetch();
        }
    ?>
    <form method="POST" action="exams.php">
        <input type="hidden" name="exam_id" value="<?php echo $e['id']; ?>">
        <div class="form-group">
            <label class="form-label">Exam Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo e($e['title']); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Training Module</label>
            <select name="module_id" class="form-control" required style="background: #2D3748;">
                <?php
                $modules = $pdo->query("SELECT id, title FROM training_modules");
                while ($m = $modules->fetch()) {
                    $sel = ($m['id'] == $e['module_id']) ? 'selected' : '';
                    echo "<option value='{$m['id']}' {$sel}>{$m['title']}</option>";
                }
                ?>
            </select>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Duration (Minutes)</label>
                <input type="number" name="duration" class="form-control" value="<?php echo e($e['duration_minutes']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Passing Score (%)</label>
                <input type="number" name="passing_score" class="form-control" value="<?php echo e($e['passing_score']); ?>" required>
            </div>
        </div>
        <div class="form-group" style="margin-top: 15px;">
            <label class="form-label">Camera Proctoring</label>
            <div style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; background: var(--sidebar-hover); border-radius: 14px; border: 1px solid var(--border-color);">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; font-size: 0.9rem; font-family: 'Outfit', sans-serif;">
                    <input type="checkbox" name="camera_enabled" value="1" <?php echo $e['camera_enabled'] ? 'checked' : ''; ?> style="width: 20px; height: 20px; accent-color: var(--primary-blue); cursor: pointer;">
                    <i class="fas fa-video" style="color: var(--primary-blue);"></i>
                    Enable camera proctoring for this exam
                </label>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;"><i class="fas fa-info-circle"></i> When enabled, trainees must have their camera on during the exam for AI face detection monitoring.</p>
        </div>
        <button type="submit" name="save_exam" class="btn btn-primary">Save Exam</button>
    </form>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
