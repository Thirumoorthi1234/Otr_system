<?php
// trainee/refresher.php — Trainee view of their refresher training tasks
require_once '../includes/layout.php';
checkRole('trainee');

$trainee_id = $_SESSION['user_id'];

// Auto-update overdue
$pdo->exec("UPDATE refresher_training SET status='overdue' WHERE due_date < CURDATE() AND status='pending' AND trainee_id=" . (int)$trainee_id);

$records = $pdo->prepare("
    SELECT rt.*, m.title as module_title, ab.full_name as assigned_by_name,
           DATEDIFF(rt.due_date, CURDATE()) as days_remaining
    FROM refresher_training rt
    JOIN training_modules m ON rt.module_id = m.id
    LEFT JOIN users ab ON rt.assigned_by = ab.id
    WHERE rt.trainee_id = ?
    ORDER BY rt.status = 'completed', rt.due_date ASC
");
$records->execute([$trainee_id]);
$records = $records->fetchAll();

renderHeader('My Refresher Training');
renderSidebar('trainee');
?>

<style>
    .refresher-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 18px; }
    .refresher-card { border-radius: 16px; border: 1.5px solid var(--border-color); background: var(--card-bg); overflow: hidden; transition: box-shadow 0.2s; }
    .refresher-card:hover { box-shadow: 0 6px 25px rgba(0,0,0,0.08); }
    .refresher-top { padding: 18px 20px 14px; }
    .refresher-footer { border-top: 1px solid var(--border-color); padding: 12px 20px; background: rgba(0,0,0,0.02); }
    .countdown-badge { font-size: 1.6rem; font-weight: 900; font-family: 'Outfit', sans-serif; display: block; margin-bottom: 4px; }
</style>

<?php if (empty($records)): ?>
<div class="card" style="text-align:center; padding:60px;">
    <i class="fas fa-party-horn" style="font-size:3rem; margin-bottom:20px; color:#10b981;"></i>
    <h3 style="color:#10b981;">All Clear!</h3>
    <p style="color:var(--text-muted);">You have no refresher training assigned. Keep up the great work!</p>
</div>
<?php else: ?>

<!-- Stats -->
<?php
$overdue   = count(array_filter($records, fn($r) => $r['status'] === 'overdue'));
$pending   = count(array_filter($records, fn($r) => $r['status'] === 'pending'));
$completed = count(array_filter($records, fn($r) => $r['status'] === 'completed'));
?>
<div class="stats-grid" style="margin-bottom:25px;">
    <div class="stat-card">
        <div class="stat-label">Total Assigned</div>
        <div class="stat-value"><?php echo count($records); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label" style="color:#ef4444;">Overdue</div>
        <div class="stat-value" style="color:#ef4444;"><?php echo $overdue; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label" style="color:#f59e0b;">Pending</div>
        <div class="stat-value" style="color:#f59e0b;"><?php echo $pending; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label" style="color:#10b981;">Completed</div>
        <div class="stat-value" style="color:#10b981;"><?php echo $completed; ?></div>
    </div>
</div>

<div class="refresher-grid">
<?php foreach ($records as $r):
    $days = (int)$r['days_remaining'];
    $isCompleted = $r['status'] === 'completed';
    $isOverdue   = $r['status'] === 'overdue';

    if ($isCompleted)        { $borderColor = '#10b981'; $bgGrad = 'rgba(16,185,129,0.06)'; }
    elseif ($isOverdue)      { $borderColor = '#ef4444'; $bgGrad = 'rgba(239,68,68,0.06)'; }
    elseif ($days <= 3)      { $borderColor = '#f59e0b'; $bgGrad = 'rgba(245,158,11,0.06)'; }
    else                     { $borderColor = '#6366f1'; $bgGrad = 'rgba(99,102,241,0.06)'; }

    if ($isCompleted)        $countLabel = 'Completed';
    elseif ($isOverdue)      $countLabel = abs($days) . 'd Overdue!';
    elseif ($days == 0)      $countLabel = 'Due Today!';
    elseif ($days <= 3)      $countLabel = $days . ' days left';
    else                     $countLabel = $days . ' days left';
?>
<div class="refresher-card" style="border-color:<?php echo $borderColor; ?>; background:<?php echo $bgGrad; ?>;">
    <div class="refresher-top">
        <!-- Countdown -->
        <div style="text-align:right; margin-bottom:12px;">
            <span class="countdown-badge" style="color:<?php echo $borderColor; ?>;"><?php echo $countLabel; ?></span>
            <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">Due: <?php echo date('d M Y', strtotime($r['due_date'])); ?></span>
        </div>

        <!-- Module -->
        <div style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); font-weight:700; letter-spacing:0.5px; margin-bottom:6px;">Module</div>
        <div style="font-weight:800; font-size:1.05rem; color:var(--text-main); margin-bottom:14px;"><?php echo e($r['module_title']); ?></div>

        <!-- Status badge -->
        <span style="background:<?php echo $borderColor; ?>20; color:<?php echo $borderColor; ?>; border:1px solid <?php echo $borderColor; ?>40; padding:5px 12px; border-radius:8px; font-weight:800; font-size:0.78rem;">
            <?php echo strtoupper(str_replace('_', ' ', $r['status'])); ?>
        </span>
    </div>
    <div class="refresher-footer">
        <!-- Notes -->
        <?php if ($r['notes']): ?>
        <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:8px;">
            <i class="fas fa-sticky-note" style="margin-right:4px;"></i> <?php echo e($r['notes']); ?>
        </div>
        <?php endif; ?>
        <div style="font-size:0.75rem; color:var(--text-muted);">
            <i class="fas fa-user-tie"></i> Assigned by: <?php echo e($r['assigned_by_name'] ?? 'Trainer'); ?>
        </div>
        <?php if ($isCompleted && $r['completed_at']): ?>
        <div style="font-size:0.75rem; color:#10b981; margin-top:4px;">
            <i class="fas fa-check-circle"></i> Completed: <?php echo date('d M Y', strtotime($r['completed_at'])); ?>
        </div>
        <?php endif; ?>

        <!-- Action: Go to module -->
        <div style="margin-top:12px;">
            <a href="my-training.php" class="btn btn-primary" style="width:100%; text-align:center; font-size:0.82rem; padding:9px;">
                <i class="fas fa-play-circle"></i> Start Refresher Training
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
