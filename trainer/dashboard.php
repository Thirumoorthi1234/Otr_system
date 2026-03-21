<?php
// trainer/dashboard.php
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];

// Get assigned trainees count
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT trainee_id) as count FROM assignments WHERE trainer_id = ?");
$stmt->execute([$trainer_id]);
$traineeCount = $stmt->fetch()['count'];

// Get pending progress updates
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignments WHERE trainer_id = ? AND status = 'in_progress'");
$stmt->execute([$trainer_id]);
$pendingCount = $stmt->fetch()['count'];

// Get completed count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignments WHERE trainer_id = ? AND status = 'completed'");
$stmt->execute([$trainer_id]);
$completedCount = $stmt->fetch()['count'];

// Leaderboard: Top trainees by average exam score (assigned to this trainer)
$leaderboard = $pdo->prepare("
    SELECT u.id, u.full_name, u.employee_id, u.photo_path,
           ROUND(AVG(er.score), 1) as avg_score,
           COUNT(er.id) as exam_count,
           SUM(CASE WHEN er.status = 'pass' THEN 1 ELSE 0 END) as pass_count
    FROM users u
    JOIN assignments a ON a.trainee_id = u.id
    LEFT JOIN exam_results er ON er.trainee_id = u.id
    WHERE a.trainer_id = ? AND er.id IS NOT NULL
    GROUP BY u.id
    ORDER BY avg_score DESC
    LIMIT 10
");
$leaderboard->execute([$trainer_id]);
$rankedTrainees = $leaderboard->fetchAll();

// Training Efficiency for completed trainees
$efficiencyData = $pdo->prepare("
    SELECT u.id, u.full_name, u.employee_id,
           a.status, a.assigned_date, a.completion_date,
           m.title as module_name,
           ROUND(AVG(er.score), 1) as avg_score,
           (SELECT COALESCE(SUM(ts.man_hours), 0) FROM training_stages ts WHERE ts.assignment_id = a.id AND ts.type = 'otj') as otj_hours,
           (SELECT COALESCE(SUM(ts.man_hours), 0) FROM training_stages ts WHERE ts.assignment_id = a.id) as total_hours
    FROM assignments a
    JOIN users u ON a.trainee_id = u.id
    JOIN training_modules m ON a.module_id = m.id
    LEFT JOIN exams e ON e.module_id = a.module_id
    LEFT JOIN exam_results er ON er.trainee_id = u.id AND er.exam_id = e.id
    WHERE a.trainer_id = ? AND a.status = 'completed'
    GROUP BY a.id
    ORDER BY a.completion_date DESC
    LIMIT 10
");
$efficiencyData->execute([$trainer_id]);
$efficiencyList = $efficiencyData->fetchAll();

renderHeader('Trainer Dashboard');
renderSidebar('trainer');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">My Trainees</div>
        <div class="stat-value"><?php echo $traineeCount; ?></div>
        <div class="stat-trend" style="color: var(--brand-sky); font-size: 0.8rem;">
            Assigned to you
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Modules In Progress</div>
        <div class="stat-value"><?php echo $pendingCount; ?></div>
        <div class="stat-trend" style="color: var(--warning); font-size: 0.8rem;">
            Active training
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed</div>
        <div class="stat-value"><?php echo $completedCount; ?></div>
        <div class="stat-trend" style="color: var(--success); font-size: 0.8rem;">
            <i class="fas fa-check-circle"></i> Certified
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Feedback Score</div>
        <div class="stat-value">A</div>
        <div class="stat-trend" style="color: var(--success); font-size: 0.8rem;">
            Trainee rating
        </div>
    </div>
</div>

<!-- Leaderboard Section -->
<?php if (!empty($rankedTrainees)): ?>
<div class="leaderboard-section">
    <div class="card" style="border: none; background: transparent; padding: 0; box-shadow: none;">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-trophy" style="color: #F59E0B; margin-right: 10px;"></i>Trainee Leaderboard</h3>
        
        <!-- Top 3 Showcase -->
        <div class="leaderboard-grid">
            <?php foreach (array_slice($rankedTrainees, 0, 3) as $i => $trainee): 
                $rank = $i + 1;
                $rankClass = 'rank-' . $rank;
                $medals = ['🥇', '🥈', '🥉'];
            ?>
            <div class="leaderboard-card <?php echo $rankClass; ?>" style="animation-delay: <?php echo $i * 0.1; ?>s;">
                <div class="rank-badge"><?php echo $medals[$i]; ?></div>
                <div class="leaderboard-info">
                    <div class="leaderboard-name"><?php echo e($trainee['full_name']); ?></div>
                    <div class="leaderboard-meta">
                        <?php echo e($trainee['employee_id']); ?> • <?php echo $trainee['pass_count']; ?>/<?php echo $trainee['exam_count']; ?> passed
                    </div>
                </div>
                <div class="leaderboard-score"><?php echo $trainee['avg_score']; ?>%</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Remaining Ranks (Table) -->
        <?php if (count($rankedTrainees) > 3): ?>
        <div class="card" style="margin-top: 5px;">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Trainee</th>
                            <th>Avg Score</th>
                            <th>Exams</th>
                            <th>Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($rankedTrainees, 3) as $i => $trainee): 
                            $rank = $i + 4;
                            $passRate = $trainee['exam_count'] > 0 ? round(($trainee['pass_count'] / $trainee['exam_count']) * 100) : 0;
                        ?>
                        <tr>
                            <td><span class="rank-badge rank-other" style="width: 32px; height: 32px; font-size: 0.85rem; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: white; background: var(--primary-gradient);"><?php echo $rank; ?></span></td>
                            <td><strong><?php echo e($trainee['full_name']); ?></strong><br><span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($trainee['employee_id']); ?></span></td>
                            <td style="font-weight: 800; font-family: 'Outfit', sans-serif;"><?php echo $trainee['avg_score']; ?>%</td>
                            <td><?php echo $trainee['exam_count']; ?></td>
                            <td>
                                <div class="progress-bar-wrap" style="width: 80px; display: inline-block; vertical-align: middle;">
                                    <div class="progress-bar-fill" style="width: <?php echo $passRate; ?>%;"></div>
                                </div>
                                <span style="font-size: 0.8rem; font-weight: 700; margin-left: 5px;"><?php echo $passRate; ?>%</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Training Efficiency Section (Completed Trainees) -->
<?php if (!empty($efficiencyList)): ?>
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3><i class="fas fa-chart-line" style="color: var(--brand-sky); margin-right: 10px;"></i>Training Efficiency (Completed)</h3>
        <span class="badge badge-success"><?php echo count($efficiencyList); ?> completed</span>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Trainee</th>
                    <th>Module</th>
                    <th>Training Efficiency</th>
                    <th>OTJ Efficiency</th>
                    <th>Avg Score</th>
                    <th>Completed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($efficiencyList as $eff): 
                    // Training Efficiency: based on score + completion speed
                    $training_eff = $eff['avg_score'] ?? 0;
                    if ($training_eff >= 90) { $tClass = 'excellent'; $tLabel = 'Excellent'; }
                    elseif ($training_eff >= 75) { $tClass = 'good'; $tLabel = 'Good'; }
                    elseif ($training_eff >= 60) { $tClass = 'average'; $tLabel = 'Average'; }
                    else { $tClass = 'low'; $tLabel = 'Needs Improvement'; }
                    
                    // OTJ Efficiency: ratio of OTJ hours to total hours
                    $otj_eff = $eff['total_hours'] > 0 ? round(($eff['otj_hours'] / $eff['total_hours']) * 100) : 0;
                    if ($otj_eff >= 50) { $oClass = 'excellent'; $oLabel = 'Strong'; }
                    elseif ($otj_eff >= 30) { $oClass = 'good'; $oLabel = 'Good'; }
                    elseif ($otj_eff > 0) { $oClass = 'average'; $oLabel = 'Moderate'; }
                    else { $oClass = 'low'; $oLabel = 'No OTJ'; }
                ?>
                <tr>
                    <td><strong><?php echo e($eff['full_name']); ?></strong><br><span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($eff['employee_id']); ?></span></td>
                    <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo e($eff['module_name']); ?></td>
                    <td>
                        <span class="efficiency-badge <?php echo $tClass; ?>">
                            <i class="fas fa-bolt"></i> <?php echo $training_eff; ?>% – <?php echo $tLabel; ?>
                        </span>
                    </td>
                    <td>
                        <span class="efficiency-badge <?php echo $oClass; ?>">
                            <i class="fas fa-industry"></i> <?php echo $otj_eff; ?>% – <?php echo $oLabel; ?>
                        </span>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;"><?php echo $eff['otj_hours']; ?>h OTJ / <?php echo $eff['total_hours']; ?>h total</div>
                    </td>
                    <td style="font-weight: 800; font-family: 'Outfit', sans-serif;"><?php echo $training_eff; ?>%</td>
                    <td style="color: var(--text-muted); font-size: 0.85rem; white-space: nowrap;">
                        <?php echo $eff['completion_date'] ? date('d M Y', strtotime($eff['completion_date'])) : '-'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Upcoming Assessments -->
<div class="card" style="margin-top: 25px;">
    <h3 style="margin-bottom: 20px;">Upcoming Assessments</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Trainee</th>
                    <th>Module</th>
                    <th>Next Milestone</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("
                    SELECT a.*, u.full_name as trainee_name, m.title as module_name 
                    FROM assignments a 
                    JOIN users u ON a.trainee_id = u.id 
                    JOIN training_modules m ON a.module_id = m.id 
                    WHERE a.trainer_id = ? AND a.status != 'completed'
                    LIMIT 5
                ");
                $stmt->execute([$trainer_id]);
                while ($row = $stmt->fetch()):
                ?>
                <tr>
                    <td><strong><?php echo e($row['trainee_name']); ?></strong></td>
                    <td><?php echo e($row['module_name']); ?></td>
                    <td>Stage 2: OTJ</td>
                    <td><span class="badge badge-warning"><?php echo ucfirst($row['status']); ?></span></td>
                    <td><a href="progress.php?assignment_id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 5px 12px; font-size: 0.8rem;">Update</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>
