<?php
// trainer/results.php - Trainer can view exam results for their trainees
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];

renderHeader('Exam Results');
renderSidebar('trainer');

// Fetch all exam results for trainees assigned to this trainer
$stmt = $pdo->prepare("
    SELECT er.*, 
           u.full_name as trainee_name, u.employee_id, u.photo_path,
           e.title as exam_title, e.passing_score, e.duration_minutes,
           m.title as module_name
    FROM exam_results er
    JOIN users u ON er.trainee_id = u.id
    JOIN exams e ON er.exam_id = e.id
    JOIN training_modules m ON e.module_id = m.id
    JOIN assignments a ON (a.trainee_id = er.trainee_id AND a.module_id = e.module_id)
    WHERE a.trainer_id = ?
    ORDER BY er.exam_date DESC
");
$stmt->execute([$trainer_id]);
$results = $stmt->fetchAll();

// Summary stats
$total   = count($results);
$passed  = array_filter($results, fn($r) => $r['status'] === 'pass');
$failed  = array_filter($results, fn($r) => $r['status'] === 'fail');
$avgScore = $total > 0 ? round(array_sum(array_column($results, 'score')) / $total) : 0;
?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Attempts</div>
        <div class="stat-value"><?php echo $total; ?></div>
        <div class="stat-trend" style="color: var(--brand-royal);"><i class="fas fa-pen-alt"></i> All exam attempts</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Passed</div>
        <div class="stat-value" style="color: #059669;"><?php echo count($passed); ?></div>
        <div class="stat-trend" style="color: #059669;"><i class="fas fa-check-circle"></i> Successful trainees</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Failed</div>
        <div class="stat-value" style="color: #dc2626;"><?php echo count($failed); ?></div>
        <div class="stat-trend" style="color: #dc2626;"><i class="fas fa-times-circle"></i> Need improvement</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Avg Score</div>
        <div class="stat-value"><?php echo $avgScore; ?>%</div>
        <div class="stat-trend" style="color: var(--brand-sky);"><i class="fas fa-chart-line"></i> Class average</div>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3><i class="fas fa-chart-bar" style="color: var(--brand-royal); margin-right: 10px;"></i>Trainee Exam Results</h3>
        <span class="badge badge-info"><?php echo $total; ?> records</span>
    </div>

    <?php if (empty($results)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(11,112,183,0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-clipboard-list" style="font-size: 2rem; color: var(--brand-royal);"></i>
            </div>
            <h4 style="color: var(--brand-navy); margin-bottom: 8px;">No Results Yet</h4>
            <p style="color: var(--text-muted);">Your trainees haven't taken any exams yet.</p>
        </div>
    <?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Trainee</th>
                    <th>Module</th>
                    <th>Exam</th>
                    <th>Score</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): 
                    $pct = round(($row['score'] / max($row['passing_score'], 1)) * 100);
                    $scoreColor = $row['status'] === 'pass' ? '#059669' : '#dc2626';
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="user-avatar" style="width: 38px; height: 38px; border-radius: 12px;">
                                <?php if (!empty($row['photo_path'])): ?>
                                    <img src="<?php echo BASE_URL . $row['photo_path']; ?>">
                                <?php else: ?>
                                    <i class="fas fa-user" style="font-size: 0.9rem;"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: var(--brand-navy); font-size: 0.9rem;"><?php echo e($row['trainee_name']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($row['employee_id']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo e($row['module_name']); ?></td>
                    <td style="font-weight: 600; color: var(--brand-navy);"><?php echo e($row['exam_title']); ?></td>
                    <td>
                        <span style="font-size: 1.2rem; font-weight: 800; color: <?php echo $scoreColor; ?>; font-family: 'Outfit', sans-serif;">
                            <?php echo $row['score']; ?>%
                        </span>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">Pass: <?php echo $row['passing_score']; ?>%</div>
                    </td>
                    <td style="width: 130px;">
                        <div class="progress-bar-wrap">
                            <div class="progress-bar-fill" style="width: <?php echo min($row['score'], 100); ?>%; background: <?php echo $row['status'] === 'pass' ? 'linear-gradient(90deg,#10b981,#059669)' : 'linear-gradient(90deg,#ef4444,#b91c1c)'; ?>;"></div>
                        </div>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'pass'): ?>
                            <span class="badge badge-success"><i class="fas fa-check"></i> PASS</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fas fa-times"></i> FAIL</span>
                        <?php endif; ?>
                    </td>
                    <td style="color: var(--text-muted); font-size: 0.85rem; white-space: nowrap;">
                        <?php echo date('d M Y, H:i', strtotime($row['exam_date'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
