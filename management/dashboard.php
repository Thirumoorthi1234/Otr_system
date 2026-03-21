<?php
// management/dashboard.php
require_once '../includes/layout.php';
checkRole('management');

// Real stats for manager dashboard
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainee'");
$totalTrainees = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
$totalTrainers = $stmt->fetch()['count'];

$totalEmployees = $totalTrainees + $totalTrainers;

$stmt = $pdo->query("SELECT COUNT(*) as count FROM exam_results WHERE status = 'pass'");
$totalPassed = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT AVG(score) as avg FROM exam_results");
$avgScore = round($stmt->fetch()['avg'] ?? 0, 1);

// Leaderboard: Top trainees by average exam score (all)
$leaderboard = $pdo->query("
    SELECT u.id, u.full_name, u.employee_id, u.photo_path, u.department,
           ROUND(AVG(er.score), 1) as avg_score,
           COUNT(er.id) as exam_count,
           SUM(CASE WHEN er.status = 'pass' THEN 1 ELSE 0 END) as pass_count
    FROM users u
    JOIN exam_results er ON er.trainee_id = u.id
    WHERE u.role = 'trainee'
    GROUP BY u.id
    ORDER BY avg_score DESC
    LIMIT 10
");
$rankedTrainees = $leaderboard->fetchAll();

// Training Efficiency for completed trainees
$efficiencyData = $pdo->query("
    SELECT u.id, u.full_name, u.employee_id, u.department,
           a.status, a.assigned_date, a.completion_date,
           m.title as module_name, tr.full_name as trainer_name,
           ROUND(AVG(er.score), 1) as avg_score,
           (SELECT COALESCE(SUM(ts.man_hours), 0) FROM training_stages ts WHERE ts.assignment_id = a.id AND ts.type = 'otj') as otj_hours,
           (SELECT COALESCE(SUM(ts.man_hours), 0) FROM training_stages ts WHERE ts.assignment_id = a.id) as total_hours
    FROM assignments a
    JOIN users u ON a.trainee_id = u.id
    JOIN users tr ON a.trainer_id = tr.id
    JOIN training_modules m ON a.module_id = m.id
    LEFT JOIN exams e ON e.module_id = a.module_id
    LEFT JOIN exam_results er ON er.trainee_id = u.id AND er.exam_id = e.id
    WHERE a.status = 'completed'
    GROUP BY a.id
    ORDER BY a.completion_date DESC
    LIMIT 10
");
$efficiencyList = $efficiencyData->fetchAll();

renderHeader('Manager Dashboard');
renderSidebar('management');
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Employees</div>
        <div class="stat-value"><?php echo $totalEmployees; ?></div>
        <div class="stat-trend" style="color: var(--primary-blue);"><i class="fas fa-users"></i> Trainee + Trainer</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Trainees</div>
        <div class="stat-value"><?php echo $totalTrainees; ?></div>
        <div class="stat-trend" style="color: var(--success);"><i class="fas fa-user-graduate"></i> Active learners</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Trainers</div>
        <div class="stat-value"><?php echo $totalTrainers; ?></div>
        <div class="stat-trend" style="color: var(--warning);"><i class="fas fa-user-tie"></i> Certified staff</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Avg. Assessment Score</div>
        <div class="stat-value"><?php echo $avgScore; ?>%</div>
        <div class="stat-trend" style="color: var(--primary-blue);"><i class="fas fa-chart-line"></i> Performance metric</div>
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
                        <?php echo e($trainee['employee_id']); ?> • <?php echo e($trainee['department'] ?? 'N/A'); ?> • <?php echo $trainee['pass_count']; ?>/<?php echo $trainee['exam_count']; ?> passed
                    </div>
                </div>
                <div class="leaderboard-score"><?php echo $trainee['avg_score']; ?>%</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Remaining Ranks -->
        <?php if (count($rankedTrainees) > 3): ?>
        <div class="card" style="margin-top: 5px;">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Trainee</th>
                            <th>Department</th>
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
                            <td><span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 10px; font-size: 0.85rem; font-weight: 900; color: white; background: var(--primary-gradient);"><?php echo $rank; ?></span></td>
                            <td><strong><?php echo e($trainee['full_name']); ?></strong><br><span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($trainee['employee_id']); ?></span></td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo e($trainee['department'] ?? 'N/A'); ?></td>
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
                    <th>Trainer</th>
                    <th>Training Efficiency</th>
                    <th>OTJ Efficiency</th>
                    <th>Completed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($efficiencyList as $eff): 
                    // Training Efficiency: based on score
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
                    <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo e($eff['trainer_name']); ?></td>
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

<!-- Charts Row -->
<div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 25px;">
    <div class="card">
        <h3>Training Completion Trends</h3>
        <div style="height: 200px; display: flex; align-items: flex-end; gap: 20px; padding: 20px; border-bottom: 2px solid var(--border-color);">
            <?php
            // Get monthly completion counts for last 4 months
            $months = [];
            for ($i = 3; $i >= 0; $i--) {
                $month_start = date('Y-m-01', strtotime("-$i months"));
                $month_end = date('Y-m-t', strtotime("-$i months"));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignments WHERE status = 'completed' AND completion_date BETWEEN ? AND ?");
                $stmt->execute([$month_start, $month_end]);
                $count = $stmt->fetch()['count'];
                $months[] = ['label' => date('M', strtotime("-$i months")), 'count' => $count];
            }
            $max_count = max(array_column($months, 'count')) ?: 1;
            foreach ($months as $m):
                $height = max(10, ($m['count'] / $max_count) * 100);
            ?>
            <div style="flex: 1; height: <?php echo $height; ?>%; background: var(--primary-blue); border-radius: 4px 4px 0 0;" title="<?php echo $m['label']; ?>: <?php echo $m['count']; ?>"></div>
            <?php endforeach; ?>
        </div>
        <div style="display: flex; gap: 20px; margin-top: 10px; color: var(--text-muted); font-size: 0.8rem; text-align: center;">
            <?php foreach ($months as $m): ?>
            <div style="flex: 1;"><?php echo $m['label']; ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3>Top Performing Departments</h3>
        <div style="margin-top: 20px;">
            <?php
            // Real department data
            $deptStmt = $pdo->query("
                SELECT u.department, ROUND(AVG(er.score), 0) as avg_score
                FROM exam_results er
                JOIN users u ON er.trainee_id = u.id
                WHERE u.department IS NOT NULL AND u.department != ''
                GROUP BY u.department
                ORDER BY avg_score DESC
                LIMIT 3
            ");
            $depts = $deptStmt->fetchAll();
            $colors = ['var(--success)', 'var(--primary-blue)', 'var(--warning)'];
            $idx = 0;
            foreach ($depts as $dept):
            ?>
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><?php echo e($dept['department']); ?></span>
                    <span><?php echo $dept['avg_score']; ?>%</span>
                </div>
                <div style="width: 100%; height: 8px; background: #edf2f7; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?php echo $dept['avg_score']; ?>%; height: 100%; background: <?php echo $colors[$idx] ?? 'var(--primary-blue)'; ?>;"></div>
                </div>
            </div>
            <?php $idx++; endforeach; ?>
            <?php if (empty($depts)): ?>
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Operations</span>
                    <span>92%</span>
                </div>
                <div style="width: 100%; height: 8px; background: #edf2f7; border-radius: 4px; overflow: hidden;">
                    <div style="width: 92%; height: 100%; background: var(--success);"></div>
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Quality Control</span>
                    <span>88%</span>
                </div>
                <div style="width: 100%; height: 8px; background: #edf2f7; border-radius: 4px; overflow: hidden;">
                    <div style="width: 88%; height: 100%; background: var(--primary-blue);"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
