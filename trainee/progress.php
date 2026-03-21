<?php
// trainee/progress.php
require_once '../includes/layout.php';
checkRole('trainee');

$trainee_id = $_SESSION['user_id'];
$assignment_id = $_GET['assignment_id'] ?? null;

// Fetch Induction Progress
$stmt = $pdo->prepare("SELECT COUNT(*) FROM induction_checklist");
$stmt->execute();
$total_induction = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trainee_checklist_progress WHERE trainee_id = ?");
$stmt->execute([$trainee_id]);
$done_induction = $stmt->fetchColumn();

$induction_percent = $total_induction > 0 ? round(($done_induction / $total_induction) * 100) : 0;

// Fetch Assignment Progress if specified
$assignment = null;
$stages = [];
if ($assignment_id) {
    $stmt = $pdo->prepare("
        SELECT a.*, m.title, m.category 
        FROM assignments a 
        JOIN training_modules m ON a.module_id = m.id 
        WHERE a.id = ? AND a.trainee_id = ?
    ");
    $stmt->execute([$assignment_id, $trainee_id]);
    $assignment = $stmt->fetch();

    if ($assignment) {
        $stmt = $pdo->prepare("SELECT * FROM training_stages WHERE assignment_id = ? ORDER BY certified_date ASC");
        $stmt->execute([$assignment_id]);
        $stages = $stmt->fetchAll();
    }
}

renderHeader('Trainee Progress');
renderSidebar('trainee');
?>

<div class="grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Overview Card -->
    <div>
        <div class="card" style="text-align: center; padding: 40px 20px;">
            <div style="position: relative; width: 150px; height: 150px; margin: 0 auto 30px;">
                <svg width="150" height="150" viewBox="0 0 150 150">
                    <circle cx="75" cy="75" r="65" fill="none" stroke="#e2e8f0" stroke-width="12" />
                    <circle cx="75" cy="75" r="65" fill="none" stroke="var(--primary-blue)" stroke-width="12" 
                            stroke-dasharray="408.4" stroke-dashoffset="<?php echo 408.4 * (1 - $induction_percent/100); ?>" 
                            stroke-linecap="round" style="transition: stroke-dashoffset 1.5s ease; transform: rotate(-90deg); transform-origin: 50% 50%;" />
                </svg>
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                    <h2 style="margin:0; font-size: 2rem;"><?php echo $induction_percent; ?>%</h2>
                </div>
            </div>
            <h3>Induction Progress</h3>
            <p style="color: var(--text-muted); margin-top: 10px;"><?php echo $done_induction; ?> of <?php echo $total_induction; ?> topics completed</p>
        </div>

        <?php if ($assignment): ?>
        <div class="card" style="margin-top: 30px;">
            <h4 style="margin-bottom: 15px;">Active Assignment</h4>
            <div style="background: rgba(11, 112, 183, 0.05); padding: 15px; border-radius: 12px; border: 1px solid rgba(11, 112, 183, 0.1);">
                <p style="margin: 0; font-weight: 700; color: var(--primary-blue);"><?php echo e($assignment['title']); ?></p>
                <p style="margin: 5px 0 0; font-size: 0.8rem; color: var(--text-muted);"><?php echo e($assignment['category']); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Details Card -->
    <div class="card">
        <h3>Training Milestones</h3>
        <div style="margin-top: 30px; position: relative; padding-left: 40px;">
            <div style="position: absolute; left: 19px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;"></div>

            <!-- Pre-defined Milestones -->
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -26px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: <?php echo $induction_percent > 0 ? 'var(--primary-blue)' : '#cbd5e1'; ?>; border: 3px solid white; box-shadow: 0 0 0 4px <?php echo $induction_percent > 0 ? 'rgba(11, 112, 183, 0.2)' : 'transparent'; ?>;"></div>
                <h4 style="margin: 0; color: <?php echo $induction_percent > 0 ? 'inherit' : 'var(--text-muted)'; ?>;">Induction Started</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 5px;">Your foundational journey into Syrma SGS.</p>
            </div>

            <?php if ($assignment): ?>
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -26px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--primary-blue); border: 3px solid white; box-shadow: 0 0 0 4px rgba(11, 112, 183, 0.2);"></div>
                <h4 style="margin: 0;">Module Assigned</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 5px;">Assigned: <?php echo formatDate($assignment['assigned_date']); ?> by Trainer.</p>
            </div>

            <?php foreach ($stages as $stage): ?>
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -26px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #38a169; border: 3px solid white; box-shadow: 0 0 0 4px rgba(56, 161, 105, 0.2);"></div>
                <h4 style="margin: 0; color: #38a169;"><?php echo e($stage['stage_name']); ?> (Certified)</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 5px;">Certified on <?php echo formatDate($stage['certified_date']); ?> | <?php echo $stage['man_hours']; ?> hrs</p>
                <?php if ($stage['remarks']): ?>
                    <p style="font-size: 0.8rem; background: #f8fafc; padding: 10px; border-radius: 8px; margin-top: 10px; border: 1px solid #edf2f7; color: #64748b; font-style: italic;">"<?php echo e($stage['remarks']); ?>"</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <?php if ($assignment['status'] == 'completed'): ?>
            <div style="position: relative; margin-bottom: 40px;">
                <div style="position: absolute; left: -26px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #f6ad55; border: 3px solid white; box-shadow: 0 0 0 4px rgba(246, 173, 85, 0.2);"></div>
                <h4 style="margin: 0; color: #dd6b20;">Certificate Issued</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 5px;">Congratulations! You have completed this module.</p>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
