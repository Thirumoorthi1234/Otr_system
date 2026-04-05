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
    $message = __("topic_deleted_successfully");
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
        $message = __("topic_updated_successfully");
    } else {
        $stmt = $pdo->prepare("INSERT INTO induction_checklist (day_number, section_name, topic_name, estimated_mins) VALUES (?, ?, ?, ?)");
        $stmt->execute([$day, $section, $topic, $mins]);
        $message = __("topic_added_successfully");
    }
}

// Fetch for Edit
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM induction_checklist WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_topic = $stmt->fetch();
}

renderHeader(__('manage_induction_topics'));
renderSidebar('admin');
?>

<div class="grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Topic Form -->
    <div class="card">
        <h3><?php echo $edit_topic ? __('edit_topic') : __('add_new_topic'); ?></h3>
        <form method="POST" style="margin-top: 20px;">
            <?php if ($edit_topic): ?>
                <input type="hidden" name="id" value="<?php echo $edit_topic['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label"><?php echo __('day_number_1_3'); ?></label>
                <input type="number" name="day_number" class="form-control" min="1" max="3" value="<?php echo $edit_topic['day_number'] ?? '1'; ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label"><?php echo __('section_name'); ?></label>
                <input type="text" name="section_name" class="form-control" placeholder="<?php echo __('eg_safety_basic_concepts'); ?>" value="<?php echo e($edit_topic['section_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label"><?php echo __('topic_name'); ?></label>
                <input type="text" name="topic_name" class="form-control" placeholder="<?php echo __('eg_basics_of_safety'); ?>" value="<?php echo e($edit_topic['topic_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label"><?php echo __('estimated_mins_optional'); ?></label>
                <input type="number" name="estimated_mins" class="form-control" value="<?php echo $edit_topic['estimated_mins'] ?? ''; ?>">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="save_topic" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> <?php echo $edit_topic ? __('update_topic') : __('add_topic'); ?>
                </button>
                <?php if ($edit_topic): ?>
                    <a href="induction_topics.php" class="btn" style="background: #eee; color: #333;"><?php echo __('Cancel'); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Topics List -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3><?php echo __('master_induction_topics'); ?></h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo __('configure_day_1_3_content'); ?></span>
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
                        <th style="width: 60px;"><?php echo __('Day'); ?></th>
                        <th><?php echo __('section'); ?></th>
                        <th><?php echo __('topic'); ?></th>
                        <th><?php echo __('time'); ?></th>
                        <th><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
 bitumen
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
                                <a href="induction_topics.php?edit=<?php echo $row['id']; ?>" style="color: var(--primary-blue);" title="<?php echo __('Edit'); ?>"><i class="fas fa-edit"></i></a>
                                <a href="induction_topics.php?delete=<?php echo $row['id']; ?>" style="color: var(--danger);" title="<?php echo __('Delete'); ?>" onclick="return confirm('<?php echo __('delete_topic_confirm'); ?>')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($stmt->rowCount() == 0): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;"><?php echo __('no_induction_topics_configured'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
