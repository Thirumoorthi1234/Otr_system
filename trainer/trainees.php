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

    <div style="margin-bottom: 20px;">
        <input type="text" id="traineeSearch" placeholder="Search by trainee name or ID..." class="form-control" style="width: 100%; max-width: 400px; border-radius: 8px; padding: 10px 15px;" onkeyup="filterTrainees()">
    </div>

    <div class="table-container">
        <table style="border-collapse: collapse; width: 100%;">
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
                    SELECT a.*, u.full_name as trainee_name, u.employee_id, u.photo_path, 
                           u.doj, u.qualification, u.category, u.batch_number, u.department, u.username,
                           m.title as module_name 
                    FROM assignments a 
                    JOIN users u ON a.trainee_id = u.id 
                    JOIN training_modules m ON a.module_id = m.id 
                    WHERE a.trainer_id = ? AND u.status = 'active'
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
                <tr class="main-row" onclick="toggleDetails(this)" style="cursor: pointer; border-bottom: 1px solid var(--border-color); background: <?php echo $row['is_locked'] ? '#fff5f5' : 'var(--white)'; ?>; transition: background 0.2s;">
                    <td style="padding: 12px 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-caret-right expand-icon" style="color:#a0aec0; transition: transform 0.2s;"></i>
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
                        </div>
                    </td>
                    <td style="padding: 12px 15px;">
                        <strong><?php echo e($row['trainee_name']); ?></strong>
                        <?php if ($row['is_locked']): ?>
                            <span class="badge badge-danger" style="font-size: 0.65rem; margin-left: 5px;">LOCKED</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px 15px;"><?php echo e($row['employee_id']); ?></td>
                    <td style="padding: 12px 15px;"><?php echo e($row['module_name']); ?></td>
                    <td style="padding: 12px 15px;"><?php echo formatDate($row['assigned_date']); ?></td>
                    <td style="padding: 12px 15px;">
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
                    <td style="padding: 12px 15px;" onclick="event.stopPropagation();">
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
                <tr class="details-row" style="display: none; background: #fafafa; border-bottom: 2px solid var(--border-color);">
                    <td colspan="7" style="padding: 20px 25px;">
                        <div style="display: flex; gap: 20px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; border: 3px solid #e2e8f0; flex-shrink: 0;">
                                <?php if (!empty($row['photo_path'])): ?>
                                    <img src="<?php echo BASE_URL . $row['photo_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #a0aec0;"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                            </div>
                            <div style="flex-grow: 1;">
                                <h4 style="margin: 0 0 10px 0; color: var(--text-main); font-size: 1.1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">Trainee Details</h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.9rem; color: #4a5568;">
                                    <div><strong>Date of Joining:</strong> <?php echo $row['doj'] ? date('d M Y', strtotime($row['doj'])) : '-'; ?></div>
                                    <div><strong>Qualification:</strong> <?php echo e($row['qualification'] ? $row['qualification'] : '-'); ?></div>
                                    <div><strong>Category:</strong> <?php echo e($row['category'] ? $row['category'] : '-'); ?></div>
                                    <div><strong>Batch Number:</strong> <?php echo e($row['batch_number'] ? $row['batch_number'] : '-'); ?></div>
                                    <div><strong>Department:</strong> <?php echo e($row['department'] ? $row['department'] : '-'); ?></div>
                                    <div><strong>Username:</strong> <?php echo e($row['username'] ? $row['username'] : '-'); ?></div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleDetails(row) {
    const detailsRow = row.nextElementSibling;
    const icon = row.querySelector('.expand-icon');
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
        icon.style.transform = 'rotate(90deg)';
        const originalBg = row.style.background;
        row.setAttribute('data-bg', originalBg);
        row.style.background = '#f8fafc';
    } else {
        detailsRow.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
        row.style.background = row.getAttribute('data-bg') || '';
    }
}

function filterTrainees() {
    const input = document.getElementById('traineeSearch').value.toLowerCase();
    const rows = document.querySelectorAll('tbody .main-row');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(input)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
            const detailsRow = row.nextElementSibling;
            if (detailsRow && detailsRow.classList.contains('details-row')) {
                detailsRow.style.display = 'none';
                const icon = row.querySelector('.expand-icon');
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }
    });
}
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
