<?php
// trainer/trainees.php
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];

renderHeader('My Trainees');
renderSidebar('trainer');
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3>Assigned Trainees</h3>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Emp ID</th>
                    <th>Module</th>
                    <th>Start Date</th>
                    <th>Efficiency / Progress</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("
                    SELECT a.*, u.full_name as trainee_name, u.employee_id, u.photo_path, m.title as module_name 
                    FROM assignments a 
                    JOIN users u ON a.trainee_id = u.id 
                    JOIN training_modules m ON a.module_id = m.id 
                    WHERE a.trainer_id = ?
                ");
                $stmt->execute([$trainer_id]);
                while ($row = $stmt->fetch()):
                    // Calculate efficiency for completed assignments
                    $training_eff = null;
                    $otj_eff = null;
                    if ($row['status'] == 'completed') {
                        // Get average exam score for this trainee on this module
                        $escStmt = $pdo->prepare("
                            SELECT ROUND(AVG(er.score), 1) as avg_score
                            FROM exam_results er
                            JOIN exams e ON er.exam_id = e.id
                            WHERE er.trainee_id = ? AND e.module_id = ?
                        ");
                        $escStmt->execute([$row['trainee_id'], $row['module_id']]);
                        $training_eff = $escStmt->fetchColumn() ?: 0;
                        
                        // Get OTJ hours vs total hours
                        $hrStmt = $pdo->prepare("
                            SELECT 
                                COALESCE(SUM(CASE WHEN type = 'otj' THEN man_hours ELSE 0 END), 0) as otj_hours,
                                COALESCE(SUM(man_hours), 0) as total_hours
                            FROM training_stages WHERE assignment_id = ?
                        ");
                        $hrStmt->execute([$row['id']]);
                        $hrs = $hrStmt->fetch();
                        $otj_eff = $hrs['total_hours'] > 0 ? round(($hrs['otj_hours'] / $hrs['total_hours']) * 100) : 0;
                    }
                ?>
                <tr <?php echo $row['is_locked'] ? 'style="background: #fff5f5;"' : ''; ?>>
                    <td>
                        <div class="user-avatar" style="width: 35px; height: 35px; position: relative;">
                            <?php if (!empty($row['photo_path'])): ?>
                                <img src="<?php echo BASE_URL . $row['photo_path']; ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                            <?php if ($row['is_locked']): ?>
                                <div style="position: absolute; top: -1px; right: -1px; width: 12px; height: 12px; background: #ef4444; border: 2px solid white; border-radius: 50%;"></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <strong><?php echo e($row['trainee_name']); ?></strong>
                        <?php if ($row['is_locked']): ?>
                            <span class="badge badge-danger" style="font-size: 0.65rem; margin-left: 5px;">LOCKED</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($row['employee_id']); ?></td>
                    <td><?php echo e($row['module_name']); ?></td>
                    <td><?php echo formatDate($row['assigned_date']); ?></td>
                    <td>
                        <?php if ($row['status'] == 'completed' && $training_eff !== null): ?>
                            <?php
                            if ($training_eff >= 90) { $tClass = 'excellent'; }
                            elseif ($training_eff >= 75) { $tClass = 'good'; }
                            elseif ($training_eff >= 60) { $tClass = 'average'; }
                            else { $tClass = 'low'; }
                            
                            if ($otj_eff >= 50) { $oClass = 'excellent'; }
                            elseif ($otj_eff >= 30) { $oClass = 'good'; }
                            elseif ($otj_eff > 0) { $oClass = 'average'; }
                            else { $oClass = 'low'; }
                            ?>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <span class="efficiency-badge <?php echo $tClass; ?>" style="font-size: 0.7rem; padding: 3px 8px;">
                                    <i class="fas fa-bolt"></i> <?php echo $training_eff; ?>%
                                </span>
                                <span class="efficiency-badge <?php echo $oClass; ?>" style="font-size: 0.7rem; padding: 3px 8px;">
                                    <i class="fas fa-industry"></i> OTJ <?php echo $otj_eff; ?>%
                                </span>
                            </div>
                        <?php else: ?>
                            <div style="width: 100px; height: 8px; background: #edf2f7; border-radius: 4px; overflow: hidden;">
                                <div style="width: <?php echo $row['status'] == 'completed' ? '100%' : ($row['status'] == 'in_progress' ? '50%' : '5%'); ?>; height: 100%; background: var(--primary-blue);"></div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px; justify-content: flex-start; width: 300px;">
                            <?php if ($row['is_locked']): ?>
                                <button onclick="unlockAssignment(<?php echo $row['id']; ?>)" class="btn" style="background: #38a169; color: white; font-size: 0.75rem; padding: 6px 12px;">
                                    <i class="fas fa-unlock"></i> Unlock Exam
                                </button>
                            <?php endif; ?>
                            <a href="progress.php?assignment_id=<?php echo $row['id']; ?>" class="btn btn-primary" style="font-size: 0.75rem; padding: 6px 12px;"><i class="fas fa-edit"></i> Manage</a>
                            <a href="../admin/training_record.php?type=full&id=<?php echo $row['id']; ?>" class="btn" style="background:#EDF2F7; color: var(--primary-blue); border: 1px solid var(--border-color); font-size: 0.75rem; padding: 6px 12px;" target="_blank"><i class="fas fa-file-invoice"></i> Report</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function unlockAssignment(assignmentId) {
    if (confirm('Are you sure you want to unlock this exam? The trainee will be able to attempt it again.')) {
        fetch('../api/unlock_assignment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ assignment_id: assignmentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to unlock assignment.');
            }
        });
    }
}
</script>

<?php renderFooter(); ?>
