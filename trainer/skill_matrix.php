<?php
// trainer/skill_matrix.php — Skill Matrix across trainees and modules
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];

// Get all trainees assigned to this trainer (unique)
$trainees = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.employee_id, u.photo_path, u.department, u.batch_number
    FROM assignments a
    JOIN users u ON a.trainee_id = u.id
    WHERE a.trainer_id = ? AND u.status = 'active'
    ORDER BY u.full_name ASC
");
$trainees->execute([$trainer_id]);
$trainees = $trainees->fetchAll();

// Get all modules assigned to this trainer's trainees
$modules = $pdo->prepare("
    SELECT DISTINCT m.id, m.title, m.category
    FROM assignments a
    JOIN training_modules m ON a.module_id = m.id
    WHERE a.trainer_id = ?
    ORDER BY m.category, m.title
");
$modules->execute([$trainer_id]);
$modules = $modules->fetchAll();

// Build status map [trainee_id][module_id] => ['status', 'exam_status', 'score']
$statusMap = [];
$assignments = $pdo->prepare("
    SELECT a.trainee_id, a.module_id, a.status as assign_status,
           er.status as exam_status, er.score
    FROM assignments a
    LEFT JOIN (
        SELECT er2.trainee_id, e.module_id, er2.status, er2.score
        FROM exam_results er2
        JOIN exams e ON er2.exam_id = e.id
        WHERE er2.id = (SELECT MAX(er3.id) FROM exam_results er3 WHERE er3.trainee_id = er2.trainee_id)
    ) er ON (er.trainee_id = a.trainee_id AND er.module_id = a.module_id)
    WHERE a.trainer_id = ?
");
$assignments->execute([$trainer_id]);
foreach ($assignments->fetchAll() as $row) {
    $statusMap[$row['trainee_id']][$row['module_id']] = [
        'assign' => $row['assign_status'],
        'exam'   => $row['exam_status'],
        'score'  => $row['score'],
    ];
}

// Filter
$dept_filter  = $_GET['dept'] ?? '';
$batch_filter = $_GET['batch'] ?? '';

if ($dept_filter) {
    $trainees = array_filter($trainees, fn($t) => $t['department'] === $dept_filter);
}
if ($batch_filter) {
    $trainees = array_filter($trainees, fn($t) => $t['batch_number'] === $batch_filter);
}

// Unique depts & batches for filter
$depts   = array_unique(array_filter(array_column($trainees, 'department')));
$batches = array_unique(array_filter(array_column($trainees, 'batch_number')));

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="skill_matrix_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header Row
    $headers = ['Employee ID', 'Full Name'];
    foreach ($modules as $mod) {
        $headers[] = $mod['title'];
    }
    fputcsv($output, $headers);
    
    // Data Rows
    foreach ($trainees as $t) {
        $row = [$t['employee_id'], $t['full_name']];
        foreach ($modules as $mod) {
            $st = $statusMap[$t['id']][$mod['id']] ?? null;
            if (!$st) {
                $row[] = 'Not Assigned';
            } else {
                if ($st['exam'] === 'pass') {
                    $row[] = 'Passed (' . $st['score'] . '%)';
                } elseif ($st['exam'] === 'fail') {
                    $row[] = 'Failed (' . $st['score'] . '%)';
                } elseif ($st['assign'] === 'in_progress') {
                    $row[] = 'In Progress';
                } else {
                    $row[] = 'Assigned';
                }
            }
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

renderHeader('Skill Matrix');
renderSidebar('trainer');
?>

<style>
    /* Elite Corporate Styling */
    .dashboard-container { padding: 30px; font-family: 'Plus Jakarta Sans', sans-serif; }
    .matrix-container { 
        background: #FFFFFF; 
        border-radius: 16px; 
        border: 1px solid #E2E8F0; 
        overflow: hidden; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        position: relative;
    }

    .matrix-scroll-wrap { 
        overflow: auto; 
        max-height: 70vh; 
        width: 100%;
    }

    .matrix-table { border-collapse: separate; border-spacing: 0; width: 100%; }
    
    /* Responsive Space Absorber */
    .matrix-table .dummy-col { width: 100%; min-width: 20px; border-right: none; }
    .matrix-table td:hover::after,
    .matrix-table th:hover::after {
        content: ""; position: absolute; background-color: rgba(99, 102, 241, 0.05);
        left: 0; right: 0; top: -5000px; bottom: -5000px; z-index: -1; pointer-events: none;
    }
    .matrix-table td:hover::before {
        content: ""; position: absolute; background-color: rgba(99, 102, 241, 0.05);
        left: -5000px; right: -5000px; top: 0; bottom: 0; z-index: -1; pointer-events: none;
    }

    /* Professional Headers: Ultra-Compact High Density */
    .matrix-table thead th { 
        position: sticky; top: 0; z-index: 100;
        background: #F8FAFC; 
        border-bottom: 2px solid #E2E8F0;
        vertical-align: bottom;
        height: 110px; /* Further reduced for absolute efficiency */
        padding: 0;
    }

    .matrix-table .fixed-col { 
        position: sticky; left: 0; z-index: 110;
        background: #FFFFFF; 
        border-right: 2px solid #E2E8F0;
        width: 550px; /* Locked optimal size */
        min-width: 550px; 
        padding: 30px 65px;
    }
    .matrix-table thead .fixed-col { z-index: 70; background: #F8FAFC; padding-bottom: 20px; }

    /* Elite Angled Architecture */
    .th-angled-container {
        width: 108px; /* Condensed for better density */
        position: relative;
    }
    .th-angled-inner {
        position: absolute;
        bottom: 12px;
        left: 20px;
        transform: rotate(-45deg);
        transform-origin: left bottom;
        white-space: nowrap;
        font-size: 0.68rem;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        max-width: 140px;
    }

    .matrix-cell { 
        width: 42px; 
        height: 48px; 
        text-align: center; 
        border-right: 1px solid #F1F5F9;
        border-bottom: 1px solid #F1F5F9;
        position: relative;
        overflow: hidden;
    }

    /* Professional Status Badges */
    .status-badge {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    .status-badge i { font-size: 0.75rem; }
    
    .badge-pass { background: rgba(16, 185, 129, 0.1); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.2); }
    .badge-fail { background: rgba(239, 68, 68, 0.1); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    .badge-progress { background: rgba(245, 158, 11, 0.1); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.2); }
    .badge-assigned { background: rgba(99, 102, 241, 0.1); color: #6366F1; border: 1px solid rgba(99, 102, 241, 0.2); }
    .badge-empty { color: #E2E8F0; font-size: 0.7rem; }

    .trainee-card { display: flex; align-items: center; gap: 15px; }
    .trainee-photo { width: 42px; height: 42px; border-radius: 12px; object-fit: cover; }
    .trainee-info { display: flex; flex-direction: column; }
    .trainee-name { font-weight: 800; color: #0F172A; font-size: 0.92rem; }
    .trainee-id { font-size: 0.75rem; color: #64748B; font-weight: 600; }

    /* Category Headers */
    .category-section { background: #F1F5F9; font-weight: 900; font-size: 0.7rem; color: #475569; letter-spacing: 1px; text-transform: uppercase; padding: 8px 25px; }
</style>

<!-- Filters -->
<div class="card" style="margin-bottom:20px; padding:16px 20px;">
    <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
        <div class="form-group" style="margin:0; flex:1; min-width:140px;">
            <label class="form-label" style="font-size:0.8rem;">Department</label>
            <select name="dept" class="form-control" style="padding:8px 12px;">
                <option value="">All Departments</option>
                <?php foreach ($depts as $d): ?>
                <option value="<?php echo e($d); ?>" <?php echo $dept_filter === $d ? 'selected' : ''; ?>><?php echo e($d); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0; flex:1; min-width:140px;">
            <label class="form-label" style="font-size:0.8rem;">Batch</label>
            <select name="batch" class="form-control" style="padding:8px 12px;">
                <option value="">All Batches</option>
                <?php foreach ($batches as $b): ?>
                <option value="<?php echo e($b); ?>" <?php echo $batch_filter === $b ? 'selected' : ''; ?>><?php echo e($b); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="padding:9px 20px;">
            <i class="fas fa-filter"></i> Filter
        </button>
        <a href="skill_matrix.php" class="btn" style="background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-muted); padding:9px 20px; font-size:0.85rem;">
            <i class="fas fa-times"></i> Clear
        </a>
        <a href="skill_matrix.php?export=csv<?php echo $dept_filter ? '&dept='.urlencode($dept_filter) : ''; ?><?php echo $batch_filter ? '&batch='.urlencode($batch_filter) : ''; ?>" 
           class="btn" style="background:#10b981; color:#fff; padding:9px 20px; font-size:0.85rem; margin-left:auto;">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </form>
</div>
<!-- Legend -->
<div class="matrix-legend" style="display: flex; gap: 20px; margin-bottom: 25px;">
    <div class="legend-item"><span class="status-badge badge-pass"><i class="fa-solid fa-check"></i></span> <?php echo __('Passed'); ?></div>
    <div class="legend-item"><span class="status-badge badge-fail"><i class="fa-solid fa-xmark"></i></span> <?php echo __('Failed'); ?></div>
    <div class="legend-item"><span class="status-badge badge-progress"><i class="fa-solid fa-hourglass-half"></i></span> <?php echo __('In Progress'); ?></div>
    <div class="legend-item"><span class="status-badge badge-assigned"><i class="fa-solid fa-circle"></i></span> <?php echo __('Assigned'); ?></div>
    <div class="legend-item" style="color: #64748B; font-weight: 800; font-size: 0.72rem; letter-spacing:0.5px;"><span class="badge-empty"><i class="fa-solid fa-minus"></i></span> <?php echo __('Not Assigned'); ?></div>
</div>

<?php if (empty($trainees) || empty($modules)): ?>
<div class="matrix-container" style="text-align:center; padding:100px 20px;">
    <div style="width: 80px; height: 80px; background: #F8FAFC; color: #CBD5E1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 25px;">
        <i class="fa-solid fa-table-cells"></i>
    </div>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: #0F172A; margin-bottom: 10px;"><?php echo __('No Matrix Data'); ?></h2>
    <p style="color: #64748B; font-weight: 600; font-size: 1rem;">Complete assignments to populate the trainer matrix.</p>
</div>
<?php else: ?>

<!-- Matrix Table -->
<div class="matrix-container">
    <div class="matrix-scroll-wrap">
        <table class="matrix-table">
            <thead>
                <tr>
                    <th class="fixed-col" style="vertical-align: middle; padding-bottom: 20px;">
                        <span style="font-size: 0.72rem; font-weight: 900; color: #0F172A; text-transform: uppercase; letter-spacing: 1px;"><?php echo __('Trainees'); ?></span>
                    </th>
                    <?php foreach ($modules as $mod): ?>
                    <th>
                        <div class="th-angled-container">
                            <div class="th-angled-inner"><?php echo e($mod['title']); ?></div>
                        </div>
                    </th>
                    <?php endforeach; ?>
                    <th class="dummy-col"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trainees as $t): ?>
                <tr>
                    <td class="fixed-col">
                        <div class="trainee-card">
                            <?php if ($t['photo_path']): ?>
                                <img src="<?php echo BASE_URL . e($t['photo_path']); ?>" class="trainee-photo">
                            <?php else: ?>
                                <div class="trainee-photo" style="display:flex; align-items:center; justify-content:center; background:#F1F5F9; font-size:1.2rem; color:#94A3B8;"><i class="fa-solid fa-user"></i></div>
                            <?php endif; ?>
                            <div class="trainee-info">
                                <span class="trainee-name"><?php echo e($t['full_name']); ?></span>
                                <span class="trainee-id"><?php echo e($t['employee_id']); ?></span>
                            </div>
                        </div>
                    </td>
                    <?php foreach ($modules as $mod): 
                        $st = $statusMap[$t['id']][$mod['id']] ?? null;
                    ?>
                    <td class="matrix-cell">
                        <?php if ($st): ?>
                            <?php if ($st['exam'] === 'pass'): ?>
                                <span class="status-badge badge-pass" title="SCORE: <?php echo $st['score']; ?>%"><i class="fa-solid fa-check"></i></span>
                            <?php elseif ($st['exam'] === 'fail'): ?>
                                <span class="status-badge badge-fail" title="SCORE: <?php echo $st['score']; ?>%"><i class="fa-solid fa-xmark"></i></span>
                            <?php elseif ($st['assign'] === 'in_progress'): ?>
                                <span class="status-badge badge-progress" title="IN PROGRESS"><i class="fa-solid fa-hourglass-half"></i></span>
                            <?php else: ?>
                                <span class="status-badge badge-assigned" title="ASSIGNED"><i class="fa-solid fa-circle"></i></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge-empty"><i class="fa-solid fa-minus"></i></span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="dummy-col"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>?>

<?php renderFooter(); ?>
