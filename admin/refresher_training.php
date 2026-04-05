<?php
// admin/refresher_training.php — Refresher Training Management
require_once '../includes/layout.php';
checkRole(['admin', 'trainer']);

$message = '';
$role = $_SESSION['role'];

// Handle assign action
if (isset($_POST['assign_refresher'])) {
    $trainee_id = (int)$_POST['trainee_id'];
    $module_id  = (int)$_POST['module_id'];
    $due_date   = $_POST['due_date'];
    $notes      = trim($_POST['notes'] ?? '');
    $assigned_by = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO refresher_training (trainee_id, module_id, due_date, notes, assigned_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$trainee_id, $module_id, $due_date, $notes, $assigned_by]);
    $message = 'Refresher training assigned successfully!';
}

// Handle mark complete
if (isset($_GET['mark_complete'])) {
    $id = (int)$_GET['mark_complete'];
    $pdo->prepare("UPDATE refresher_training SET status='completed', completed_at=NOW() WHERE id=?")->execute([$id]);
    $message = 'Marked as completed.';
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM refresher_training WHERE id=?")->execute([$id]);
    $message = 'Refresher record deleted.';
}

// Update overdue status automatically
$pdo->exec("UPDATE refresher_training SET status='overdue' WHERE due_date < CURDATE() AND status='pending'");

// Fetch all refresher records
$filter = $_GET['filter'] ?? 'all';
$where = $filter !== 'all' ? "WHERE rt.status = " . $pdo->quote($filter) : '';
// If trainer: only show their trainees
if ($role === 'trainer') {
    $trainer_id = $_SESSION['user_id'];
    $where = $where ? "$where AND a.trainer_id = $trainer_id" : "WHERE a.trainer_id = $trainer_id";
}

