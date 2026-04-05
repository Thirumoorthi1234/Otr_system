<?php
// admin/induction_records.php
require_once '../includes/layout.php';
checkRole(['admin', 'trainer']);

$trainee_id = $_GET['trainee_id'] ?? null;
$message = '';

// Handle Delete Progress
if (isset($_GET['delete_progress'])) {
    $stmt = $pdo->prepare("DELETE FROM trainee_checklist_progress WHERE id = ?");
    $stmt->execute([$_GET['delete_progress']]);
    $message = __("progress_record_removed");
}

// Handle Add Progress Manual
if (isset($_POST['add_progress'])) {
    $topic_id = $_POST['topic_id'];
    $trainer_id = $_POST['trainer_id'];
    $t_id = $_POST['trainee_id'];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO trainee_checklist_progress (trainee_id, checklist_id, is_done, trainer_id, completed_at) VALUES (?, ?, 1, ?, NOW())");
    $stmt->execute([$t_id, $topic_id, $trainer_id]);
    $message = __("record_added_successfully");
}

renderHeader(__('induction_progress_management'));
renderSidebar($_SESSION['role']);
?>

<div class="grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Selector Side -->
    <div>
        <div class="card" style="margin-bottom: 25px;">
            <h3><?php echo __('select_trainee'); ?></h3>
            <form method="GET" style="margin-top: 15px;">
                <div class="form-group">
                    <select name="trainee_id" class="form-control" onchange="this.form.submit()">
                        <option value=""><?php echo __('-- Choose Trainee --'); ?></option>
                        <?php
                        $users = $pdo->query("SELECT id, full_name, employee_id FROM users WHERE role = 'trainee' ORDER BY full_name ASC");
                        while ($u = $users->fetch()):
                        ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $trainee_id == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo e($u['full_name']); ?> (<?php echo e($u['employee_id']); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($trainee_id): ?>
        <div class="card">
            <h3><?php echo __('record_new_entry'); ?></h3>
            <form method="POST" style="margin-top: 15px;">
                <input type="hidden" name="trainee_id" value="<?php echo $trainee_id; ?>">
                <div class="form-group">
                    <label class="form-label"><?php echo __('induction_topic'); ?></label>
                    <select name="topic_id" class="form-control" required>
                        <?php
                        // Only show topics not yet completed
                        $stmt = $pdo->prepare("SELECT id, day_number, topic_name FROM induction_checklist WHERE id NOT IN (SELECT checklist_id FROM trainee_checklist_progress WHERE trainee_id = ?) ORDER BY day_number, id");
                        $stmt->execute([$trainee_id]);
                        while ($t = $stmt->fetch()):
                        ?>
                        <option value="<?php echo $t['id']; ?>">D<?php echo $t['day_number']; ?> - <?php echo e($t['topic_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __('certified_by_trainer'); ?></label>
                    <select name="trainer_id" class="form-control" required>
                        <?php
                        $trainers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'trainer'");
                        while ($tr = $trainers->fetch()) echo "<option value='{$tr['id']}'>{$tr['full_name']}</option>";
                        ?>
                    </select>
                </div>
                <button type="submit" name="add_progress" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-plus-circle"></i> <?php echo __('add_record'); ?>
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Details Side -->
    <div class="card">
        <?php if (!$trainee_id): ?>
            <div style="text-align: center; padding: 60px;">
                <i class="fas fa-id-badge" style="font-size: 3rem; color: #ddd; margin-bottom: 20px;"></i>
                <p style="color: var(--text-muted);"><?php echo __('select_trainee_to_manage_induction_desc'); ?></p>
            </div>
        <?php else: 
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$trainee_id]);
            $t_info = $stmt->fetch();

            // Fetch the trainee's latest assignment for OTJ/SDC forms
            $stmt = $pdo->prepare("SELECT id FROM assignments WHERE trainee_id = ? ORDER BY assigned_date DESC LIMIT 1");
            $stmt->execute([$trainee_id]);
            $latest_assignment = $stmt->fetch();
            $assignment_id = $latest_assignment ? $latest_assignment['id'] : null;
        ?>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                <div>
                    <h3><?php echo e($t_info['full_name']); ?>'s <?php echo __('Records'); ?></h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted);"><?php echo e($t_info['employee_id']); ?> | <?php echo e($t_info['department']); ?></p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="otr_form.php?tid=<?php echo $trainee_id; ?>" target="_blank" class="btn" style="background: var(--primary-blue); color: white; font-size: 0.85rem;"><i class="fas fa-file-invoice"></i> <?php echo __('Cover'); ?></a>
                    <a href="induction_p2_view.php?tid=<?php echo $trainee_id; ?>" target="_blank" class="btn" style="background: #edf2f7; color: var(--primary-blue); font-size: 0.85rem;"><i class="fas fa-file-alt"></i> <?php echo __('P2'); ?></a>
                    <a href="induction_p3_view.php?tid=<?php echo $trainee_id; ?>" target="_blank" class="btn" style="background: #edf2f7; color: var(--primary-blue); font-size: 0.85rem;"><i class="fas fa-file-alt"></i> <?php echo __('P3'); ?></a>
                    <?php if ($assignment_id): ?>
                        <a href="sdc_form.php?id=<?php echo $assignment_id; ?>" target="_blank" class="btn" style="background: #edf2f7; color: #38a169; font-size: 0.85rem;"><i class="fas fa-tools"></i> <?php echo __('SDC'); ?></a>
                        <a href="otj_form.php?id=<?php echo $assignment_id; ?>" target="_blank" class="btn" style="background: #edf2f7; color: #38a169; font-size: 0.85rem;"><i class="fas fa-industry"></i> <?php echo __('OTJ P1'); ?></a>
                        <a href="otj_p2_form.php?id=<?php echo $assignment_id; ?>" target="_blank" class="btn" style="background: #edf2f7; color: #38a169; font-size: 0.85rem;"><i class="fas fa-industry"></i> <?php echo __('OTJ P2'); ?></a>
                    <?php endif; ?>
                    <a href="score_sheet_entry.php?tid=<?php echo $trainee_id; ?>" target="_blank" class="btn" style="background: #edf2f7; color: #805AD5; font-size: 0.85rem;"><i class="fas fa-star"></i> <?php echo __('Score'); ?></a>
                    <a href="feedback_view.php?tid=<?php echo $trainee_id; ?>" target="_blank" class="btn" style="background: #edf2f7; color: var(--primary-blue); font-size: 0.85rem;"><i class="fas fa-comment-dots"></i> <?php echo __('Feedback'); ?></a>
                    <a href="../<?php echo $_SESSION['role']; ?>/dashboard.php?trainee_id=<?php echo $trainee_id; ?>" class="btn" style="background: #edf2f7; color: #4A5568; font-size: 0.85rem;"><i class="fas fa-chart-line"></i> <?php echo __('Dashboard'); ?></a>
                </div>
            </div>

            <?php if ($message): ?>
                <div style="background: rgba(56, 161, 105, 0.1); color: #2F855A; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(56, 161, 105, 0.2);">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><?php echo __('Day'); ?></th>
                            <th><?php echo __('Topic'); ?></th>
                            <th><?php echo __('completed_at'); ?></th>
                            <th><?php echo __('signed_by'); ?></th>
                            <th><?php echo __('action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT p.*, ic.topic_name, ic.day_number, u.full_name as trainer_name 
                            FROM trainee_checklist_progress p 
                            JOIN induction_checklist ic ON p.checklist_id = ic.id 
                            LEFT JOIN users u ON p.trainer_id = u.id 
                            WHERE p.trainee_id = ? 
                            ORDER BY ic.day_number, p.completed_at DESC
                        ");
                        $stmt->execute([$trainee_id]);
                        while ($row = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><span class="badge badge-info">Day <?php echo $row['day_number']; ?></span></td>
                            <td><strong><?php echo e($row['topic_name']); ?></strong></td>
                            <td style="font-size: 0.85rem;"><?php echo formatDate($row['completed_at']); ?></td>
                            <td style="font-size: 0.85rem;"><?php echo e($row['trainer_name'] ?? '-'); ?></td>
                            <td>
                                <a href="induction_records.php?trainee_id=<?php echo $trainee_id; ?>&delete_progress=<?php echo $row['id']; ?>" 
                                   style="color: var(--danger);" onclick="return confirm('<?php echo __('remove_record_confirm'); ?>')">
                                   <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($stmt->rowCount() == 0): ?>
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;"><?php echo __('no_induction_records_found'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php renderFooter(); ?>
