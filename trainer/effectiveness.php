<?php
// trainer/effectiveness.php
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];

// ─── Fetch All Trainee Performance Data for this trainer ──
$trainees = $pdo->prepare("
    SELECT u.id, u.full_name, u.employee_id, u.department, u.photo_path,
           a.status as assignment_status, a.assigned_date, a.completion_date, a.id as assignment_id,
           m.title as module_name, m.id as module_id,
           ROUND(AVG(er.score), 1) as avg_score,
           COUNT(er.id) as exam_count,
           SUM(CASE WHEN er.status = 'pass' THEN 1 ELSE 0 END) as pass_count,
           MAX(er.score) as best_score,
           MIN(er.score) as worst_score,
           (SELECT COALESCE(SUM(ts.man_hours), 0) FROM training_stages ts WHERE ts.assignment_id = a.id AND ts.type = 'otj') as otj_hours,
           (SELECT COALESCE(SUM(ts.man_hours), 0) FROM training_stages ts WHERE ts.assignment_id = a.id) as total_hours
    FROM users u
    JOIN assignments a ON a.trainee_id = u.id
    JOIN training_modules m ON a.module_id = m.id
    LEFT JOIN exams e ON e.module_id = m.id
    LEFT JOIN exam_results er ON er.trainee_id = u.id AND er.exam_id = e.id
    WHERE a.trainer_id = ?
    GROUP BY a.id
    ORDER BY avg_score DESC
");
$trainees->execute([$trainer_id]);
$traineeList = $trainees->fetchAll();

// ─── Aggregate Stats ──────────────────────────────────────
$totalTrainees = count($traineeList);
$completedCount = count(array_filter($traineeList, fn($t) => $t['assignment_status'] == 'completed'));
$traineesWithScores = array_filter($traineeList, fn($t) => $t['avg_score'] > 0);
$avgTeamScore = count($traineesWithScores) > 0 ? round(array_sum(array_column($traineesWithScores, 'avg_score')) / count($traineesWithScores), 1) : 0;
$highRiskCount = count(array_filter($traineeList, fn($t) => $t['avg_score'] !== null && $t['avg_score'] < 60 && $t['exam_count'] > 0));
$totalPasses = array_sum(array_column($traineeList, 'pass_count'));
$totalExams = array_sum(array_column($traineeList, 'exam_count'));
$overallPassRate = $totalExams > 0 ? round(($totalPasses / $totalExams) * 100) : 0;

// ─── Skill Area Performance (by module for this trainer's trainees) ──
$skillData = $pdo->prepare("
    SELECT m.title, 
           ROUND(AVG(er.score), 1) as actual_score,
           e.passing_score as required_score
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.id
    JOIN training_modules m ON e.module_id = m.id
    JOIN assignments a ON a.trainee_id = er.trainee_id AND a.module_id = m.id
    WHERE a.trainer_id = ?
    GROUP BY m.id
    ORDER BY m.title
    LIMIT 6
");
$skillData->execute([$trainer_id]);
$skillResults = $skillData->fetchAll();

renderHeader('Trainee Effectiveness');
renderSidebar('trainer');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .eff-container { font-family: 'Plus Jakarta Sans', sans-serif; }
    .eff-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
    
    .eff-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    .eff-stat-card {
        background: var(--card-bg); border-radius: 20px; padding: 24px;
        border: 1px solid var(--border-color); box-shadow: 0 4px 16px rgba(21,56,95,0.05);
        position: relative; overflow: hidden; transition: all 0.3s ease;
    }
    .eff-stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .eff-stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(21,56,95,0.12); }
    .eff-stat-label { color: var(--text-muted); font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
    .eff-stat-value { font-size: 2rem; font-weight: 900; color: var(--text-main); line-height: 1; font-family: 'Outfit', sans-serif; }
    
    .eff-chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 450px), 1fr)); gap: 20px; margin-bottom: 25px; }
    .eff-chart-card {
        background: var(--card-bg); border-radius: 20px; padding: 20px;
        border: 1px solid var(--border-color); box-shadow: 0 4px 16px rgba(21,56,95,0.05);
        min-width: 0; overflow: hidden;
    }
    .eff-chart-title { font-weight: 800; color: var(--text-main); font-size: 1rem; margin-bottom: 15px; font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 10px; }
    .eff-chart-wrap { position: relative; height: 260px; width: 100%; }

    /* Print & Preview Mode */
    @media print {
        .sidebar, .header, .footer, .eff-header, .btn-preview-close { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
        .eff-container { padding: 0 !important; background: white !important; }
        .eff-stat-card, .eff-chart-card, .card { break-inside: avoid !important; page-break-inside: avoid !important; box-shadow: none !important; border: 1px solid #E2E8F0 !important; }
    }

    .preview-mode-active .sidebar, .preview-mode-active .header, .preview-mode-active .eff-header { display: none !important; }
    .preview-mode-active .main-content { margin-left: 0 !important; padding: 20px !important; width: 100% !important; background: #F8FAFC !important; }
    .preview-mode-active .eff-container { max-width: 1100px !important; margin: 0 auto !important; }
    
    .btn-preview-close {
        display: none; position: fixed; top: 20px; right: 20px; z-index: 100000;
        background: #EF4444; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); border: none;
    }
    .preview-mode-active .btn-preview-close { display: block; }
    
    @media (max-width: 1200px) {
        .eff-stat-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .eff-chart-grid { grid-template-columns: 1fr !important; gap: 15px; }
        .eff-stat-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .eff-stat-value { font-size: 1.6rem; }
        .eff-stat-card { padding: 15px; }
        .dashboard-container { padding: 0 !important; }
        .eff-container { padding: 10px; }
        .eff-table { display: block; overflow-x: auto; white-space: nowrap; }
    }
    @media (max-width: 500px) {
        .eff-stat-grid { grid-template-columns: 1fr; }
    }
</style>
</style>

<div class="eff-container">
    <button onclick="togglePrintPreview()" class="btn-preview-close"><i class="fas fa-times"></i> Close Preview</button>

    <div class="eff-header">
        <h2 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin: 0; font-size: 1.5rem;">
            <i class="fas fa-chart-line" style="color: #3B82F6; margin-right: 10px;"></i>Trainee Effectiveness
        </h2>
        
        <div style="display: flex; gap: 10px;">
            <button onclick="togglePrintPreview()" class="btn" style="background: white; color: #0F172A; padding: 10px 20px; font-size: 0.9rem; font-weight: 700; border-radius: 12px; border: 2px solid #E2E8F0; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-eye"></i> Preview & Print
            </button>
            <button onclick="exportEffReport()" id="exportBtn" class="btn" style="background: linear-gradient(135deg, #0F172A, #334155); color: white; padding: 10px 20px; font-size: 0.9rem; font-weight: 700; border-radius: 12px; box-shadow: 0 4px 15px rgba(15, 23, 42, 0.25); border: none; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </button>
        </div>
    </div>
    
    <!-- EXPORT CONTAINER -->
    <div id="eff-report-content">
        
        <!-- PDF Header (hidden, shown during PDF export) -->
        <div style="display:none;" id="pdfHeader">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 20px; margin-bottom: 30px;">
                <div>
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" style="height: 35px; margin-bottom: 8px;" onerror="this.style.display='none';">
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">Trainee Effectiveness Report</div>
                    <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 500;">Performance Analysis – <?php echo e($_SESSION['full_name'] ?? 'Trainer'); ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: #0EA5E9; font-size: 1.1rem;"><?php echo date('F d, Y'); ?></div>
                    <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">System Generated Report</div>
                </div>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="eff-stat-grid">
            <div class="eff-stat-card" style="border-top: 4px solid #3B82F6;">
                <div class="eff-stat-label">Avg Team Score</div>
                <div class="eff-stat-value" style="color: #3B82F6;"><?php echo $avgTeamScore; ?>%</div>
            </div>
            <div class="eff-stat-card" style="border-top: 4px solid #10B981;">
                <div class="eff-stat-label">Completed Training</div>
                <div class="eff-stat-value" style="color: #10B981;"><?php echo $completedCount; ?></div>
            </div>
            <div class="eff-stat-card" style="border-top: 4px solid #EF4444;">
                <div class="eff-stat-label">High Risk Learners</div>
                <div class="eff-stat-value" style="color: #EF4444;"><?php echo $highRiskCount; ?></div>
            </div>
            <div class="eff-stat-card" style="border-top: 4px solid #8B5CF6;">
                <div class="eff-stat-label">Overall Pass Rate</div>
                <div class="eff-stat-value" style="color: #8B5CF6;"><?php echo $overallPassRate; ?>%</div>
            </div>
        </div>
        
        <!-- CHARTS -->
        <div class="eff-chart-grid">
            <!-- Radar Chart: Skill Gap -->
            <div class="eff-chart-card">
                <div class="eff-chart-title">
                    <i class="fas fa-project-diagram" style="color: #3B82F6;"></i>
                    Technical Skill Gap (Actual vs. Required)
                </div>
                <div class="eff-chart-wrap"><canvas id="radarChart"></canvas></div>
            </div>
            
            <!-- Bar Chart: Trainee Scores -->
            <div class="eff-chart-card">
                <div class="eff-chart-title">
                    <i class="fas fa-chart-bar" style="color: #8B5CF6;"></i>
                    Trainee Performance Comparison
                </div>
                <div class="eff-chart-wrap"><canvas id="traineeBarChart"></canvas></div>
            </div>
        </div>
        
        <!-- Second Row -->
        <div class="eff-chart-grid">
            <div class="eff-chart-card">
                <div class="eff-chart-title">
                    <i class="fas fa-chart-pie" style="color: #10B981;"></i>
                    Pass / Fail Distribution
                </div>
                <div class="eff-chart-wrap" style="height: 260px; display: flex; justify-content: center;">
                    <canvas id="passFailChart"></canvas>
                </div>
            </div>
            
            <div class="eff-chart-card">
                <div class="eff-chart-title">
                    <i class="fas fa-industry" style="color: #F59E0B;"></i>
                    OTJ Hours Distribution
                </div>
                <div class="eff-chart-wrap"><canvas id="otjChart"></canvas></div>
            </div>
        </div>
        
        <script>
        Chart.defaults.font.family = "'Plus Jakarta Sans', 'Outfit', sans-serif";
        Chart.defaults.color = '#94A3B8';
        Chart.defaults.plugins.tooltip.backgroundColor = '#0F172A';
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        
        // Radar Chart
        const skillLabels = <?php echo json_encode(array_column($skillResults, 'title') ?: ['Soldering', 'ESD Safety', 'Assembly', '5S', 'Safety']); ?>;
        const actualScores = <?php echo json_encode(array_map('floatval', array_column($skillResults, 'actual_score') ?: [75, 82, 68, 90, 85])); ?>;
        const requiredScores = <?php echo json_encode(array_map('floatval', array_column($skillResults, 'required_score') ?: [80, 85, 75, 85, 90])); ?>;
        
        new Chart(document.getElementById('radarChart'), {
            type: 'bar',
            data: {
                labels: skillLabels,
                datasets: [
                    { label: 'Actual', data: actualScores, backgroundColor: '#EF4444', borderRadius: 4, barPercentage: 0.6 },
                    { label: 'Required', data: requiredScores, backgroundColor: '#3B82F6', borderRadius: 4, barPercentage: 0.6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, max: 100, grid: { borderDash: [4, 4], color: '#F1F5F9' } },
                    x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } }
                },
                plugins: { legend: { position: window.innerWidth < 1200 ? 'bottom' : 'top', labels: { usePointStyle: true, boxWidth: 10, font: { weight: '600', size: 12 } } } }
            }
        });
        
        // Trainee Comparison Bar
        <?php
        $top10 = array_slice(array_filter($traineeList, fn($t) => $t['avg_score'] > 0), 0, 10);
        $barNames = array_column($top10, 'full_name');
        $barScores = array_map('floatval', array_column($top10, 'avg_score'));
        ?>
        new Chart(document.getElementById('traineeBarChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($barNames ?: ['Trainee A', 'Trainee B']); ?>,
                datasets: [{
                    label: 'Avg Score %',
                    data: <?php echo json_encode($barScores ?: [80, 65]); ?>,
                    backgroundColor: <?php echo json_encode(array_map(fn($s) => $s >= 80 ? '#10B981' : ($s >= 60 ? '#3B82F6' : '#EF4444'), $barScores ?: [80, 65])); ?>,
                    borderRadius: 8, barPercentage: 0.6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 100, grid: { borderDash: [4, 4], color: '#F1F5F9' } }, x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } } },
                plugins: { legend: { display: false } }
            }
        });
        
        // Pass/Fail Doughnut
        new Chart(document.getElementById('passFailChart'), {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed'],
                datasets: [{ data: [<?php echo $totalPasses ?: 60; ?>, <?php echo ($totalExams - $totalPasses) ?: 40; ?>], backgroundColor: ['#10B981', '#EF4444'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: window.innerWidth < 1200 ? 'bottom' : 'right', labels: { usePointStyle: true, font: { weight: '600', size: 12 } } } } }
        });
        
        // OTJ Hours Bar
        <?php
        $otjTrainees = array_filter($traineeList, fn($t) => $t['total_hours'] > 0);
        $otjNames = array_column(array_slice($otjTrainees, 0, 8), 'full_name');
        $otjHrs = array_map('floatval', array_column(array_slice($otjTrainees, 0, 8), 'otj_hours'));
        $totalHrs = array_map('floatval', array_column(array_slice($otjTrainees, 0, 8), 'total_hours'));
        ?>
        new Chart(document.getElementById('otjChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($otjNames ?: ['No Data']); ?>,
                datasets: [
                    { label: 'OTJ Hours', data: <?php echo json_encode($otjHrs ?: [0]); ?>, backgroundColor: '#F59E0B', borderRadius: 8, barPercentage: 0.5 },
                    { label: 'Total Hours', data: <?php echo json_encode($totalHrs ?: [0]); ?>, backgroundColor: '#E2E8F0', borderRadius: 8, barPercentage: 0.5 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, grid: { borderDash: [4, 4], color: '#F1F5F9' } }, x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } } },
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 10, font: { weight: '600' } } } }
            }
        });
        </script>
        
        <!-- TRAINEE EFFECTIVENESS TABLE -->
        <div class="card" style="margin-top: 5px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-user-graduate" style="color: #3B82F6; margin-right: 10px;"></i>Detailed Trainee Effectiveness</h3>
            <div class="table-container">
                <table class="eff-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Trainee</th>
                            <th>Module</th>
                            <th>Status</th>
                            <th>Avg Score</th>
                            <th>Pass Rate</th>
                            <th>OTJ Efficiency</th>
                            <th>Training Efficiency</th>
                            <th>Overall Effectiveness</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($traineeList as $i => $t): 
                            $passRate = $t['exam_count'] > 0 ? round(($t['pass_count'] / $t['exam_count']) * 100) : 0;
                            $avgScore = $t['avg_score'] ?? 0;
                            $otjEff = $t['total_hours'] > 0 ? round(($t['otj_hours'] / $t['total_hours']) * 100) : 0;
                            
                            // Training Efficiency
                            $trainingEff = $avgScore;
                            if ($trainingEff >= 80) { $tClass = 'eff-badge-green'; }
                            elseif ($trainingEff >= 60) { $tClass = 'eff-badge-blue'; }
                            elseif ($trainingEff >= 40) { $tClass = 'eff-badge-yellow'; }
                            else { $tClass = $t['exam_count'] > 0 ? 'eff-badge-red' : 'eff-badge-yellow'; }
                            
                            // OTJ Efficiency display
                            if ($otjEff >= 50) { $oClass = 'eff-badge-green'; $oLabel = 'Strong'; }
                            elseif ($otjEff >= 30) { $oClass = 'eff-badge-blue'; $oLabel = 'Good'; }
                            elseif ($otjEff > 0) { $oClass = 'eff-badge-yellow'; $oLabel = 'Moderate'; }
                            else { $oClass = 'eff-badge-yellow'; $oLabel = 'No OTJ'; }
                            
                            // Overall Effectiveness
                            $effectiveness = $t['exam_count'] > 0 ? round(($passRate * 0.4) + ($avgScore * 0.4) + ($otjEff * 0.2)) : 0;
                            if ($effectiveness >= 75) { $effClass = 'eff-badge-green'; $effLabel = 'Excellent'; }
                            elseif ($effectiveness >= 55) { $effClass = 'eff-badge-blue'; $effLabel = 'Good'; }
                            elseif ($effectiveness >= 35) { $effClass = 'eff-badge-yellow'; $effLabel = 'Average'; }
                            elseif ($t['exam_count'] == 0) { $effClass = 'eff-badge-yellow'; $effLabel = 'No Data'; }
                            else { $effClass = 'eff-badge-red'; $effLabel = 'At Risk'; }
                        ?>
                        <tr>
                            <td style="font-weight: 800; color: var(--text-muted);"><?php echo $i + 1; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar" style="width: 36px; height: 36px; border-radius: 10px;">
                                        <?php if (!empty($t['photo_path'])): ?>
                                            <img src="<?php echo BASE_URL . $t['photo_path']; ?>">
                                        <?php else: ?>
                                            <i class="fas fa-user" style="font-size: 0.85rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700;"><?php echo e($t['full_name']); ?></div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted);"><?php echo e($t['employee_id']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo e($t['module_name']); ?></td>
                            <td>
                                <span class="eff-badge <?php echo $t['assignment_status'] == 'completed' ? 'eff-badge-green' : 'eff-badge-yellow'; ?>">
                                    <?php echo ucfirst($t['assignment_status']); ?>
                                </span>
                            </td>
                            <td style="font-weight: 800; font-family: 'Outfit', sans-serif;"><?php echo $avgScore ? $avgScore . '%' : '-'; ?></td>
                            <td>
                                <?php if ($t['exam_count'] > 0): ?>
                                <div class="progress-bar-wrap" style="width: 60px; display: inline-block; vertical-align: middle;">
                                    <div class="progress-bar-fill" style="width: <?php echo $passRate; ?>%; background: <?php echo $passRate >= 70 ? 'linear-gradient(90deg,#10b981,#059669)' : 'linear-gradient(90deg,#ef4444,#b91c1c)'; ?>;"></div>
                                </div>
                                <span style="font-size: 0.78rem; font-weight: 700; margin-left: 4px;"><?php echo $passRate; ?>%</span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td>
                                <span class="eff-badge <?php echo $oClass; ?>">
                                    <i class="fas fa-industry"></i> <?php echo $otjEff; ?>% – <?php echo $oLabel; ?>
                                </span>
                                <?php if ($t['total_hours'] > 0): ?>
                                <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 3px;"><?php echo $t['otj_hours']; ?>h / <?php echo $t['total_hours']; ?>h</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="eff-badge <?php echo $tClass; ?>">
                                    <i class="fas fa-bolt"></i> <?php echo $trainingEff ? $trainingEff . '%' : 'N/A'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="eff-badge <?php echo $effClass; ?>">
                                    <i class="fas fa-<?php echo $effectiveness >= 55 ? 'check-circle' : ($t['exam_count'] == 0 ? 'minus-circle' : 'exclamation-triangle'); ?>"></i>
                                    <?php echo $effectiveness; ?>% – <?php echo $effLabel; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($traineeList)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 40px;">No trainee data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- PDF Footer (hidden, shown during PDF export) -->
        <div style="display: none;" id="pdfFooter">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--border-color); font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">
                <div>Confidential - Internal Use Only</div>
                <div style="display: flex; align-items: center; gap: 10px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                    Powered By 
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg" alt="Learnlike" style="height: 22px;" onerror="this.outerHTML='<span>Learnlike</span>';">
                </div>
            </div>
        </div>
        
    </div> <!-- end eff-report-content -->
