<?php
// trainer/mapping_status.php — Supervisor Mapping Status Dashboard
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];
$filter_status = $_GET['filter'] ?? 'all';

// Fetch all trainees mapped to this trainer with full status
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.full_name, u.employee_id, u.photo_path, u.department, u.batch_number, u.doj,
        a.id as assignment_id, a.status as assignment_status, a.assigned_date, a.completion_date,
        m.title as module_name,
        a.is_locked,
        -- Induction progress
        (SELECT COUNT(*) FROM trainee_checklist_progress tcp WHERE tcp.trainee_id = u.id) as induction_done,
        (SELECT COUNT(*) FROM induction_checklist) as induction_total,
        -- Exam result
        (SELECT er.status FROM exam_results er JOIN exams e ON er.exam_id = e.id WHERE er.trainee_id = u.id AND e.module_id = a.module_id ORDER BY er.exam_date DESC LIMIT 1) as exam_status,
        (SELECT er.score FROM exam_results er JOIN exams e ON er.exam_id = e.id WHERE er.trainee_id = u.id AND e.module_id = a.module_id ORDER BY er.exam_date DESC LIMIT 1) as exam_score,
        -- OJT stages count
        (SELECT COUNT(*) FROM training_stages ts WHERE ts.assignment_id = a.id AND ts.type = 'otj') as otj_count,
        -- OJT evidence count
        (SELECT COUNT(*) FROM ojt_evidence oe WHERE oe.assignment_id = a.id) as evidence_count
    FROM assignments a
    JOIN users u ON a.trainee_id = u.id
    JOIN training_modules m ON a.module_id = m.id
    WHERE a.trainer_id = ? AND u.status = 'active'
    ORDER BY a.status ASC, u.full_name ASC
");
$stmt->execute([$trainer_id]);
$trainees = $stmt->fetchAll();

// Filter
if ($filter_status !== 'all') {
    $trainees = array_filter($trainees, fn($t) => $t['assignment_status'] === $filter_status);
}

$counts = [
    'all' => count($stmt->fetchAll()) ?: count($trainees),
    'not_started' => 0, 'in_progress' => 0, 'completed' => 0
];
// Recount from all
$stmt->execute([$trainer_id]);
$all = $stmt->fetchAll();
foreach ($all as $r) { $counts[$r['assignment_status']] = ($counts[$r['assignment_status']] ?? 0) + 1; }
$counts['all'] = count($all);

renderHeader('Mapping Status');
renderSidebar('trainer');
?>

