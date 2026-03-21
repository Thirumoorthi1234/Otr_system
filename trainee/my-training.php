<?php
// trainee/my-training.php
require_once '../includes/layout.php';
checkRole('trainee');

$trainee_id = $_SESSION['user_id'];

renderHeader('My Training');
renderSidebar('trainee');

// Fixed Modules as requested
$modules_to_show = [
    [
        'id' => 1,
        'title' => 'Induction Training',
        'icon' => 'fa-book-open-reader',
        'color' => '#4e73df',
        'desc' => 'Essential foundational training including Safety, PPE, 5S, and ESD concepts.'
    ],
    [
        'id' => 2,
        'title' => 'Practical Training-SDC',
        'icon' => 'fa-microchip',
        'color' => '#1cc88a',
        'desc' => 'Hands-on skill development center training for technical proficiency.'
    ],
    [
        'id' => 3,
        'title' => 'On the job training- shop floor',
        'icon' => 'fa-industry',
        'color' => '#f6c23e',
        'desc' => 'Real-world production floor training and operational experience.'
    ]
];
?>

<div class="training-container" style="padding: 20px;">
    <div style="margin-bottom: 30px;">
        <h2 style="font-weight: 800; color: var(--brand-navy); margin-bottom: 10px;">Your Training Modules</h2>
        <p style="color: var(--text-muted);">Complete your assigned modules to progress in your training journey.</p>
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
                        <?php echo strtoupper(str_replace('_', ' ', $status)); ?>
                    </span>
                </div>
                
                <?php if ($status == 'completed'): ?>
                    <a href="course-material.php?id=<?php echo $mod['id']; ?>" class="btn" style="background: #edf2f7; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 8px; border-radius: 12px; padding: 10px 20px;">
                        <i class="fas fa-eye"></i> Revisit Module
                    </a>
                <?php else: ?>
                    <a href="course-material.php?id=<?php echo $mod['id']; ?>" class="btn btn-primary" style="padding: 10px 25px; border-radius: 12px; font-weight: 700; box-shadow: 0 8px 15px <?php echo $mod['color']; ?>30; background: <?php echo $mod['color']; ?>; border: none;">
                        Start Training <i class="fas fa-arrow-right" style="margin-left: 8px; font-size: 0.8rem;"></i>
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