</div>

<script>
function togglePrintPreview() {
    document.body.classList.toggle('preview-mode-active');
    if (document.body.classList.contains('preview-mode-active')) {
        Swal.fire({
            title: 'Print Preview Active',
            text: 'Review the alignment. You can press Ctrl+P or the button below to Print.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-print"></i> Open Print Dialog',
            confirmButtonColor: '#0F172A'
        }).then((res) => { if (res.isConfirmed) window.print(); });
    }
}

function exportEffReport() {
    const element = document.getElementById('eff-report-content');
    const pdfHeader = document.getElementById('pdfHeader');
    const pdfFooter = document.getElementById('pdfFooter');
    const sidebar = document.querySelector('.sidebar');
    const header = document.querySelector('.header');
    const mainContent = document.querySelector('.main-content');
    
    const btn = document.getElementById('exportBtn');
    const origText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
    btn.disabled = true;

    const originalStyles = {
        sidebarDisplay: sidebar ? sidebar.style.display : '',
        headerDisplay: header ? header.style.display : '',
        mainMargin: mainContent ? mainContent.style.marginLeft : '',
        mainPadding: mainContent ? mainContent.style.padding : '',
        headerShow: pdfHeader ? pdfHeader.style.display : 'none',
        footerShow: pdfFooter ? pdfFooter.style.display : 'none'
    };

    if (sidebar) sidebar.style.display = 'none';
    if (header) header.style.display = 'none';
    if (pdfHeader) pdfHeader.style.display = 'block';
    if (pdfFooter) pdfFooter.style.display = 'block';
    if (mainContent) {
        mainContent.style.marginLeft = '0';
        mainContent.style.padding = '0';
    }
    
    window.scrollTo(0,0);

    setTimeout(() => {
        const rect = element.getBoundingClientRect();
        const opt = {
            margin: [0.3, 0.3, 0.3, 0.3],
            filename: 'Effectiveness_Report_<?php echo date("Y-m-d"); ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2, useCORS: true, 
                width: rect.width, height: rect.height + 20, 
                x: rect.left, y: rect.top,
                windowWidth: rect.width
            },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            if (sidebar) sidebar.style.display = originalStyles.sidebarDisplay;
            if (header) header.style.display = originalStyles.headerDisplay;
            if (pdfHeader) pdfHeader.style.display = originalStyles.headerShow;
            if (pdfFooter) pdfFooter.style.display = originalStyles.footerShow;
            if (mainContent) {
                mainContent.style.marginLeft = originalStyles.mainMargin;
                mainContent.style.padding = originalStyles.mainPadding;
            }
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Downloaded!';
            btn.style.background = 'linear-gradient(135deg, #059669, #10B981)';
            setTimeout(() => {
                btn.innerHTML = origText;
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.background = 'linear-gradient(135deg, #0F172A, #334155)';
            }, 2000);
        }).catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Retry';
        });
    }, 700);
}
</script>

<?php renderFooter(); ?>
