<?php
// admin/training_hub.php
require_once '../includes/layout.php';
checkRole(['admin', 'trainer']);

$assignment_id = $_GET['id'] ?? null;

if (!$assignment_id) {
    renderHeader('Training Hub Selection');
    renderSidebar($_SESSION['role']);
    ?>
    <div class="card" style="max-width: 800px; margin: 40px auto;">
        <h3 style="margin-bottom: 20px;"><?php echo __('Select Training Assignment'); ?></h3>
        <p style="color: var(--text-muted); margin-bottom: 20px;"><?php echo __('Please select an active training assignment to manage its practical stages and induction progress.'); ?></p>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?php echo __('trainee'); ?></th>
                        <th><?php echo __('trainer'); ?></th>
                        <th><?php echo __('module'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Adjust query based on role
                    $query = "
                        SELECT a.*, t.full_name as trainee_name, tr.full_name as trainer_name, m.title as module_name 
                        FROM assignments a 
                        JOIN users t ON a.trainee_id = t.id 
                        JOIN users tr ON a.trainer_id = tr.id
                        JOIN training_modules m ON a.module_id = m.id
                    ";
                    $params = [];
                    if ($_SESSION['role'] == 'trainer') {
                        $query .= " WHERE a.trainer_id = ?";
                        $params[] = $_SESSION['user_id'];
                    }
                    $query .= " ORDER BY a.assigned_date DESC";
                    
                    $stmt = $pdo->prepare($query);
                    $stmt->execute($params);
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><strong><?php echo e($row['trainee_name']); ?></strong></td>
                        <td><?php echo e($row['trainer_name']); ?></td>
                        <td><?php echo e($row['module_name']); ?></td>
                        <td><span class="badge <?php echo $row['status'] == 'completed' ? 'badge-success' : 'badge-warning'; ?>"><?php echo __(strtoupper(str_replace('_', ' ', $row['status']))); ?></span></td>
                        <td><a href="training_hub.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 5px 12px; font-size: 0.8rem;"><?php echo __('Manage'); ?></a></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($stmt->rowCount() == 0): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;"><?php echo __('No training assignments found.'); ?></td></tr>
                    <?php endif; ?>
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

if (!$assignment) die(__("Assignment not found."));

$message = '';
$edit_stage = null;

// Handle CRUD Operations for Practical/OTJ Stages
if (isset($_POST['save_stage'])) {
    $stage_id = $_POST['stage_id'] ?? null;
    $type = $_POST['type'] ?? 'sdc';
    $stage_name = $_POST['stage_name'];
    $man_hours = $_POST['man_hours'];
    $date = $_POST['certified_date'];
    $remarks = $_POST['remarks'];
    
    if ($stage_id) {
        $stmt = $pdo->prepare("UPDATE training_stages SET type = ?, stage_name = ?, man_hours = ?, certified_date = ?, remarks = ? WHERE id = ?");
        $stmt->execute([$type, $stage_name, $man_hours, $date, $remarks, $stage_id]);
        $message = __("Stage updated successfully!");
    } else {
        $stmt = $pdo->prepare("INSERT INTO training_stages (assignment_id, type, stage_name, man_hours, certified_date, remarks, trainer_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$assignment_id, $type, $stage_name, $man_hours, $date, $remarks, $assignment['trainer_id']]);
        $message = __("Stage added successfully!");
    }
}

if (isset($_GET['delete_stage'])) {
    $stmt = $pdo->prepare("DELETE FROM training_stages WHERE id = ? AND assignment_id = ?");
    $stmt->execute([$_GET['delete_stage'], $assignment_id]);
    $message = __("Stage record removed.");
}

if (isset($_GET['edit_stage'])) {
    $stmt = $pdo->prepare("SELECT * FROM training_stages WHERE id = ? AND assignment_id = ?");
    $stmt->execute([$_GET['edit_stage'], $assignment_id]);
    $edit_stage = $stmt->fetch();
}

// Handle Induction Completion Toggle (AJAX-like)
if (isset($_POST['toggle_induction'])) {
    $topic_id = $_POST['topic_id'];
    $is_done = $_POST['is_done'];
    $trainee_id = $assignment['trainee_id'];
    
    if ($is_done == 1) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO trainee_checklist_progress (trainee_id, checklist_id, is_done, trainer_id, completed_at) VALUES (?, ?, 1, ?, NOW())");
        $stmt->execute([$trainee_id, $topic_id, $assignment['trainer_id']]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM trainee_checklist_progress WHERE trainee_id = ? AND checklist_id = ?");
        $stmt->execute([$trainee_id, $topic_id]);
    }
    header("Location: training_hub.php?id=$assignment_id#induction");
    exit();
}

renderHeader('Training Management Hub');
renderSidebar($_SESSION['role']);
?>

<div style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 25px; border-radius: 12px; border: 1px solid #edf2f7; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
        <div>
            <h2 style="margin:0; color: var(--primary-blue);"><?php echo e($assignment['trainee_name']); ?></h2>
            <p style="margin:5px 0 0; color: var(--text-muted); font-size: 0.9rem;">
                <?php echo __('emp_id'); ?>: <strong><?php echo e($assignment['trainee_eno']); ?></strong> | 
                <?php echo __('dept'); ?>: <strong><?php echo e($assignment['trainee_dept']); ?></strong> | 
                <?php echo __('module'); ?>: <strong><?php echo e($assignment['module_name']); ?></strong>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="sdc_form.php?id=<?php echo $assignment_id; ?>" target="_blank" class="btn" style="background: var(--primary-blue); color: white;">
                <i class="fas fa-print"></i> <?php echo __('Page 4 (SDC)'); ?>
            </a>
            <a href="otj_form.php?id=<?php echo $assignment_id; ?>" target="_blank" class="btn" style="background: var(--primary-blue); color: white;">
                <i class="fas fa-print"></i> <?php echo __('Page 5 (OTJ)'); ?>
            </a>
            <a href="training_record.php?type=full&id=<?php echo $assignment_id; ?>" target="_blank" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> <?php echo __('Full Report'); ?>
            </a>
            <a href="<?php echo ($_SESSION['role'] == 'admin') ? 'assignments.php' : '../trainer/trainees.php'; ?>" class="btn" style="background:#eee; color:#555;"><?php echo __('Back'); ?></a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div style="background: rgba(56, 161, 105, 0.1); color: #2F855A; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid rgba(56, 161, 105, 0.2);">
        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
    
    <!-- Practical/OTJ Stages Section -->
    <div style="grid-column: span 2;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3><?php echo __('Practical & Shop Floor Training Stages'); ?></h3>
                <button onclick="document.getElementById('stage-form').scrollIntoView()" class="btn btn-primary" style="font-size: 0.8rem; padding: 5px 12px;">+ <?php echo __('Add New Stage'); ?></button>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 120px;"><?php echo __('Type'); ?></th>
                            <th><?php echo __('Certified Date'); ?></th>
                            <th><?php echo __('Stage Name'); ?></th>
                            <th style="width: 100px;"><?php echo __('Hours'); ?></th>
                            <th><?php echo __('Remarks'); ?></th>
                            <th style="width: 100px;"><?php echo __('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM training_stages WHERE assignment_id = ? ORDER BY type, certified_date ASC");
                        $stmt->execute([$assignment_id]);
                        while ($st = $stmt->fetch()):
                            $type_label = [
                                'sdc' => '<span class="badge badge-primary">' . __('Practical (SDC)') . '</span>',
                                'otj' => '<span class="badge badge-success">' . __('OTJ (Shop Floor)') . '</span>',
                                'recertification' => '<span class="badge badge-warning">' . __('Recertification') . '</span>'
                            ][$st['type']];
                        ?>
                        <tr>
                            <td><?php echo $type_label; ?></td>
                            <td><?php echo date('d M Y', strtotime($st['certified_date'])); ?></td>
                            <td><strong><?php echo e($st['stage_name']); ?></strong></td>
                            <td><span class="badge badge-info"><?php echo e($st['man_hours']); ?>h</span></td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?php echo e($st['remarks']); ?></td>
                            <td>
                                <div style="display: flex; gap: 15px;">
                                    <a href="training_hub.php?id=<?php echo $assignment_id; ?>&edit_stage=<?php echo $st['id']; ?>#stage-form" style="color: var(--primary-blue);"><i class="fas fa-edit"></i> <?php echo __('Edit'); ?></a>
                                    <a href="training_hub.php?id=<?php echo $assignment_id; ?>&delete_stage=<?php echo $st['id']; ?>" style="color: var(--danger);" onclick="return confirm('<?php echo __('Delete this training record?'); ?>')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($stmt->rowCount() == 0): ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;"><?php echo __('No stages certified yet.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Stage Form (Anchor) -->
            <div id="stage-form" style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 30px;">
                <h4 style="margin-bottom: 20px;"><?php echo $edit_stage ? __('Update Stage Details') : __('Certify New Training Stage'); ?></h4>
                <form method="POST" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                    <?php if ($edit_stage): ?>
                        <input type="hidden" name="stage_id" value="<?php echo $edit_stage['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('Training Type'); ?></label>
                        <select name="type" class="form-control" required>
                            <option value="sdc" <?php echo ($edit_stage && $edit_stage['type'] == 'sdc') ? 'selected' : ''; ?>><?php echo __('Practical (SDC)'); ?></option>
                            <option value="otj" <?php echo ($edit_stage && $edit_stage['type'] == 'otj') ? 'selected' : ''; ?>><?php echo __('OTJ (Shop Floor)'); ?></option>
                            <option value="recertification" <?php echo ($edit_stage && $edit_stage['type'] == 'recertification') ? 'selected' : ''; ?>><?php echo __('Recertification'); ?></option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label"><?php echo __('Stage Name / Course'); ?></label>
                        <input type="text" name="stage_name" class="form-control" value="<?php echo e($edit_stage['stage_name'] ?? ''); ?>" required placeholder="e.g. Soldering Proficiency">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('Man Hours'); ?></label>
                        <input type="number" step="0.5" name="man_hours" class="form-control" value="<?php echo $edit_stage['man_hours'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo __('Certified Date'); ?></label>
                        <input type="date" name="certified_date" class="form-control" value="<?php echo $edit_stage['certified_date'] ?? date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group" style="grid-column: span 3;">
                        <label class="form-label"><?php echo __('Remarks'); ?></label>
                        <input type="text" name="remarks" class="form-control" value="<?php echo e($edit_stage['remarks'] ?? ''); ?>" placeholder="Optional evaluator comments...">
                    </div>
                    <div style="grid-column: span 4; display: flex; gap: 10px; margin-top: 10px;">
                        <button type="submit" name="save_stage" class="btn btn-primary" style="padding: 10px 30px;">
                            <?php echo $edit_stage ? __('Update Record') : __('Save Training Record'); ?>
                        </button>
                        <?php if ($edit_stage): ?>
                            <a href="training_hub.php?id=<?php echo $assignment_id; ?>" class="btn" style="background:#eee; color:#333;"><?php echo __('Cancel'); ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Induction Progress Section -->
    <div id="induction" style="grid-column: span 2;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3><?php echo __('Induction Completion Matrix'); ?></h3>
                <a href="induction_topics.php" class="btn" style="font-size: 0.8rem; background: #EDF2F7; color: var(--primary-blue);"><?php echo __('Manage Master Topics'); ?></a>
            </div>

            <?php
            // Fetch checklist and progress
            $stmt = $pdo->prepare("SELECT day_number, id, section_name, topic_name FROM induction_checklist ORDER BY day_number, id");
            $stmt->execute();
            $all_topics = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT checklist_id FROM trainee_checklist_progress WHERE trainee_id = ?");
            $stmt->execute([$assignment['trainee_id']]);
            $completed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            ?>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <?php foreach ($all_topics as $day => $items): ?>
                <div style="border: 1px solid #edf2f7; border-radius: 10px; overflow: hidden;">
                    <div style="background: var(--primary-blue); color: white; padding: 10px 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                        <span><?php echo __('Day'); ?> <?php echo $day; ?></span>
                        <span style="font-size: 0.75rem; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px;">
                            <?php 
                            $day_total = count($items);
                            $day_done = count(array_intersect(array_column($items, 'id'), $completed_ids));
                            echo "$day_done / $day_total " . __('Done');
                            ?>
                        </span>
                    </div>
                    <div style="max-height: 400px; overflow-y: auto; padding: 10px;">
                        <?php 
                        $curr_sec = "";
                        foreach ($items as $item): 
                            if ($item['section_name'] != $curr_sec):
                                $curr_sec = $item['section_name'];
                                echo "<div style='font-size: 0.75rem; color: var(--text-muted); font-weight: bold; margin: 10px 0 5px; text-transform: uppercase;'>$curr_sec</div>";
                            endif;
                            $is_checked = in_array($item['id'], $completed_ids);
                        ?>
                        <label style="display: flex; align-items: center; padding: 8px 10px; border-radius: 6px; cursor: pointer; border-bottom: 1px solid #f9fafb; font-size: 0.85rem; background: <?php echo $is_checked ? '#f0fff4' : 'transparent'; ?>;">
                            <form method="POST" style="margin:0; display: flex; align-items: center; width: 100%;">
                                <input type="hidden" name="topic_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="is_done" value="<?php echo $is_checked ? '0' : '1'; ?>">
                                <input type="checkbox" <?php echo $is_checked ? 'checked' : ''; ?> onchange="this.form.submit()" style="width: 16px; height: 16px; margin-right: 12px; accent-color: #38A169;">
                                <span style="flex: 1; color: <?php echo $is_checked ? '#2F855A' : '#4A5568'; ?>;"><?php echo e($item['topic_name']); ?></span>
                                <input type="hidden" name="toggle_induction" value="1">
                            </form>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
