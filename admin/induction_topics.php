<?php
// admin/induction_topics.php
require_once '../includes/layout.php';
checkRole('admin');

$message = '';
$edit_topic = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM induction_checklist WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Topic deleted successfully!";
}

// Handle Add/Update
if (isset($_POST['save_topic'])) {
    $id = $_POST['id'] ?? null;
    $day = $_POST['day_number'];
    $section = $_POST['section_name'];
    $topic = $_POST['topic_name'];
    $mins = $_POST['estimated_mins'];
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE induction_checklist SET day_number = ?, section_name = ?, topic_name = ?, estimated_mins = ? WHERE id = ?");
        $stmt->execute([$day, $section, $topic, $mins, $id]);
        $message = "Topic updated successfully!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO induction_checklist (day_number, section_name, topic_name, estimated_mins) VALUES (?, ?, ?, ?)");
        $stmt->execute([$day, $section, $topic, $mins]);
        $message = "Topic added successfully!";
    }
}

// Fetch for Edit
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM induction_checklist WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_topic = $stmt->fetch();
}

renderHeader('Manage Induction Topics');
renderSidebar('admin');
?>

<div class="grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Topic Form -->
    <div class="card">
        <h3><?php echo $edit_topic ? 'Edit Topic' : 'Add New Topic'; ?></h3>
        <form method="POST" style="margin-top: 20px;">
            <?php if ($edit_topic): ?>
                <input type="hidden" name="id" value="<?php echo $edit_topic['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label">Day Number (1-3)</label>
                <input type="number" name="day_number" class="form-control" min="1" max="3" value="<?php echo $edit_topic['day_number'] ?? '1'; ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Section Name</label>
                <input type="text" name="section_name" class="form-control" placeholder="e.g. SAFETY, BASIC CONCEPTS" value="<?php echo e($edit_topic['section_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Topic Name</label>
                <input type="text" name="topic_name" class="form-control" placeholder="e.g. Basics of safety" value="<?php echo e($edit_topic['topic_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Estimated Mins (Optional)</label>
                <input type="number" name="estimated_mins" class="form-control" value="<?php echo $edit_topic['estimated_mins'] ?? ''; ?>">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="save_topic" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> <?php echo $edit_topic ? 'Update Topic' : 'Add Topic'; ?>
                </button>
                <?php if ($edit_topic): ?>
                    <a href="induction_topics.php" class="btn" style="background: #eee; color: #333;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Topics List -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Master Induction Topics</h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Configure Day 1-3 content.</span>
        </div>

        <?php if ($message): ?>
            <div style="background: rgba(56, 161, 105, 0.1); color: #2F855A; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(56, 161, 105, 0.2);">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">Day</th>
                        <th>Section</th>
                        <th>Topic</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM induction_checklist ORDER BY day_number, id");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td style="text-align: center;"><span class="badge badge-info">D<?php echo $row['day_number']; ?></span></td>
                        <td style="font-size: 0.85rem; font-weight: bold; color: var(--primary-blue);"><?php echo e($row['section_name']); ?></td>
                        <td><?php echo e($row['topic_name']); ?></td>
                        <td style="font-size: 0.85rem;"><?php echo $row['estimated_mins'] ? $row['estimated_mins'] . 'm' : '-'; ?></td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="induction_topics.php?edit=<?php echo $row['id']; ?>" style="color: var(--primary-blue);" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="induction_topics.php?delete=<?php echo $row['id']; ?>" style="color: var(--danger);" title="Delete" onclick="return confirm('Delete this topic?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($stmt->rowCount() == 0): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">No induction topics configured.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