<style>
    .mapping-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
    .trainee-card { background: var(--card-bg); border-radius: 18px; border: 1.5px solid var(--border-color); overflow: hidden; transition: box-shadow 0.2s, transform 0.2s; }
    .trainee-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.1); transform: translateY(-2px); }
    .card-header-strip { height: 5px; }
    .card-header-strip.completed   { background: linear-gradient(90deg,#10b981,#34d399); }
    .card-header-strip.in_progress { background: linear-gradient(90deg,#f59e0b,#fcd34d); }
    .card-header-strip.not_started { background: linear-gradient(90deg,#94a3b8,#cbd5e1); }
    .card-header-strip.locked      { background: linear-gradient(90deg,#ef4444,#f87171); }

    .card-body { padding: 18px 20px; }
    .trainee-info { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .trainee-avatar { width: 46px; height: 46px; border-radius: 50%; overflow: hidden; background: var(--border-color); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--text-muted); }
    .trainee-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 12px 0; }
    .status-cell { background: var(--bg-color, #f8fafc); border-radius: 10px; padding: 8px 10px; }
    .status-cell .label { font-size: 0.68rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; margin-bottom: 4px; }
    .status-cell .val { font-size: 0.95rem; font-weight: 800; color: var(--text-main); }

    .progress-bar-mini { height: 6px; background: var(--border-color); border-radius: 3px; overflow: hidden; margin: 3px 0; }
    .progress-bar-mini div { height: 100%; border-radius: 3px; }

    .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 25px; }
    .filter-tab { padding: 8px 18px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; text-decoration: none; border: 2px solid transparent; transition: all 0.2s; }
    .filter-tab.active { border-color: var(--primary-blue); background: rgba(11,112,183,0.08); color: var(--primary-blue); }
    .filter-tab:not(.active) { background: var(--card-bg); color: var(--text-muted); border-color: var(--border-color); }
    .filter-tab:hover:not(.active) { border-color: var(--primary-blue); }
</style>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="mapping_status.php?filter=all" class="filter-tab <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
        <i class="fas fa-list"></i> All <span style="margin-left:5px; background:rgba(0,0,0,0.08); padding:2px 7px; border-radius:6px;"><?php echo $counts['all']; ?></span>
    </a>
    <a href="mapping_status.php?filter=in_progress" class="filter-tab <?php echo $filter_status === 'in_progress' ? 'active' : ''; ?>">
        <i class="fas fa-spinner" style="color:#f59e0b;"></i> In Progress <span style="margin-left:5px; background:rgba(0,0,0,0.08); padding:2px 7px; border-radius:6px;"><?php echo $counts['in_progress'] ?? 0; ?></span>
    </a>
    <a href="mapping_status.php?filter=not_started" class="filter-tab <?php echo $filter_status === 'not_started' ? 'active' : ''; ?>">
        <i class="fas fa-clock" style="color:#94a3b8;"></i> Not Started <span style="margin-left:5px; background:rgba(0,0,0,0.08); padding:2px 7px; border-radius:6px;"><?php echo $counts['not_started'] ?? 0; ?></span>
    </a>
    <a href="mapping_status.php?filter=completed" class="filter-tab <?php echo $filter_status === 'completed' ? 'active' : ''; ?>">
        <i class="fas fa-check-circle" style="color:#10b981;"></i> Completed <span style="margin-left:5px; background:rgba(0,0,0,0.08); padding:2px 7px; border-radius:6px;"><?php echo $counts['completed'] ?? 0; ?></span>
    </a>
</div>

<?php if (empty($trainees)): ?>
<div class="card" style="text-align:center; padding:60px;">
    <i class="fas fa-users-slash" style="font-size:3rem; color:#ddd; margin-bottom:20px;"></i>
    <p style="color:var(--text-muted);">No trainees found for selected filter.</p>
</div>
<?php else: ?>

<div class="mapping-grid">
<?php foreach ($trainees as $t):
    $stripClass = $t['is_locked'] ? 'locked' : $t['assignment_status'];
    $inductionPct = $t['induction_total'] > 0 ? round(($t['induction_done'] / $t['induction_total']) * 100) : 0;
    $examColor = $t['exam_status'] === 'pass' ? '#10b981' : ($t['exam_status'] === 'fail' ? '#ef4444' : '#94a3b8');
    $examLabel = $t['exam_status'] ?? 'Not Taken';
    $statusColors = ['completed' => '#10b981', 'in_progress' => '#f59e0b', 'not_started' => '#94a3b8'];
    $statusColor  = $statusColors[$t['assignment_status']] ?? '#94a3b8';
?>
<div class="trainee-card">
    <div class="card-header-strip <?php echo $stripClass; ?>"></div>
    <div class="card-body">
        <!-- Trainee Info -->
        <div class="trainee-info">
            <div class="trainee-avatar">
                <?php if ($t['photo_path']): ?>
                <img src="<?php echo BASE_URL . e($t['photo_path']); ?>">
                <?php else: ?><i class="fas fa-user"></i><?php endif; ?>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:800; font-size:0.95rem; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?php echo e($t['full_name']); ?>
                    <?php if ($t['is_locked']): ?><span class="badge badge-danger" style="font-size:0.6rem; margin-left:5px;">LOCKED</span><?php endif; ?>
                </div>
                <div style="font-size:0.78rem; color:var(--text-muted);"><?php echo e($t['employee_id']); ?> · <?php echo e($t['department'] ?? 'N/A'); ?></div>
                <div style="font-size:0.75rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($t['module_name']); ?></div>
            </div>
            <span style="background:<?php echo $statusColor; ?>20; color:<?php echo $statusColor; ?>; padding:4px 10px; border-radius:8px; font-size:0.7rem; font-weight:800; white-space:nowrap; border: 1px solid <?php echo $statusColor; ?>40;">
                <?php echo strtoupper(str_replace('_', ' ', $t['assignment_status'])); ?>
            </span>
        </div>

        <!-- Status Grid -->
        <div class="status-grid">
            <div class="status-cell">
                <div class="label"><i class="fas fa-list-check"></i> Induction</div>
                <div class="val"><?php echo $t['induction_done']; ?>/<?php echo $t['induction_total']; ?></div>
                <div class="progress-bar-mini"><div style="width:<?php echo $inductionPct; ?>%; background: linear-gradient(90deg,#3b82f6,#6366f1);"></div></div>
                <div style="font-size:0.7rem; color:var(--text-muted);"><?php echo $inductionPct; ?>% done</div>
            </div>
            <div class="status-cell">
                <div class="label"><i class="fas fa-file-alt"></i> Exam Result</div>
                <div class="val" style="color:<?php echo $examColor; ?>;"><?php echo strtoupper($examLabel); ?></div>
                <?php if ($t['exam_score'] !== null): ?>
                <div style="font-size:0.75rem; color:var(--text-muted);">Score: <?php echo $t['exam_score']; ?>%</div>
                <?php endif; ?>
            </div>
            <div class="status-cell">
                <div class="label"><i class="fas fa-industry"></i> OJT Stages</div>
                <div class="val"><?php echo $t['otj_count'] ?? 0; ?> certified</div>
            </div>
            <div class="status-cell">
                <div class="label"><i class="fas fa-camera"></i> Evidence</div>
                <div class="val" style="color:<?php echo $t['evidence_count'] > 0 ? '#a78bfa' : '#94a3b8'; ?>;">
                    <?php echo $t['evidence_count'] ?? 0; ?> photo<?php echo $t['evidence_count'] != 1 ? 's' : ''; ?>
                </div>
            </div>
        </div>

        <!-- Start Date -->
        <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom: 14px;">
            <i class="fas fa-calendar-alt"></i> Started: <?php echo $t['assigned_date'] ? date('d M Y', strtotime($t['assigned_date'])) : 'N/A'; ?>
            <?php if ($t['completion_date']): ?>
            &nbsp;·&nbsp;<i class="fas fa-flag-checkered" style="color:#10b981;"></i> Done: <?php echo date('d M Y', strtotime($t['completion_date'])); ?>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="../admin/induction_records.php?trainee_id=<?php echo $t['id']; ?>" class="btn" style="background:rgba(11,112,183,0.1); color:var(--primary-blue); font-size:0.75rem; padding:6px 12px; flex:1; text-align:center;">
                <i class="fas fa-id-badge"></i> Induction
            </a>
            <a href="progress.php?assignment_id=<?php echo $t['assignment_id']; ?>" class="btn btn-primary" style="font-size:0.75rem; padding:6px 12px; flex:1; text-align:center;">
                <i class="fas fa-edit"></i> Manage
            </a>
            <a href="../admin/training_record.php?type=full&id=<?php echo $t['assignment_id']; ?>" class="btn" style="background:#edf2f7; color:var(--text-muted); font-size:0.75rem; padding:6px 12px; flex:1; text-align:center;" target="_blank">
                <i class="fas fa-file-export"></i> Report
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