$records = $pdo->query("
    SELECT rt.*, 
           u.full_name as trainee_name, u.employee_id,
           m.title as module_title,
           ab.full_name as assigned_by_name,
           DATEDIFF(rt.due_date, CURDATE()) as days_remaining
    FROM refresher_training rt
    JOIN users u ON rt.trainee_id = u.id
    JOIN training_modules m ON rt.module_id = m.id
    LEFT JOIN users ab ON rt.assigned_by = ab.id
    LEFT JOIN assignments a ON (a.trainee_id = rt.trainee_id AND a.module_id = rt.module_id)
    $where
    ORDER BY rt.due_date ASC
")->fetchAll();

// Fetch trainees & modules for the form
$trainees = $pdo->query("SELECT id, full_name, employee_id FROM users WHERE role='trainee' AND status='active' ORDER BY full_name")->fetchAll();
$modules  = $pdo->query("SELECT id, title FROM training_modules ORDER BY title")->fetchAll();

renderHeader('Refresher Training');
renderSidebar($role);
?>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 25px; align-items: start;">
    <!-- Assign Form -->
    <div class="card">
        <h3 style="margin-bottom:20px;"><i class="fas fa-redo" style="color:var(--primary-blue);"></i> Assign Refresher</h3>
        <?php if ($message): ?>
        <div style="background:rgba(16,185,129,0.1); color:#065f46; border:1px solid rgba(16,185,129,0.3); border-radius:8px; padding:10px 14px; margin-bottom:15px; font-weight:600; font-size:0.87rem;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Trainee</label>
                <select name="trainee_id" class="form-control" required>
                    <option value="">-- Select Trainee --</option>
                    <?php foreach ($trainees as $t): ?>
                    <option value="<?php echo $t['id']; ?>"><?php echo e($t['full_name']); ?> (<?php echo e($t['employee_id']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Training Module</label>
                <select name="module_id" class="form-control" required>
                    <option value="">-- Select Module --</option>
                    <?php foreach ($modules as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo e($m['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Notes (Optional)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Reason for refresher or specific topics to cover..."></textarea>
            </div>
            <button type="submit" name="assign_refresher" class="btn btn-primary" style="width:100%;">
                <i class="fas fa-plus-circle"></i> Assign Refresher
            </button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
            <h3><i class="fas fa-list-check" style="color:var(--primary-blue);"></i> Refresher Schedule</h3>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php foreach (['all' => 'All', 'pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'overdue' => 'Overdue'] as $key => $label): ?>
                <a href="refresher_training.php?filter=<?php echo $key; ?>" 
                   style="padding:6px 14px; border-radius:8px; font-weight:700; font-size:0.8rem; text-decoration:none;
                          <?php echo $filter === $key ? 'background:var(--primary-blue); color:#fff;' : 'background:var(--bg-color,#f1f5f9); color:var(--text-muted); border:1px solid var(--border-color);'; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($records)): ?>
        <div style="text-align:center; padding:50px; color:var(--text-muted);">
            <i class="fas fa-calendar-check" style="font-size:2.5rem; opacity:0.3; margin-bottom:15px; display:block;"></i>
            No refresher training records found.
        </div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Module</th>
                        <th>Due Date</th>
                        <th>Countdown</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $r):
                    $days = (int)$r['days_remaining'];
                    $dueBadge = '';
                    if ($r['status'] !== 'completed') {
                        if ($days < 0)       $dueBadge = "<span style='color:#ef4444; font-weight:800;'><i class='fas fa-exclamation-triangle'></i> " . abs($days) . "d overdue</span>";
                        elseif ($days == 0)  $dueBadge = "<span style='color:#ef4444; font-weight:800;'><i class='fas fa-fire'></i> TODAY</span>";
                        elseif ($days <= 3)  $dueBadge = "<span style='color:#f59e0b; font-weight:800;'><i class='fas fa-clock'></i> {$days}d left</span>";
                        else                 $dueBadge = "<span style='color:#10b981;'><i class='fas fa-calendar'></i> {$days}d left</span>";
                    } else {
                        $dueBadge = "<span style='color:#10b981;'>✓ Done</span>";
                    }
                    $statusColors = ['pending'=>'#f59e0b','completed'=>'#10b981','overdue'=>'#ef4444','in_progress'=>'#3b82f6'];
                    $sc = $statusColors[$r['status']] ?? '#94a3b8';
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700; color:var(--text-main);"><?php echo e($r['trainee_name']); ?></div>
                        <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo e($r['employee_id']); ?></div>
                    </td>
                    <td style="font-size:0.85rem;"><?php echo e($r['module_title']); ?></td>
                    <td style="font-size:0.85rem; font-weight:600;"><?php echo date('d M Y', strtotime($r['due_date'])); ?></td>
                    <td><?php echo $dueBadge; ?></td>
                    <td>
                        <span style="background:<?php echo $sc; ?>20; color:<?php echo $sc; ?>; padding:4px 10px; border-radius:7px; font-weight:800; font-size:0.75rem;">
                            <?php echo strtoupper(str_replace('_', ' ', $r['status'])); ?>
                        </span>
                    </td>
                    <td style="font-size:0.8rem; color:var(--text-muted); max-width:160px;"><?php echo e($r['notes'] ?? '-'); ?></td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <?php if ($r['status'] !== 'completed'): ?>
                            <a href="refresher_training.php?mark_complete=<?php echo $r['id']; ?>&filter=<?php echo $filter; ?>" 
                               class="btn" style="background:#dcfce7; color:#16a34a; font-size:0.72rem; padding:5px 10px;"
                               onclick="return confirm('Mark as completed?')">
                               <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                            <a href="refresher_training.php?delete=<?php echo $r['id']; ?>&filter=<?php echo $filter; ?>"
                               class="btn" style="background:#fef2f2; color:#ef4444; font-size:0.72rem; padding:5px 10px;"
                               onclick="return confirm('Delete this refresher record?')">
                               <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php renderFooter(); ?>
