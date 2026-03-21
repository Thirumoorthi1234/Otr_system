<?php
// admin/modules.php
require_once '../includes/layout.php';
checkRole('admin');

$action = $_GET['action'] ?? 'list';
$message = '';

if (isset($_POST['save_module'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $total_hours = $_POST['total_hours'];
    
    if ($_POST['module_id']) {
        $stmt = $pdo->prepare("UPDATE training_modules SET title=?, description=?, category=?, total_hours=? WHERE id=?");
        $stmt->execute([$title, $description, $category, $total_hours, $_POST['module_id']]);
        $message = "Module updated!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO training_modules (title, description, category, total_hours) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $category, $total_hours]);
        $message = "Module created!";
    }
    $action = 'list';
}

if ($action == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM training_modules WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $message = "Module deleted!";
    $action = 'list';
}

renderHeader('Module Management');
renderSidebar('admin');
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3><?php echo $action == 'add' ? 'Create Training Module' : ($action == 'edit' ? 'Edit Module' : 'Training Modules'); ?></h3>
        <?php if ($action == 'list'): ?>
            <a href="modules.php?action=add" class="btn btn-primary">Create Module</a>
        <?php else: ?>
            <a href="modules.php" class="btn" style="background: #4A5568; color: white;">Back to List</a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div style="background: rgba(56, 161, 105, 0.1); color: #48BB78; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(56, 161, 105, 0.2);">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">Title</th>
                        <th style="text-align: center;">Category</th>
                        <th style="text-align: center;">Hours</th>
                        <th style="text-align: center;">Exams</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT m.*, (SELECT COUNT(*) FROM exams WHERE module_id = m.id) as exam_count FROM training_modules m");
                    while ($m = $stmt->fetch()):
                    ?>
                    <tr>
                        <td style="text-align: left;"><strong><?php echo e($m['title']); ?></strong></td>
                        <td style="text-align: center;"><span class="badge badge-info"><?php echo e($m['category']); ?></span></td>
                        <td style="text-align: center; color: var(--text-muted);"><?php echo e($m['total_hours']); ?>h</td>
                        <td style="text-align: center;"><?php echo $m['exam_count']; ?></td>
                        <td style="text-align: center;">
                            <a href="modules.php?action=edit&id=<?php echo $m['id']; ?>" style="color: var(--primary-blue); margin-right: 15px;"><i class="fas fa-edit"></i></a>
                            <a href="modules.php?action=delete&id=<?php echo $m['id']; ?>" style="color: var(--danger);" onclick="return confirm('Delete module?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: 
        $m = ['id' => '', 'title' => '', 'description' => '', 'category' => '', 'total_hours' => '4'];
        if ($action == 'edit' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM training_modules WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $m = $stmt->fetch();
        }
    ?>
    <form method="POST" action="modules.php">
        <input type="hidden" name="module_id" value="<?php echo $m['id']; ?>">
        <div class="form-group">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo e($m['title']); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"><?php echo e($m['description']); ?></textarea>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control" value="<?php echo e($m['category']); ?>" placeholder="e.g. Safety, Lean" required>
            </div>
            <div class="form-group">
                <label class="form-label">Hours</label>
                <input type="number" name="total_hours" class="form-control" value="<?php echo e($m['total_hours']); ?>" required>
            </div>
        </div>
        <button type="submit" name="save_module" class="btn btn-primary" style="margin-top: 10px;">Save Module</button>
    </form>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
