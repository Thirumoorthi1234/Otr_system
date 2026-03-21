<?php
// management/effectiveness.php
require_once '../includes/layout.php';
checkRole('management');

// ─── Fetch All Trainee Performance Data ───────────────────
$trainees = $pdo->query("
    SELECT u.id, u.full_name, u.employee_id, u.department, u.photo_path,
           ROUND(AVG(er.score), 1) as avg_score,
           COUNT(er.id) as exam_count,
           SUM(CASE WHEN er.status = 'pass' THEN 1 ELSE 0 END) as pass_count,
           MAX(er.score) as best_score,
           MIN(er.score) as worst_score
    FROM users u
    LEFT JOIN exam_results er ON er.trainee_id = u.id
    WHERE u.role = 'trainee'
    GROUP BY u.id
    ORDER BY avg_score DESC
")->fetchAll();

// ─── Fetch Trainer Effectiveness Data ─────────────────────
$trainers = $pdo->query("
    SELECT tr.id, tr.full_name, tr.employee_id, tr.department, tr.photo_path,
           COUNT(DISTINCT a.trainee_id) as trainee_count,
           COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed_assignments,
           COUNT(DISTINCT a.id) as total_assignments,
           ROUND(AVG(er.score), 1) as avg_trainee_score,
           SUM(CASE WHEN er.status = 'pass' THEN 1 ELSE 0 END) as total_passes,
           COUNT(er.id) as total_exams,
           ROUND(AVG(CASE WHEN a.status = 'completed' AND a.completion_date IS NOT NULL AND a.assigned_date IS NOT NULL 
                       THEN DATEDIFF(a.completion_date, a.assigned_date) END), 0) as avg_completion_days
    FROM users tr
    LEFT JOIN assignments a ON a.trainer_id = tr.id
    LEFT JOIN exam_results er ON er.trainee_id = a.trainee_id
    WHERE tr.role = 'trainer'
    GROUP BY tr.id
    ORDER BY avg_trainee_score DESC
")->fetchAll();

// ─── Aggregate Stats ──────────────────────────────────────
$totalTrainees = count($trainees);
$avgTeamScore = $totalTrainees > 0 ? round(array_sum(array_column($trainees, 'avg_score')) / max(array_filter(array_column($trainees, 'avg_score'), fn($v) => $v > 0) ?: [1]), 1) : 0;
$certifiedStaff = count(array_filter($trainees, fn($t) => $t['pass_count'] > 0));
$highRiskCount = count(array_filter($trainees, fn($t) => $t['avg_score'] !== null && $t['avg_score'] < 60));
$avgTrainerRating = count($trainers) > 0 ? round(array_sum(array_column($trainers, 'avg_trainee_score')) / max(count(array_filter(array_column($trainers, 'avg_trainee_score'), fn($v) => $v > 0)) ?: 1, 1), 1) : 0;

// ─── Skill Area Performance (by module) ──────────────────
$skillData = $pdo->query("
    SELECT m.title, 
           ROUND(AVG(er.score), 1) as actual_score,
           e.passing_score as required_score
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.id
    JOIN training_modules m ON e.module_id = m.id
    GROUP BY m.id
    ORDER BY m.title
    LIMIT 6
")->fetchAll();

// ─── Trainer Improvement Index (trainee pass rate per trainer) ──
$trainerImprovement = $pdo->query("
    SELECT tr.full_name as trainer_name,
           ROUND(
               (SUM(CASE WHEN er.status = 'pass' THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(er.id), 0)
           , 1) as improvement_pct
    FROM users tr
    JOIN assignments a ON a.trainer_id = tr.id
    JOIN exam_results er ON er.trainee_id = a.trainee_id
    WHERE tr.role = 'trainer'
    GROUP BY tr.id
    ORDER BY improvement_pct DESC
    LIMIT 8
")->fetchAll();

$report_tab = $_GET['tab'] ?? 'overview';

renderHeader('Effectiveness Reports');
renderSidebar('management');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .eff-container { font-family: 'Plus Jakarta Sans', sans-serif; }
    .eff-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
    
    .eff-tabs { display: flex; gap: 8px; background: #F1F5F9; padding: 6px; border-radius: 12px; }
    .eff-tabs .btn { font-size: 0.85rem; font-weight: 600; padding: 8px 16px; border-radius: 8px; text-decoration: none; border: none; }
    .eff-tab-active { background: #0F172A !important; color: #fff !important; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2); }
    .eff-tab-inactive { background: transparent !important; color: #64748B !important; }
    
    .eff-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; width: 100%; }
    .eff-stat-card {
        background: var(--card-bg); border-radius: 20px; padding: 20px;
        border: 1px solid var(--border-color); box-shadow: 0 4px 16px rgba(21,56,95,0.05);
        position: relative; overflow: hidden; transition: all 0.3s ease;
        min-width: 0;
    }
    .eff-stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .eff-stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(21,56,95,0.12); }
    .eff-stat-label { color: var(--text-muted); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
    .eff-stat-value { font-size: 1.8rem; font-weight: 900; color: var(--text-main); line-height: 1; font-family: 'Outfit', sans-serif; }
    
    .eff-chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 450px), 1fr)); gap: 20px; margin-bottom: 25px; }
    .eff-chart-card {
        background: var(--card-bg); border-radius: 20px; padding: 20px;
        border: 1px solid var(--border-color); box-shadow: 0 4px 16px rgba(21,56,95,0.05);
        min-width: 0; overflow: hidden;
    }
    .eff-chart-title { font-weight: 800; color: var(--text-main); font-size: 1rem; margin-bottom: 15px; font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 10px; }
    .eff-chart-wrap { position: relative; height: 260px; width: 100%; }
    
    .eff-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
    .eff-table th { font-weight: 800; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 1px; padding: 12px 16px; color: var(--text-muted); font-family: 'Outfit', sans-serif; border-bottom: 2px solid var(--border-color); }
    .eff-table td { padding: 14px 16px; background: var(--card-bg); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); font-size: 0.88rem; }
    .eff-table td:first-child { border-left: 1px solid var(--border-color); border-radius: 12px 0 0 12px; }
    .eff-table td:last-child { border-right: 1px solid var(--border-color); border-radius: 0 12px 12px 0; }
    .eff-table tr:hover td { background: var(--sidebar-hover) !important; }
    
    .eff-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; font-family: 'Outfit', sans-serif; }
    .eff-badge-green { background: rgba(16,185,129,0.12); color: #059669; }
    .eff-badge-blue { background: rgba(59,130,246,0.12); color: #2563EB; }
    .eff-badge-yellow { background: rgba(245,158,11,0.12); color: #D97706; }
    .eff-badge-red { background: rgba(239,68,68,0.12); color: #dc2626; }
    
    [data-theme="dark"] .eff-badge-green { background: rgba(16,185,129,0.2); color: #6ee7b7; }
    [data-theme="dark"] .eff-badge-blue { background: rgba(59,130,246,0.2); color: #93c5fd; }
    [data-theme="dark"] .eff-badge-yellow { background: rgba(245,158,11,0.2); color: #fcd34d; }
    [data-theme="dark"] .eff-badge-red { background: rgba(239,68,68,0.2); color: #fca5a5; }
    
    .print-header { display: none; }
    
    /* Report container for PDF */
    .eff-report-container { background: var(--card-bg); border-radius: 24px; padding: 40px; }
    .eff-report-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 20px; margin-bottom: 30px; }
    .eff-report-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--border-color); font-weight: 600; color: var(--text-muted); font-size: 0.85rem; }
    
    .btn-preview-close {
        display: none; position: fixed; top: 20px; right: 20px; z-index: 100000;
        background: #EF4444 !important; color: white !important; padding: 10px 20px !important; border-radius: 8px !important; font-weight: 700 !important; cursor: pointer; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important; border: none !important;
    }
    .preview-mode-active .btn-preview-close { display: block !important; }
    
    .eff-container { width: 100%; box-sizing: border-box; max-width: 100% !important; overflow-x: hidden; }
    .eff-container *, .eff-container *::before, .eff-container *::after { box-sizing: border-box; }
    
    @media (max-width: 1400px) {
        .eff-stat-grid { grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr)) !important; gap: 8px; }
        .eff-stat-card { padding: 12px; }
        .eff-stat-value { font-size: 1.4rem; }
        .eff-stat-label { font-size: 0.65rem; }
    }
    @media (max-width: 1100px) {
        .eff-stat-grid { grid-template-columns: repeat(2, 1fr) !important; }
    }
    @media (max-width: 768px) {
        .eff-header { flex-direction: column !important; align-items: stretch !important; gap: 10px; }
        .eff-tabs .btn { padding: 8px 10px; font-size: 0.8rem; }
        .main-content { padding: 10px !important; }
    }
</style>

    <button onclick="togglePrintPreview()" class="btn-preview-close"><i class="fas fa-times"></i> Close Preview</button>

    <div class="eff-header">
        <div class="eff-tabs">
            <a href="effectiveness.php?tab=overview" class="btn <?php echo $report_tab == 'overview' ? 'eff-tab-active' : 'eff-tab-inactive'; ?>"><i class="fas fa-chart-pie" style="margin-right:8px;"></i>Overview</a>
            <a href="effectiveness.php?tab=trainers" class="btn <?php echo $report_tab == 'trainers' ? 'eff-tab-active' : 'eff-tab-inactive'; ?>"><i class="fas fa-user-tie" style="margin-right:8px;"></i>Trainers</a>
            <a href="effectiveness.php?tab=trainees" class="btn <?php echo $report_tab == 'trainees' ? 'eff-tab-active' : 'eff-tab-inactive'; ?>"><i class="fas fa-user-graduate" style="margin-right:8px;"></i>Trainees</a>
        </div>
        
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
        
        <!-- Report Header (shown in PDF) -->
        <div class="eff-report-container" style="border: none; padding: 0; background: transparent;">
            <div class="eff-report-header" style="display: none;" id="pdfHeader">
                <div>
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" style="height: 35px; margin-bottom: 8px;" onerror="this.style.display='none';">
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;">Effectiveness Report</div>
                    <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 500;">Training Analytics & Performance Metrics</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: #0EA5E9; font-size: 1.1rem;"><?php echo date('F d, Y'); ?></div>
                    <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">System Generated Report</div>
                </div>
            </div>
        
        <?php if ($report_tab == 'overview' || $report_tab == 'trainers'): ?>
        <!-- ══════════════════════════════════════════ -->
        <!-- STAT CARDS -->
        <!-- ══════════════════════════════════════════ -->
        <div class="eff-stat-grid">
            <div class="eff-stat-card" style="border-top: 4px solid #3B82F6;">
                <div class="eff-stat-label">Avg Team Score</div>
                <div class="eff-stat-value" style="color: #3B82F6;"><?php echo $avgTeamScore ?: '0'; ?>%</div>
            </div>
            <div class="eff-stat-card" style="border-top: 4px solid #10B981;">
                <div class="eff-stat-label">Certified Staff</div>
                <div class="eff-stat-value" style="color: #10B981;"><?php echo $certifiedStaff; ?></div>
            </div>
            <div class="eff-stat-card" style="border-top: 4px solid #EF4444;">
                <div class="eff-stat-label">High Risk Learners</div>
                <div class="eff-stat-value" style="color: #EF4444;"><?php echo $highRiskCount; ?></div>
            </div>
            <div class="eff-stat-card" style="border-top: 4px solid #8B5CF6;">
                <div class="eff-stat-label">Trainer Rating</div>
                <div class="eff-stat-value" style="color: #8B5CF6;"><?php echo $avgTrainerRating ?: '0'; ?>%</div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($report_tab == 'overview'): ?>
        <!-- ══════════════════════════════════════════ -->
        <!-- CHARTS ROW -->
        <!-- ══════════════════════════════════════════ -->
        <div class="eff-chart-grid">
            <!-- Radar Chart: Skill Gap -->
            <div class="eff-chart-card">
                <div class="eff-chart-title">
                    <i class="fas fa-project-diagram" style="color: #3B82F6;"></i>
                    Technical Skill Gap (Actual vs. Required)
                </div>
                <div class="eff-chart-wrap"><canvas id="radarChart"></canvas></div>
            </div>
            
            <!-- Bar Chart: Trainer Improvement -->
            <div class="eff-chart-card">
                <div class="eff-chart-title">
                    <i class="fas fa-chart-bar" style="color: #8B5CF6;"></i>
                    Trainer Effectiveness Index
                </div>
                <div class="eff-chart-wrap"><canvas id="trainerBarChart"></canvas></div>
            </div>
        </div>
        
        <!-- Second Row of Charts -->
        <div class="eff-chart-grid">
            <!-- Doughnut: Pass/Fail Distribution -->
            <div class="eff-chart-card">
                <div class="eff-chart-title">
                    <i class="fas fa-chart-pie" style="color: #10B981;"></i>
                    Overall Pass / Fail Distribution
                </div>
                <div class="eff-chart-wrap" style="height: 260px; display: flex; justify-content: center;">
                    <canvas id="passFailChart"></canvas>
                </div>
            </div>
            
            <!-- Horizontal Bar: Score by Department -->
            <div class="eff-chart-card">
                <div class="eff-chart-title">
                    <i class="fas fa-building" style="color: #F59E0B;"></i>
                    Department Performance Comparison
                </div>
                <div class="eff-chart-wrap"><canvas id="deptChart"></canvas></div>
            </div>
        </div>
        
        <script>
        // ── Chart Data ──
        const skillLabels = <?php echo json_encode(array_column($skillData, 'title') ?: ['Soldering', 'ESD Safety', 'Assembly', '5S', 'Safety']); ?>;
        const actualScores = <?php echo json_encode(array_map('floatval', array_column($skillData, 'actual_score') ?: [75, 82, 68, 90, 85])); ?>;
        const requiredScores = <?php echo json_encode(array_map('floatval', array_column($skillData, 'required_score') ?: [80, 85, 75, 85, 90])); ?>;
        
        const trainerNames = <?php echo json_encode(array_column($trainerImprovement, 'trainer_name') ?: ['Trainer A', 'Trainer B', 'Trainer C']); ?>;
        const trainerPcts = <?php echo json_encode(array_map('floatval', array_column($trainerImprovement, 'improvement_pct') ?: [85, 72, 90])); ?>;
        
        Chart.defaults.font.family = "'Plus Jakarta Sans', 'Outfit', sans-serif";
        Chart.defaults.color = '#94A3B8';
        Chart.defaults.plugins.tooltip.backgroundColor = '#0F172A';
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        
        // Radar Chart
        new Chart(document.getElementById('radarChart'), {
            type: 'bar',
            data: {
                labels: skillLabels,
                datasets: [
                    {
                        label: 'Actual',
                        data: actualScores,
                        backgroundColor: '#EF4444',
                        borderRadius: 4,
                        barPercentage: 0.6
                    },
                    {
                        label: 'Required',
                        data: requiredScores,
                        backgroundColor: '#3B82F6',
                        borderRadius: 4,
                        barPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true, max: 100, 
                        grid: { borderDash: [4, 4], color: '#F1F5F9' } 
                    },
                    x: { 
                        grid: { display: false }, 
                        ticks: { font: { size: 11, weight: '600' } } 
                    }
                },
                plugins: {
                    legend: { position: window.innerWidth < 1200 ? 'bottom' : 'top', labels: { usePointStyle: true, boxWidth: 10, font: { weight: '600', size: 12 } } }
                }
            }
        });
        
        // Trainer Bar Chart
        new Chart(document.getElementById('trainerBarChart'), {
            type: 'bar',
            data: {
                labels: trainerNames,
                datasets: [{
                    label: 'Learner Improvement %',
                    data: trainerPcts,
                    backgroundColor: '#8B5CF6',
                    borderRadius: 8,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { borderDash: [4, 4], color: '#F1F5F9' }, ticks: { callback: v => v + '%' } },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                },
                plugins: { legend: { display: true, labels: { usePointStyle: true, boxWidth: 10, font: { weight: '600' } } } }
            }
        });
        
        // Pass/Fail Doughnut
        <?php
        $totalPass = array_sum(array_column($trainees, 'pass_count'));
        $totalExams = array_sum(array_column($trainees, 'exam_count'));
        $totalFail = $totalExams - $totalPass;
        ?>
        new Chart(document.getElementById('passFailChart'), {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed'],
                datasets: [{ 
                    data: [<?php echo $totalPass ?: 65; ?>, <?php echo $totalFail ?: 35; ?>], 
                    backgroundColor: ['#10B981', '#EF4444'], 
                    borderWidth: 0 
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: window.innerWidth < 1200 ? 'bottom' : 'right', labels: { usePointStyle: true, font: { weight: '600', size: 12 } } } } }
        });
        
        // Department Performance
        <?php
        $deptData = $pdo->query("
            SELECT u.department, ROUND(AVG(er.score), 1) as avg_score
            FROM exam_results er JOIN users u ON er.trainee_id = u.id
            WHERE u.department IS NOT NULL AND u.department != ''
            GROUP BY u.department ORDER BY avg_score DESC LIMIT 6
        ")->fetchAll();
        ?>
        new Chart(document.getElementById('deptChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($deptData, 'department') ?: ['Operations', 'QC', 'Safety']); ?>,
                datasets: [{
                    label: 'Avg Score %',
                    data: <?php echo json_encode(array_map('floatval', array_column($deptData, 'avg_score') ?: [88, 82, 75])); ?>,
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444', '#06B6D4'],
                    borderRadius: 8,
                    barPercentage: 0.6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, max: 100, grid: { borderDash: [4, 4], color: '#F1F5F9' } },
                    y: { grid: { display: false }, ticks: { font: { size: 12, weight: '600' } } }
                },
                plugins: { legend: { display: false } }
            }
        });
        </script>
        <?php endif; ?>
        
        <?php if ($report_tab == 'trainers' || $report_tab == 'overview'): ?>
        <!-- ══════════════════════════════════════════ -->
        <!-- TRAINER EFFECTIVENESS TABLE -->
        <!-- ══════════════════════════════════════════ -->
        <div class="card" style="margin-bottom: 25px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-user-tie" style="color: #8B5CF6; margin-right: 10px;"></i>Trainer Effectiveness Report</h3>
            <div class="table-container">
                <table class="eff-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Trainer</th>
                            <th>Department</th>
                            <th>Trainees</th>
                            <th>Completion Rate</th>
                            <th>Avg Trainee Score</th>
                            <th>Pass Rate</th>
                            <th>Avg Days to Complete</th>
                            <th>Effectiveness</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainers as $i => $tr): 
                            $completionRate = $tr['total_assignments'] > 0 ? round(($tr['completed_assignments'] / $tr['total_assignments']) * 100) : 0;
                            $passRate = $tr['total_exams'] > 0 ? round(($tr['total_passes'] / $tr['total_exams']) * 100) : 0;
                            $avgScore = $tr['avg_trainee_score'] ?? 0;
                            
                            // Effectiveness = weighted combo of pass rate, completion rate, and avg score
                            $effectiveness = round(($passRate * 0.4) + ($completionRate * 0.3) + ($avgScore * 0.3));
                            if ($effectiveness >= 80) { $effClass = 'eff-badge-green'; $effLabel = 'Excellent'; }
                            elseif ($effectiveness >= 60) { $effClass = 'eff-badge-blue'; $effLabel = 'Good'; }
                            elseif ($effectiveness >= 40) { $effClass = 'eff-badge-yellow'; $effLabel = 'Average'; }
                            else { $effClass = 'eff-badge-red'; $effLabel = 'Needs Support'; }
                        ?>
                        <tr>
                            <td style="font-weight: 800; color: var(--text-muted);"><?php echo $i + 1; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar" style="width: 38px; height: 38px; border-radius: 12px;">
                                        <?php if (!empty($tr['photo_path'])): ?>
                                            <img src="<?php echo BASE_URL . $tr['photo_path']; ?>">
                                        <?php else: ?>
                                            <i class="fas fa-user-tie" style="font-size: 0.9rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700;"><?php echo e($tr['full_name']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($tr['employee_id']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo e($tr['department'] ?? 'N/A'); ?></td>
                            <td style="font-weight: 700;"><?php echo $tr['trainee_count']; ?></td>
                            <td>
                                <div class="progress-bar-wrap" style="width: 80px; display: inline-block; vertical-align: middle;">
                                    <div class="progress-bar-fill" style="width: <?php echo $completionRate; ?>%;"></div>
                                </div>
                                <span style="font-size: 0.8rem; font-weight: 700; margin-left: 5px;"><?php echo $completionRate; ?>%</span>
                            </td>
                            <td style="font-weight: 800; font-family: 'Outfit', sans-serif;"><?php echo $avgScore; ?>%</td>
                            <td>
                                <span class="eff-badge <?php echo $passRate >= 70 ? 'eff-badge-green' : ($passRate >= 50 ? 'eff-badge-yellow' : 'eff-badge-red'); ?>">
                                    <?php echo $passRate; ?>%
                                </span>
                            </td>
                            <td style="font-weight: 700;">
                                <?php echo $tr['avg_completion_days'] ? $tr['avg_completion_days'] . ' days' : 'N/A'; ?>
                            </td>
                            <td>
                                <span class="eff-badge <?php echo $effClass; ?>">
                                    <i class="fas fa-<?php echo $effectiveness >= 60 ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                    <?php echo $effectiveness; ?>% – <?php echo $effLabel; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($trainers)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 40px;">No trainer data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($report_tab == 'trainees' || $report_tab == 'overview'): ?>
        <!-- ══════════════════════════════════════════ -->
        <!-- TRAINEE EFFECTIVENESS TABLE -->
        <!-- ══════════════════════════════════════════ -->
        <div class="card">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-user-graduate" style="color: #3B82F6; margin-right: 10px;"></i>Trainee Effectiveness Report</h3>
            <div class="table-container">
                <table class="eff-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Trainee</th>
                            <th>Department</th>
                            <th>Exams Taken</th>
                            <th>Best Score</th>
                            <th>Avg Score</th>
                            <th>Pass Rate</th>
                            <th>Effectiveness</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainees as $i => $t): 
                            $passRate = $t['exam_count'] > 0 ? round(($t['pass_count'] / $t['exam_count']) * 100) : 0;
                            $avgScore = $t['avg_score'] ?? 0;
                            
                            // Effectiveness = weighted combo
                            $effectiveness = $t['exam_count'] > 0 ? round(($passRate * 0.5) + ($avgScore * 0.5)) : 0;
                            if ($effectiveness >= 80) { $effClass = 'eff-badge-green'; $effLabel = 'Excellent'; }
                            elseif ($effectiveness >= 60) { $effClass = 'eff-badge-blue'; $effLabel = 'Good'; }
                            elseif ($effectiveness >= 40) { $effClass = 'eff-badge-yellow'; $effLabel = 'Average'; }
                            elseif ($t['exam_count'] == 0) { $effClass = 'eff-badge-yellow'; $effLabel = 'No Data'; $effectiveness = 0; }
                            else { $effClass = 'eff-badge-red'; $effLabel = 'At Risk'; }
                        ?>
                        <tr>
                            <td style="font-weight: 800; color: var(--text-muted);"><?php echo $i + 1; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar" style="width: 38px; height: 38px; border-radius: 12px;">
                                        <?php if (!empty($t['photo_path'])): ?>
                                            <img src="<?php echo BASE_URL . $t['photo_path']; ?>">
                                        <?php else: ?>
                                            <i class="fas fa-user" style="font-size: 0.9rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700;"><?php echo e($t['full_name']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($t['employee_id']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo e($t['department'] ?? 'N/A'); ?></td>
                            <td style="font-weight: 700;"><?php echo $t['exam_count']; ?></td>
                            <td style="font-weight: 800; color: #059669;"><?php echo $t['best_score'] ? $t['best_score'] . '%' : '-'; ?></td>
                            <td style="font-weight: 800; font-family: 'Outfit', sans-serif;"><?php echo $avgScore ? $avgScore . '%' : '-'; ?></td>
                            <td>
                                <?php if ($t['exam_count'] > 0): ?>
                                <div class="progress-bar-wrap" style="width: 70px; display: inline-block; vertical-align: middle;">
                                    <div class="progress-bar-fill" style="width: <?php echo $passRate; ?>%; background: <?php echo $passRate >= 70 ? 'linear-gradient(90deg,#10b981,#059669)' : 'linear-gradient(90deg,#ef4444,#b91c1c)'; ?>;"></div>
                                </div>
                                <span style="font-size: 0.8rem; font-weight: 700; margin-left: 5px;"><?php echo $passRate; ?>%</span>
                                <?php else: ?>
                                <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="eff-badge <?php echo $effClass; ?>">
                                    <i class="fas fa-<?php echo $effectiveness >= 60 ? 'check-circle' : ($t['exam_count'] == 0 ? 'minus-circle' : 'exclamation-triangle'); ?>"></i>
                                    <?php echo $effectiveness; ?>% – <?php echo $effLabel; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($trainees)): ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">No trainee data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- PDF Footer -->
        <div class="eff-report-footer" style="display: none;" id="pdfFooter">
            <div>Confidential - Internal Use Only</div>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                Powered By 
                <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg" alt="Learnlike" style="height: 22px;" onerror="this.outerHTML='<span>Learnlike</span>';">
            </div>
        </div>
        
        </div> <!-- end eff-report-container -->
    </div> <!-- end eff-report-content -->
</div>

<script>
function togglePrintPreview() {
    document.body.classList.toggle('preview-mode-active');
    if (document.body.classList.contains('preview-mode-active')) {
        Swal.fire({
            title: 'Print Preview Active',
            text: 'Review the layout. Use Ctrl+P to Print or the button below.',
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
    if (pdfHeader) pdfHeader.style.display = 'flex';
    if (pdfFooter) pdfFooter.style.display = 'flex';
    if (mainContent) {
        mainContent.style.marginLeft = '0';
        mainContent.style.padding = '0';
    }
    
    window.scrollTo(0,0);

    setTimeout(() => {
        const rect = element.getBoundingClientRect();
        const opt = {
            margin: [0.3, 0.3, 0.3, 0.3],
            filename: 'Management_Effectiveness_Report_<?php echo date("Y-m-d"); ?>.pdf',
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
