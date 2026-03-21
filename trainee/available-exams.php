<?php
// trainee/available-exams.php
require_once '../includes/layout.php';
checkRole('trainee');

$trainee_id = $_SESSION['user_id'];

// Fetch available exams based on assigned modules
$stmt = $pdo->prepare("
    SELECT e.*, m.title as module_name, a.status as assignment_status
    FROM exams e
    JOIN training_modules m ON e.module_id = m.id
    JOIN assignments a ON a.module_id = m.id
    WHERE a.trainee_id = ? AND a.status != 'completed'
");
$stmt->execute([$trainee_id]);
$exams = $stmt->fetchAll();

renderHeader('Available Exams');
renderSidebar('trainee');
?>

<div class="card">
    <h3>My Assessments</h3>
    <p style="color: var(--text-muted); margin-bottom: 25px;">Exams available for your assigned training modules.</p>

    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php if (empty($exams)): ?>
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <i class="fas fa-info-circle" style="font-size: 2rem; color: var(--primary-blue); margin-bottom: 15px;"></i>
                <p>No exams are currently available for you. Ensure your training modules are in progress.</p>
            </div>
        <?php else: ?>
            <?php foreach ($exams as $exam): ?>
                <div class="card" style="border: 1px solid var(--border-color); background: var(--white); box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <span class="badge badge-warning" style="font-size: 0.7rem;"><?php echo strtoupper($exam['assignment_status']); ?></span>
                        <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="far fa-clock"></i> <?php echo $exam['duration_minutes']; ?> Min</span>
                    </div>
                    <h4 style="margin-bottom: 10px;"><?php echo e($exam['title']); ?></h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">
                        Module: <strong><?php echo e($exam['module_name']); ?></strong><br>
                        Passing Score: <strong><?php echo $exam['passing_score']; ?>%</strong>
                    </p>
                    <a href="exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary" style="width: 100%; text-align: center; display: block;">Start Examination</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php renderFooter(); ?>
