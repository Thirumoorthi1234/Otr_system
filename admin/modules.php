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
    
    $curriculum_path = $_POST['current_curriculum'] ?? null;
    if (isset($_FILES['curriculum']) && $_FILES['curriculum']['error'] == 0) {
        $uploadDir = '../assets/curriculum/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['curriculum']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['curriculum']['tmp_name'], $targetPath)) {
            $curriculum_path = 'assets/curriculum/' . $fileName;
        }
    }

    if ($_POST['module_id']) {
        $stmt = $pdo->prepare("UPDATE training_modules SET title=?, description=?, category=?, total_hours=?, curriculum_path=? WHERE id=?");
        $stmt->execute([$title, $description, $category, $total_hours, $curriculum_path, $_POST['module_id']]);
        $message = __("module_updated");
    } else {
        $stmt = $pdo->prepare("INSERT INTO training_modules (title, description, category, total_hours, curriculum_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $category, $total_hours, $curriculum_path]);
        $message = __("module_created");
    }
    $action = 'list';
}

if ($action == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM training_modules WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $message = __("module_deleted");
    $action = 'list';
}

renderHeader(__('module_management'));
renderSidebar('admin');
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3><?php echo $action == 'add' ? __('create_training_module') : ($action == 'edit' ? __('edit_module') : __('training_modules')); ?></h3>
        <?php if ($action == 'list'): ?>
            <a href="modules.php?action=add" class="btn btn-primary"><?php echo __('create_module'); ?></a>
        <?php else: ?>
            <a href="modules.php" class="btn" style="background: #4A5568; color: white;"><?php echo __('back_to_list'); ?></a>
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
                        <th style="text-align: left;"><?php echo __('title'); ?></th>
                        <th style="text-align: center;"><?php echo __('category'); ?></th>
                        <th style="text-align: center;"><?php echo __('hours'); ?></th>
                        <th style="text-align: center;"><?php echo __('exams'); ?></th>
                        <th style="text-align: center;"><?php echo __('actions'); ?></th>
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
                            <a href="modules.php?action=delete&id=<?php echo $m['id']; ?>" style="color: var(--danger);" onclick="return confirm('<?php echo __('delete_module_confirm'); ?>')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: 
        $m = [
            'id' => '', 
            'title' => '', 
            'description' => '', 
            'category' => '', 
            'total_hours' => '4',
            'curriculum_path' => ''
        ];
        if ($action == 'edit' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM training_modules WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $m = $stmt->fetch();
        }
    ?>
    <form method="POST" action="modules.php" enctype="multipart/form-data">
        <input type="hidden" name="module_id" value="<?php echo $m['id'] ?? ''; ?>">
        <input type="hidden" name="current_curriculum" value="<?php echo $m['curriculum_path'] ?? ''; ?>">
        <div class="form-group">
            <label class="form-label"><?php echo __('title'); ?></label>
            <input type="text" name="title" class="form-control" value="<?php echo e($m['title']); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label"><?php echo __('description'); ?></label>
            <textarea name="description" class="form-control" rows="4"><?php echo e($m['description']); ?></textarea>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label"><?php echo __('category'); ?></label>
                <input type="text" name="category" class="form-control" value="<?php echo e($m['category']); ?>" placeholder="<?php echo __('module_category_placeholder'); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo __('hours'); ?></label>
                <input type="number" name="total_hours" class="form-control" value="<?php echo e($m['total_hours']); ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?php echo __('Curriculum / Training Material (PDF)'); ?></label>
            <input type="file" name="curriculum" class="form-control" accept=".pdf">
<?php if (!empty($m['curriculum_path'])): ?>
                <div style="margin-top: 10px; font-size: 0.85rem; color: #3182CE;">
                    <i class="fas fa-file-pdf"></i> <?php echo __('Currently'); ?>: <a href="<?php echo BASE_URL . $m['curriculum_path']; ?>" target="_blank"><?php echo basename($m['curriculum_path']); ?></a>
                </div>
<?php endif; ?>
        </div>
        <button type="submit" name="save_module" class="btn btn-primary" style="margin-top: 10px;"><?php echo __('save_module'); ?></button>
    </form>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
