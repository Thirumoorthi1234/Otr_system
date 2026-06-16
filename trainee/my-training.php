<?php
// trainee/my-training.php
require_once '../includes/layout.php';
checkRole('trainee');

$trainee_id = $_SESSION['user_id'];

renderHeader(__('my_training'));
renderSidebar('trainee');

// Fetch all modules dynamically from the database
$stmt_modules = $pdo->query("SELECT * FROM training_modules ORDER BY id ASC");
$db_modules = $stmt_modules->fetchAll();

$modules_to_show = [];
foreach ($db_modules as $m) {
    $id = $m['id'];
    
    // Translation keys mapping for standard/seeded modules
    $title_key = '';
    $desc_key = '';
    if ($id == 1) {
        $title_key = 'induction_training';
        $desc_key = 'induction_training_desc';
    } elseif ($id == 2) {
        $title_key = 'practical_training_sdc';
        $desc_key = 'practical_training_sdc_desc';
    } elseif ($id == 3) {
        // Correcting key to match translation config (ojt vs otj)
        $title_key = 'ojt_training_shop_floor';
        $desc_key = 'ojt_training_shop_floor_desc';
    } else {
        // Dynamically check translation based on slug/normalized title or fallback to DB
        $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($m['title'])));
        $title_key = $normalized;
        $desc_key = $normalized . '_desc';
    }
    
    $title = __($title_key, $m['title']);
    $desc = __($desc_key, $m['description']);
    
    // Choose icon and color based on ID/Category
    $category = strtolower(trim($m['category'] ?? ''));
    if ($id == 1) {
        $icon = 'fa-book-open-reader';
        $color = '#4e73df';
    } elseif ($id == 2) {
        $icon = 'fa-microchip';
        $color = '#1cc88a';
    } elseif ($id == 3) {
        $icon = 'fa-industry';
        $color = '#f6c23e';
    } elseif (strpos($category, 'ai') !== false || strpos($category, 'digital') !== false) {
        if (strpos(strtolower($m['title']), 'robot') !== false || strpos(strtolower($m['title']), 'rpa') !== false) {
            $icon = 'fa-robot';
            $color = '#ec4899';
        } elseif (strpos(strtolower($m['title']), 'predictive') !== false || strpos(strtolower($m['title']), 'maintenance') !== false) {
            $icon = 'fa-brain';
            $color = '#a855f7';
        } else {
            $icon = 'fa-laptop-code';
            $color = '#36b9cc';
        }
    } else {
        // Dynamic fallback selection
        $colors = ['#36b9cc', '#a855f7', '#ec4899', '#f43f5e', '#6366f1', '#10b981', '#f59e0b'];
        $icons = ['fa-graduation-cap', 'fa-cogs', 'fa-chart-line', 'fa-qrcode', 'fa-wrench', 'fa-desktop'];
        $color = $colors[$id % count($colors)];
        $icon = $icons[$id % count($icons)];
    }
    
    $modules_to_show[] = [
        'id' => $id,
        'title' => $title,
        'icon' => $icon,
        'color' => $color,
        'desc' => $desc
    ];
}
?>

<div class="training-container" style="padding: 20px;">
    <div style="margin-bottom: 30px;">
        <h2 style="font-weight: 800; color: var(--brand-navy); margin-bottom: 10px;"><?php echo __('your_training_modules'); ?></h2>
        <p style="color: var(--text-muted);"><?php echo __('complete_assigned_modules_desc'); ?></p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
        <?php foreach ($modules_to_show as $mod): 
            // Check status from assignments table
            $stmt = $pdo->prepare("SELECT status FROM assignments WHERE trainee_id = ? AND module_id = ?");
            $stmt->execute([$trainee_id, $mod['id']]);
            $status_row = $stmt->fetch();
            $status = $status_row ? $status_row['status'] : 'not_assigned';
            
            // If not assigned yet, we might want to auto-assign for demo purposes or just show it
            if (!$status_row && $mod['id'] == 1) {
                // Auto-assign Induction if not present
                //$pdo->prepare("INSERT INTO assignments (trainee_id, trainer_id, module_id, status, assigned_date) VALUES (?, 1, ?, 'not_started', CURDATE())")->execute([$trainee_id, $mod['id']]);
                //$status = 'not_started';
            }
        ?>
        <div class="training-box" style="background: white; border-radius: 20px; padding: 35px; border: 1px solid var(--border-color); box-shadow: 0 15px 35px rgba(0,0,0,0.05); transition: all 0.3s ease; position: relative; overflow: hidden; display: flex; flex-direction: column;">
            <div style="position: absolute; top: -20px; right: -20px; width: 120px; height: 120px; background: <?php echo $mod['color']; ?>; opacity: 0.05; border-radius: 50%;"></div>
            
            <div style="width: 70px; height: 70px; background: <?php echo $mod['color']; ?>15; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 25px;">
                <i class="fas <?php echo $mod['icon']; ?>" style="font-size: 2rem; color: <?php echo $mod['color']; ?>;"></i>
            </div>
            
            <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--brand-navy); margin-bottom: 15px;"><?php echo $mod['title']; ?></h3>
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 30px; flex-grow: 1;"><?php echo $mod['desc']; ?></p>
            
            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 25px; border-top: 1px dashed var(--border-color);">
                <div>
                    <span class="badge <?php 
                        echo ($status == 'completed') ? 'badge-success' : (($status == 'in_progress') ? 'badge-info' : 'badge-warning'); 
                    ?>" style="font-size: 0.75rem;">
                        <?php echo strtoupper(__(str_replace('_', ' ', $status))); ?>
                    </span>
                </div>
                
                <?php if ($status == 'completed'): ?>
                    <a href="course-material.php?id=<?php echo $mod['id']; ?>" class="btn" style="background: #edf2f7; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 20px;">
                        <i class="fas fa-eye"></i> <?php echo __('revisit_module'); ?>
                    </a>
                <?php else: ?>
                    <a href="course-material.php?id=<?php echo $mod['id']; ?>" class="btn btn-primary" style="padding: 10px 25px; border-radius: 12px; font-weight: 700; box-shadow: 0 8px 15px <?php echo $mod['color']; ?>30; background: <?php echo $mod['color']; ?>; border: none;">
                        <?php echo __('start_training'); ?> <i class="fas fa-arrow-right" style="margin-left: 8px; font-size: 0.8rem;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .training-box:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 50px rgba(0,0,0,0.1) !important;
        border-color: var(--primary-blue);
    }
    .badge-info { background: #3abaf4; color: white; }
</style>

<?php renderFooter(); ?>
