<?php
// admin/reports.php
require_once '../includes/layout.php';
checkRole('admin');

$report_type = $_GET['type'] ?? 'overview';

// Fetch data for charts (same as management)
$stats = [
    'completion' => $pdo->query("SELECT status, COUNT(*) as c FROM assignments GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR),
    'scores' => $pdo->query("SELECT AVG(score) as avg, COUNT(*) as total FROM exam_results")->fetch(),
    'modules' => $pdo->query("SELECT m.title, AVG(r.score) as avg_score FROM exam_results r JOIN exams e ON r.exam_id = e.id JOIN training_modules m ON e.module_id = m.id GROUP BY m.id ORDER BY avg_score DESC LIMIT 5")->fetchAll(PDO::FETCH_KEY_PAIR),
    'feedback' => $pdo->query("SELECT rating_overall, COUNT(*) as c FROM feedback GROUP BY rating_overall")->fetchAll(PDO::FETCH_KEY_PAIR)
];

// Handle CSV download (MUST BE BEFORE ANY HTML OUTPUT)
if ($report_type == 'mis' && isset($_GET['download_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=OTR_MIS_Export_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Emp ID','Full Name','Department','Batch','Role','DOJ','Qualification','Mobile','Module','Module Status','Exam Score','Exam Result','Induction Done','Induction Total','OJT Stages','Refresher Due','Refresher Status']);
    $misRows = $pdo->query("
        SELECT u.employee_id, u.full_name, u.department, u.batch_number, u.role, u.doj, u.qualification,
               u.mobile_number,
               m.title as module_name, a.status as assign_status,
               (SELECT score FROM exam_results er2 JOIN exams e2 ON er2.exam_id = e2.id WHERE er2.trainee_id = u.id AND e2.module_id = a.module_id ORDER BY er2.id DESC LIMIT 1) as exam_score,
               (SELECT status FROM exam_results er2 JOIN exams e2 ON er2.exam_id = e2.id WHERE er2.trainee_id = u.id AND e2.module_id = a.module_id ORDER BY er2.id DESC LIMIT 1) as exam_result,
               (SELECT COUNT(*) FROM trainee_checklist_progress tcp WHERE tcp.trainee_id = u.id) as ind_done,
               (SELECT COUNT(*) FROM induction_checklist) as ind_total,
               (SELECT COUNT(*) FROM training_stages ts WHERE ts.assignment_id = a.id AND ts.type='otj') as otj_count,
               rt.due_date as refresher_due, rt.status as refresher_status
        FROM users u
        LEFT JOIN assignments a ON a.trainee_id = u.id
        LEFT JOIN training_modules m ON a.module_id = m.id
        LEFT JOIN refresher_training rt ON rt.trainee_id = u.id AND rt.status != 'completed'
        WHERE u.role = 'trainee' AND u.status = 'active'
        ORDER BY u.full_name, m.title
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($misRows as $r) {
        fputcsv($out, [
            $r['employee_id'], $r['full_name'], $r['department'] ?? '', $r['batch_number'] ?? '',
            $r['role'], $r['doj'] ?? '', $r['qualification'] ?? '', $r['mobile_number'] ?? '',
            $r['module_name'] ?? 'N/A', $r['assign_status'] ?? 'Not Assigned',
            $r['exam_score'] !== null ? $r['exam_score'] . '%' : 'N/A',
            $r['exam_result'] ?? 'N/A',
            $r['ind_done'], $r['ind_total'],
            $r['otj_count'] ?? 0,
            $r['refresher_due'] ?? 'None', $r['refresher_status'] ?? 'N/A'
        ]);
    }
    fclose($out);
    exit();
}

renderHeader('System Analysis');
renderSidebar('admin');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* Premium Dashboard Styling */
    .dashboard-container {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: #ffffff;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        width: 100%;
        max-width: calc(100vw - 320px); 
        margin: 0;
        position: relative;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    
    .kpi-card {
        background: #FFFFFF;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        transition: all 0.2s;
    }
    .kpi-card:hover {
        border-color: #CBD5E1;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-bottom: 15px;
    }
    .icon-blue { background: #E0F2FE; color: #0369A1; }
    .icon-emerald { background: #DCFCE7; color: #15803D; }
    .icon-amber { background: #FEF3C7; color: #B45309; }
    .icon-rose { background: #FFE4E6; color: #BE123C; }
    
    .kpi-label {
        font-size: 0.8rem;
        color: #64748B;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    .kpi-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #0F172A;
    }
    
    .glass-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .card-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0F172A;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .chart-container { position: relative; height: 350px; width: 100%; }
    
    .chart-legend-custom {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        color: #64748B;
        font-weight: 600;
    }
    .legend-item .dot { width: 10px; height: 10px; border-radius: 50%; }
    .legend-item .val { margin-left: auto; color: #0F172A; font-weight: 800; }

    .db-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .btn-premium {
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .btn-premium.active { background: #0F172A; color: white; }
    .btn-premium:not(.active) { background: #F1F5F9; color: #64748B; }
    .btn-premium:hover:not(.active) { background: #E2E8F0; }
    
    #downloadPDFBtn { background: linear-gradient(135deg, #0F172A, #334155); color: white; border: none; }
    #downloadPDFBtn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2); }

    .employee-data-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-bottom: 25px; 
    }
    .employee-data-table th, .employee-data-table td { padding: 12px 15px; border-bottom: 1px solid #F1F5F9; text-align: left; }
    .employee-data-table th { background: #F8FAFC; color: #64748B; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
    .employee-data-table td { color: #1E293B; font-weight: 500; font-size: 0.9rem; }

    @media (max-width: 480px) {
        .stat-grid { grid-template-columns: 1fr !important; }
        .premium-tabs { justify-content: center; }
    }

    /* Grid Utilities for Professional Layout */
    .row { display: flex; flex-wrap: wrap; margin-right: -12px; margin-left: -12px; }
    .g-4 { padding: 12px; }
    .col-md-3 { flex: 0 0 25%; max-width: 25%; padding: 12px; box-sizing: border-box; }
    .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 12px; box-sizing: border-box; }
    .col-md-5 { flex: 0 0 41.666%; max-width: 41.666%; padding: 12px; box-sizing: border-box; }
    .col-md-7 { flex: 0 0 58.333%; max-width: 58.333%; padding: 12px; box-sizing: border-box; }
    .mt-4 { margin-top: 1.5rem !important; }
    .mt-1 { margin-top: 0.25rem !important; }
    .h-100 { height: 100% !important; }

    @media (max-width: 992px) {
        .col-md-3, .col-md-4, .col-md-5, .col-md-7 { flex: 0 0 50%; max-width: 50%; }
    }
    @media (max-width: 600px) {
        .col-md-3, .col-md-4, .col-md-5, .col-md-7 { flex: 0 0 100%; max-width: 100%; }
    }

    @media (max-width: 1366px) {
        .dashboard-container { max-width: calc(100vw - 310px) !important; }
    }
    @media (max-width: 1100px) {
        .dashboard-container { max-width: 100% !important; padding: 15px; }
        .db-header { flex-direction: column; align-items: flex-start; text-align: left !important; }
        .db-header div { text-align: left !important; }
        .report-nav-group { flex-direction: column; align-items: stretch; }
        .premium-tabs { justify-content: flex-start; }
    }

    @media (max-width: 768px) {
        .stat-grid { grid-template-columns: 1fr 1fr !important; }
        .dashboard-container { padding: 15px !important; margin: 0 !important; border-radius: 12px; max-width: 100vw !important; box-sizing: border-box; }
        .db-title { font-size: 1.2rem; }
        .db-header { margin-bottom: 15px; padding-bottom: 10px; }
        .chart-grid { grid-template-columns: 1fr !important; gap: 15px; }
        .employee-data-table { display: block; overflow-x: auto; white-space: nowrap; }
        .rpt-stat-card, .chart-card { padding: 12px !important; min-width: 0 !important; overflow: hidden !important; }
        .chart-wrapper { height: 260px !important; }
        .header-actions { justify-content: space-between; width: 100%; }
    }

    /* PDF Export Helper Styles */
    .pdf-export-mode {
        width: 1050px !important;
        max-width: 1050px !important;
        margin: 0 !important;
        padding: 20px !important;
        background: white !important;
        box-shadow: none !important;
        border: none !important;
    }
    .pdf-export-mode .chart-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 20px !important;
    }
    .pdf-export-mode .chart-wrapper { 
        height: 320px !important; /* Extra height for legends */
        overflow: visible !important;
    }
    .pdf-export-mode .rpt-stat-card, 
    .pdf-export-mode .chart-card,
    .pdf-export-mode .stat-card {
        padding: 20px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin-bottom: 25px !important;
        overflow: visible !important;
    }
    .pdf-export-mode-portrait {
        width: 750px !important;
        max-width: 750px !important;
    }
    .pdf-export-mode-portrait .chart-grid {
        grid-template-columns: 1fr !important;
    }
    /* Professional Print Scaling */
    @media print {
        @page { size: landscape; margin: 0.5cm; }
        .sidebar, .header, .footer, .report-nav-group, .btn-preview-close, .btn, .db-header .btn { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; border: none !important; }
        .dashboard-container { box-shadow: none !important; border: none !important; max-width: 100% !important; padding: 0 !important; width: 100% !important; }
        .rpt-stat-card, .chart-card, .stat-card { break-inside: avoid !important; page-break-inside: avoid !important; box-shadow: none !important; border: 1px solid #E2E8F0 !important; }
        body { background: white !important; font-size: 10pt !important; }
        .db-title { font-size: 16pt !important; }
        .db-subtitle { font-size: 10pt !important; }
        .employee-data-table { display: table !important; width: 100% !important; font-size: 8pt !important; table-layout: auto !important; }
        .employee-data-table th, .employee-data-table td { white-space: normal !important; word-break: break-word !important; padding: 6px !important; }
        .stat-grid { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; }
    }

    .preview-mode-active .sidebar, 
    .preview-mode-active .header,
    .preview-mode-active .report-nav-group { 
        display: none !important; 
    }
    .preview-mode-active .main-content { 
        margin-left: 0 !important; 
        padding: 20px !important; 
        width: 100% !important;
        background: #F8FAFC !important;
    }
    .preview-mode-active .dashboard-container {
        max-width: min(1100px, 96vw) !important;
        margin: 0 auto !important;
        width: 100% !important;
        box-sizing: border-box !important;
        overflow-x: hidden !important; 
    }
    .preview-mode-active .employee-data-table {
        display: table; /* Allow it to be a real table in preview if it fits */
        width: 100% !important;
    }
    @media (max-width: 1200px) {
        .preview-mode-active .employee-data-table {
            display: block !important;
            overflow-x: auto !important;
        }
    }
    .btn-preview-close {
        display: none;
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 100000;
        background: #EF4444;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        border: none;
    }
    .preview-mode-active .btn-preview-close {
        display: block;
    }
</style>

<div class="card" style="border: none; background: transparent; padding: 0; box-shadow: none; max-width: 100%; overflow-x: hidden;">
    <button onclick="togglePrintPreview()" class="btn-preview-close"><i class="fas fa-times"></i> Close Preview</button>

    <div class="db-actions-suite" style="margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid #E2E8F0; padding-bottom: 15px; margin-bottom: 20px;">
            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                <a href="?type=overview" style="text-decoration:none; color:<?php echo $report_type=='overview'?'#0F172A':'#64748B'; ?>; font-weight:700; font-size:0.9rem; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-chart-pie"></i> <?php echo __('Dashboard Summary'); ?>
                </a>
                <a href="?type=employee" style="text-decoration:none; color:<?php echo $report_type=='employee'?'#0F172A':'#64748B'; ?>; font-weight:700; font-size:0.9rem; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-address-card"></i> <?php echo __('Employee Report'); ?>
                </a>
                <a href="?type=completion" style="text-decoration:none; color:<?php echo $report_type=='completion'?'#0F172A':'#64748B'; ?>; font-weight:700; font-size:0.9rem; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-list-check"></i> <?php echo __('Completion List'); ?>
                </a>
                <a href="?type=performance" style="text-decoration:none; color:<?php echo $report_type=='performance'?'#0F172A':'#64748B'; ?>; font-weight:700; font-size:0.9rem; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-graduation-cap"></i> <?php echo __('Performance Log'); ?>
                </a>
                <a href="?type=feedback" style="text-decoration:none; color:<?php echo $report_type=='feedback'?'#0F172A':'#64748B'; ?>; font-weight:700; font-size:0.9rem; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-comment-dots"></i> <?php echo __('Feedback'); ?>
                </a>
            </div>
            <a href="?type=mis" class="btn" style="background: #10B981; color: white; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                <i class="fa-solid fa-download"></i> <?php echo __('MIS Export'); ?>
            </a>
        </div>

        <div style="display: flex; gap: 15px;">
            <button onclick="togglePrintPreview()" class="btn" style="background: white; color: #0F172A; border: 1px solid #E2E8F0; padding: 12px 30px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-eye"></i> <?php echo __('Preview & Print'); ?>
            </button>
            <button onclick="downloadPDF()" class="btn" style="background: #1E293B; color: white; border: none; padding: 12px 30px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);">
                <i class="fa-solid fa-file-pdf"></i> <?php echo __('Generate PDF'); ?>
            </button>
        </div>
    </div>

    <!-- MAIN EXPORT CONTAINER -->
    <div class="print-wrapper" id="report-content">
        
        <?php if ($report_type == 'overview'): ?>
        <!-- VISUAL DASHBOARD -->
        <div class="dashboard-container">
            <div class="row g-4">
            <!-- High Level KPI Cards -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon icon-blue"><i class="fa-solid fa-users"></i></div>
                    <div class="kpi-label"><?php echo __('Total Active Trainees'); ?></div>
                    <div class="kpi-value"><?php echo number_format($pdo->query("SELECT COUNT(*) FROM users WHERE role='trainee' AND status='active'")->fetchColumn()); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon icon-emerald"><i class="fa-solid fa-check-double"></i></div>
                    <div class="kpi-label"><?php echo __('Training Completion Rate'); ?></div>
                    <div class="kpi-value">
                        <?php 
                            $total = array_sum($stats['completion']);
                            $comp = $stats['completion']['completed'] ?? 0;
                            echo $total > 0 ? round(($comp / $total) * 100, 1) . '%' : '0%';
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon icon-amber"><i class="fa-solid fa-bolt"></i></div>
                    <div class="kpi-label"><?php echo __('Average Exam Score'); ?></div>
                    <div class="kpi-value"><?php echo round($stats['scores']['avg'] ?? 0, 1); ?>%</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon icon-rose"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="kpi-label"><?php echo __('Active Blocked Users'); ?></div>
                    <div class="kpi-value"><?php echo number_format($pdo->query("SELECT COUNT(*) FROM users WHERE status='inactive'")->fetchColumn()); ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-7">
                <div class="glass-card h-100">
                    <h3 class="card-title"><?php echo __('Module Popularity & Success Distribution'); ?></h3>
                    <div style="height: 350px;">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="glass-card h-100">
                    <h3 class="card-title"><?php echo __('Completion Status Breakdown'); ?></h3>
                    <div style="height: 250px; display: flex; justify-content: center;">
                        <canvas id="donutChart"></canvas>
                    </div>
                    <div class="chart-legend-custom mt-4">
                        <?php foreach($stats['completion'] as $status => $count): ?>
                            <div class="legend-item">
                                <span class="dot" style="background: <?php echo $status=='completed'?'#10B981':($status=='in_progress'?'#F59E0B':'#64748B'); ?>"></span>
                                <span class="lbl"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                <span class="val"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
</div>
                </div>
                <div class="chart-card">
                    <div class="chart-title"><i class="fas fa-star" style="color: #F59E0B; margin-right: 8px;"></i> <?php echo __('Feedback Sentiment Breakdown'); ?></div>
                    <div class="chart-wrapper" style="height: 220px; display: flex; justify-content: center;"><canvas id="pieChart"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="chart-title"><i class="fas fa-history" style="color: #8B5CF6; margin-right: 8px;"></i> Monthly Completion Trend</div>
                    <div class="chart-wrapper"><canvas id="lineChart"></canvas></div>
                </div>
            </div>

            <!-- COMPULSORY FOOTER -->
            <div class="report-footer">
                <div>Confidential - Internal Use Only</div>
                <div class="powered-by-logo">
                    Powered By 
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg" alt="Learnlike" style="height: 22px;" onerror="this.outerHTML='<span style=\'color:#4A5568;font-weight:900;background:#E2E8F0;padding:4px 8px;border-radius:4px;display:inline-flex;align-items:center;gap:5px;\'><i class=\'fas fa-layer-group\'></i> Learnlike</span>';">
                </div>
            </div>
        </div>
        
        <script>
            const moduleLabels = <?php echo json_encode(array_keys($stats['modules'])); ?>;
            const moduleScores = <?php echo json_encode(array_values($stats['modules'])); ?>;
            const compData = [<?php echo $stats['completion']['completed'] ?? 0; ?>, <?php echo $stats['completion']['in_progress'] ?? 0; ?>, <?php echo $stats['completion']['not_started'] ?? 0; ?>];
            const feedData = [<?php echo $stats['feedback']['5'] ?? 0; ?>, <?php echo $stats['feedback']['4'] ?? 0; ?>, <?php echo $stats['feedback']['3'] ?? 0; ?>];

            Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
            Chart.defaults.color = '#94A3B8';
            Chart.defaults.plugins.tooltip.backgroundColor = '#0F172A';
            Chart.defaults.plugins.tooltip.padding = 12;
            Chart.defaults.plugins.tooltip.cornerRadius = 8;
            Chart.defaults.plugins.legend.labels.usePointStyle = true;
            Chart.defaults.plugins.legend.labels.boxWidth = 10;
            Chart.defaults.plugins.legend.labels.font = { weight: '600', size: 13 };

            new Chart(document.getElementById('barChart'), {
                type: 'bar',
                data: {
                    labels: moduleLabels.length ? moduleLabels : ['Basic Safety', 'ESD Control', '5S Practices'],
                    datasets: [{
                        label: 'Average Score (%)',
                        data: moduleScores.length ? moduleScores : [88, 75, 92],
                        backgroundColor: '#3B82F6',
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100, grid: { borderDash: [4, 4], color: '#F1F5F9' } }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
            });

            new Chart(document.getElementById('donutChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Not Started'],
                    datasets: [{ data: compData.every(x => x===0) ? [45, 25, 30] : compData, backgroundColor: ['#10B981', '#F59E0B', '#E2E8F0'], borderWidth: 0 }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: window.innerWidth < 1200 ? 'bottom' : 'right' } } }
            });

            new Chart(document.getElementById('pieChart'), {
                type: 'pie',
                data: {
                    labels: ['5 Stars', '4 Stars', '3 Stars & Below'],
                    datasets: [{ data: feedData.every(x => x===0) ? [60, 30, 10] : feedData, backgroundColor: ['#F59E0B', '#3B82F6', '#94A3B8'], borderWidth: 0 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: window.innerWidth < 1200 ? 'bottom' : 'right' } } }
            });

            new Chart(document.getElementById('lineChart'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{ label: 'Assessments', data: [12, 19, 15, 25, 22, 30], borderColor: '#8B5CF6', borderWidth: 3, tension: 0.4, fill: true, backgroundColor: 'rgba(139, 92, 246, 0.1)', pointBackgroundColor: '#fff', pointBorderWidth: 2, pointRadius: 4 }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, grid: { borderDash: [4, 4], color: '#F1F5F9' } }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
            });
        </script>

        <?php elseif ($report_type == 'employee'): ?>
        <!-- EMPLOYEE REPORT PROFILE -->
        <?php
        // Fetch all employees for autocomplete
        $all_emps = $pdo->query("SELECT id, employee_id, full_name, batch_number, role, department, doj, qualification FROM users WHERE role != 'admin'")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div style="background: #F8FAFC; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 25px;">
            <form method="GET" action="reports.php" style="display: flex; gap: 15px; align-items: flex-end;" id="empSearchForm">
                <input type="hidden" name="type" value="employee">
                <div class="form-group" style="margin: 0; flex: 1; max-width: 600px; position: relative;">
                    <label class="form-label" style="font-weight: 700;">Search Employee Data</label>
                    <input type="text" id="empSearchInput" class="form-control" placeholder="Search by name, ID, batch, role, dept, joining date..." value="<?php echo e($_GET['emp_id'] ?? ''); ?>" autocomplete="off" required>
                    <input type="hidden" name="emp_id" id="empIdValue" value="<?php echo e($_GET['emp_id'] ?? ''); ?>">
                    
                    <div id="empSearchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #E2E8F0; border-radius: 10px; max-height: 350px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-top: 5px;">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 48px; padding: 0 25px;"><i class="fas fa-search"></i> Find Record</button>
            </form>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const employeesList = <?php echo json_encode($all_emps); ?>;
            const searchInput = document.getElementById('empSearchInput');
            const idValue = document.getElementById('empIdValue');
            const resultsDiv = document.getElementById('empSearchResults');
            const searchForm = document.getElementById('empSearchForm');

            function formatDate(dateString) {
                if (!dateString) return '';
                const d = new Date(dateString);
                return d.getDate() + ' ' + d.toLocaleString('default', { month: 'short' }) + ' ' + d.getFullYear();
            }

            if (idValue.value) {
                const found = employeesList.find(e => e.employee_id === idValue.value);
                if (found) searchInput.value = found.full_name + ' (' + found.employee_id + ')';
            }

            searchInput.addEventListener('input', function() {
                const val = this.value.toLowerCase();
                resultsDiv.innerHTML = '';
                
                if (!val) {
                    resultsDiv.style.display = 'none';
                    idValue.value = '';
                    return;
                }
                
                idValue.value = this.value;

                const matches = employeesList.filter(emp => {
                    const searchStr = [
                        emp.full_name, emp.employee_id, emp.batch_number, 
                        emp.role, emp.department, 
                        formatDate(emp.doj), emp.qualification
                    ].join(' ').toLowerCase();
                    return searchStr.includes(val);
                });

                if (matches.length > 0) {
                    matches.forEach(emp => {
                        const div = document.createElement('div');
                        div.style.cssText = 'padding: 12px 15px; border-bottom: 1px solid #F1F5F9; cursor: pointer; transition: background 0.2s; text-align: left;';
                        div.onmouseover = () => div.style.background = '#F8FAFC';
                        div.onmouseout = () => div.style.background = '#fff';
                        
                        const roleBadge = emp.role == 'management' ? 'Manager' : (emp.role == 'trainer' ? 'Trainer' : 'Trainee');
                        const batchHtml = emp.batch_number ? `<span style="margin-right: 10px; color: #64748B;"><i class="fas fa-layer-group"></i> ${emp.batch_number}</span>` : '';
                        const deptHtml = emp.department ? `<span style="margin-right: 10px; color: #64748B;"><i class="fas fa-building"></i> ${emp.department}</span>` : '';
                        const qualHtml = emp.qualification ? `<span style="margin-right: 10px; color: #64748B;"><i class="fas fa-graduation-cap"></i> ${emp.qualification}</span>` : '';
                        
                        div.innerHTML = `
                            <div style="font-weight: 700; color: #0F172A; font-size: 0.95rem; display: flex; justify-content: space-between;">
                                <span>${emp.full_name} <span style="color: #64748B; font-weight: 500;">(${emp.employee_id})</span></span>
                                <span style="font-size: 0.75rem; background: #E2E8F0; padding: 2px 8px; border-radius: 4px; color: #475569;">${roleBadge}</span>
                            </div>
                            <div style="font-size: 0.8rem; margin-top: 5px; display: flex; flex-wrap: wrap;">
                                ${deptHtml} ${batchHtml} ${qualHtml}
                            </div>
                        `;
                        
                        div.onclick = function() {
                            searchInput.value = emp.full_name + ' (' + emp.employee_id + ')'; 
                            idValue.value = emp.employee_id;
                            resultsDiv.style.display = 'none';
                            searchForm.submit();
                        };
                        resultsDiv.appendChild(div);
                    });
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div style="padding: 15px; text-align: center; color: #94A3B8; font-size: 0.9rem;">No records found</div>';
                    resultsDiv.style.display = 'block';
                }
            });

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    if (resultsDiv.style.display === 'block' && resultsDiv.children.length > 0) {
                        e.preventDefault();  
                        resultsDiv.children[0].click();
                    }
                }
            });

            document.addEventListener('click', function(e) {
                if (e.target !== searchInput && e.target !== resultsDiv && !resultsDiv.contains(e.target)) {
                    resultsDiv.style.display = 'none';
                }
            });
        });
        </script>

        <?php 
        if (!empty($_GET['emp_id'])): 
            $emp_id = $_GET['emp_id'];
            $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_id = ?");
            $stmt->execute([$emp_id]);
            $emp = $stmt->fetch();

            if (!$emp):
                echo "<div style='color: var(--danger); font-weight: bold; padding: 20px; text-align: center; background: #fff5f5; border-radius: 8px;'>⚠️ Employee ID not found.</div>";
            else:
                $astmt = $pdo->prepare("SELECT a.*, m.title as module_name FROM assignments a JOIN training_modules m ON a.module_id = m.id WHERE a.trainee_id = ? ORDER BY a.assigned_date DESC");
                $astmt->execute([$emp['id']]); $assignments = $astmt->fetchAll();

                $exstmt = $pdo->prepare("SELECT r.*, e.title as exam_name FROM exam_results r JOIN exams e ON r.exam_id = e.id WHERE r.trainee_id = ? ORDER BY r.exam_date DESC");
                $exstmt->execute([$emp['id']]); $exams = $exstmt->fetchAll();

                $indstmt = $pdo->prepare("SELECT * FROM induction_scores WHERE trainee_id = ?");
                $indstmt->execute([$emp['id']]); $induction = $indstmt->fetch();
        ?>
        <div class="dashboard-container employee-report-card">
            <div class="db-header">
                <div>
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" style="height: 35px; margin-bottom: 10px;" onerror="this.style.display='none';">
                    <div class="db-title">Employee Training Profile</div>
                    <div class="db-subtitle">Comprehensive Skill & Performance Record</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: #0EA5E9; font-size: 1.2rem;"><?php echo date('F d, Y'); ?></div>
                    <div style="color: #64748B; font-weight: 600; font-size: 0.9rem; margin-top: 4px;">Record ID: TR-<?php echo time(); ?></div>
                </div>
            </div>

            <!-- Employee Info Header -->
            <div style="display: flex; gap: 30px; margin-bottom: 40px; background: #F8FAFC; padding: 25px; border-radius: 16px; border: 1px solid #E2E8F0;">
                <?php if ($emp['photo_path']): ?>
                <div style="width: 130px; height: 130px; border-radius: 12px; overflow: hidden; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); flex-shrink: 0;">
                    <img src="<?php echo BASE_URL . $emp['photo_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <?php else: ?>
                <div style="width: 130px; height: 130px; border-radius: 12px; background: #E2E8F0; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; font-size: 3.5rem; color: #94A3B8; flex-shrink: 0;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <?php endif; ?>

                <div style="flex: 1;">
                    <h2 style="margin: 0 0 15px 0; color: #0F172A; font-size: 2rem; font-weight: 800;"><?php echo e($emp['full_name']); ?></h2>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; row-gap: 15px;">
                        <div><span style="color:#64748B; font-weight:600; font-size:0.9rem; text-transform:uppercase;">Emp ID:</span> <span style="font-weight:700; color:#1E293B; margin-left: 8px;"><?php echo e($emp['employee_id']); ?></span></div>
                        <div><span style="color:#64748B; font-weight:600; font-size:0.9rem; text-transform:uppercase;">Role:</span> <span style="font-weight:700; color:#1E293B; margin-left: 8px;"><?php echo ucfirst($emp['role']); ?></span></div>
                        <div><span style="color:#64748B; font-weight:600; font-size:0.9rem; text-transform:uppercase;">Department:</span> <span style="font-weight:700; color:#1E293B; margin-left: 8px;"><?php echo e($emp['department'] ?? 'N/A'); ?></span></div>
                        <div><span style="color:#64748B; font-weight:600; font-size:0.9rem; text-transform:uppercase;">Batch No:</span> <span style="font-weight:700; color:#1E293B; margin-left: 8px;"><?php echo e($emp['batch_number'] ?? 'N/A'); ?></span></div>
                        <div><span style="color:#64748B; font-weight:600; font-size:0.9rem; text-transform:uppercase;">Joining Date:</span> <span style="font-weight:700; color:#1E293B; margin-left: 8px;"><?php echo (!empty($emp['doj']) && $emp['doj'] !== '0000-00-00') ? date('d M Y', strtotime($emp['doj'])) : 'N/A'; ?></span></div>
                        <div><span style="color:#64748B; font-weight:600; font-size:0.9rem; text-transform:uppercase;">Leaving Date:</span> <span style="font-weight:700; color:#1E293B; margin-left: 8px;"><?php echo (!empty($emp['dol']) && $emp['dol'] !== '0000-00-00') ? date('d M Y', strtotime($emp['dol'])) : 'N/A'; ?></span></div>
                        <div><span style="color:#64748B; font-weight:600; font-size:0.9rem; text-transform:uppercase;">Qualification:</span> <span style="font-weight:700; color:#1E293B; margin-left: 8px;"><?php echo e($emp['qualification'] ?? 'N/A'); ?></span></div>
                    </div>
                </div>
            </div>

            <!-- Tables -->
            <div class="emp-section-heading">Induction Training Scores</div>
            <table class="employee-data-table">
                <thead>
                    <tr><th>About Syrma</th><th>Safety Test</th><th>PPE Test</th><th>5S Test</th><th>ESD Test</th><th>E-Module Test</th></tr>
                </thead>
                <tbody>
                    <?php if ($induction): ?>
                    <tr style="text-align: center; font-weight: 800; color: #0F172A; font-size: 1.1rem !important;">
                        <td><?php echo $induction['topic_1_score']; ?>%</td>
                        <td><?php echo $induction['topic_2_score']; ?>%</td>
                        <td><?php echo $induction['topic_3_score']; ?>%</td>
                        <td><?php echo $induction['topic_4_score']; ?>%</td>
                        <td><?php echo $induction['topic_5_score']; ?>%</td>
                        <td><?php echo $induction['topic_6_score']; ?>%</td>
                    </tr>
                    <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; color: #94A3B8; padding: 20px;">No induction records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="emp-section-heading">Modules & Practical Training</div>
            <table class="employee-data-table">
                <thead><tr><th>Module Assignments</th><th style="width:160px;">Date Assigned</th><th style="width:160px;">Status</th><th style="width:160px;">Date Completed</th></tr></thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                    <tr>
                        <td style="font-weight: 700; color: #334155;"><?php echo e($a['module_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($a['assigned_date'])); ?></td>
                        <td style="color: <?php echo $a['status'] == 'completed' ? '#10B981' : '#F59E0B'; ?>; font-weight: 800;"><?php echo strtoupper($a['status']); ?></td>
                        <td><?php echo $a['completion_date'] ? date('d M Y', strtotime($a['completion_date'])) : '-'; ?></td>
                    </tr>
                    <?php endforeach; if (empty($assignments)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #94A3B8; padding: 20px;">No modules assigned yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="emp-section-heading">Final Assessment Accreditations</div>
            <table class="employee-data-table">
                <thead><tr><th>Exam Title</th><th style="width:160px;">Date Taken</th><th style="width:120px;">Score</th><th style="width:160px;">Accreditation</th></tr></thead>
                <tbody>
                    <?php foreach ($exams as $ex): ?>
                    <tr>
                        <td style="font-weight: 700; color: #334155;"><?php echo e($ex['exam_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($ex['exam_date'])); ?></td>
                        <td style="font-weight: 800; color: #0F172A;"><?php echo $ex['score']; ?>%</td>
                        <td><span style="background: <?php echo $ex['status'] == 'pass' ? '#DCFCE7' : '#FEE2E2'; ?>; color: <?php echo $ex['status'] == 'pass' ? '#16A34A' : '#DC2626'; ?>; padding: 4px 12px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; text-transform: uppercase;"><?php echo $ex['status']; ?></span></td>
                    </tr>
                    <?php endforeach; if (empty($exams)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #94A3B8; padding: 20px;">No assessments taken yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Signatures -->
            <div style="margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr 1fr; text-align: center; color: #64748B; font-weight: 700; font-size: 0.95rem;">
                <div><div style="border-top: 2px dashed #CBD5E1; margin: 0 40px 15px 40px;"></div>Signature of Trainer</div>
                <div><div style="border-top: 2px dashed #CBD5E1; margin: 0 40px 15px 40px;"></div>Signature of Manager</div>
                <div><div style="border-top: 2px dashed #CBD5E1; margin: 0 40px 15px 40px;"></div>Signature of Employee</div>
            </div>

            <!-- COMPULSORY FOOTER -->
            <div class="report-footer" style="margin-top: 60px;">
                <div>Confidential - Internal Use Only</div>
                <div class="powered-by-logo">
                    Powered By 
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg" alt="Learnlike" style="height: 22px;" onerror="this.outerHTML='<span style=\'color:#4A5568;font-weight:900;background:#E2E8F0;padding:4px 8px;border-radius:4px;display:inline-flex;align-items:center;gap:5px;\'><i class=\'fas fa-layer-group\'></i> Learnlike</span>';">
                </div>
            </div>
        </div>
        <?php endif; endif; ?>

        <?php elseif ($report_type == 'completion' || $report_type == 'performance' || $report_type == 'feedback'): ?>
        <!-- LIST REPORTS -->
        <div class="dashboard-container">
            <div class="db-header">
                <div>
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" style="height: 35px; margin-bottom: 10px;" onerror="this.style.display='none';">
                    <div class="db-title"><?php 
                        if ($report_type == 'completion') echo 'Training Completion List';
                        elseif ($report_type == 'performance') echo 'Performance Log';
                        else echo 'Feedback Summary';
                    ?></div>
                    <div class="db-subtitle">Current System Records & Audit Log</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: #0EA5E9; font-size: 1.2rem;"><?php echo date('F d, Y'); ?></div>
                </div>
            </div>

            <?php if ($report_type == 'completion'): ?>
            <table class="employee-data-table">
                <thead><tr><th>Trainee Name</th><th>Module</th><th>Trainer</th><th>Status</th><th>Comp. Date</th></tr></thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT a.*, u.full_name as trainee_name, tr.full_name as trainer_name, m.title as module_name FROM assignments a JOIN users u ON a.trainee_id = u.id JOIN users tr ON a.trainer_id = tr.id JOIN training_modules m ON a.module_id = m.id ORDER BY a.completion_date DESC");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td style="font-weight: 700; color: #0F172A;"><?php echo e($row['trainee_name']); ?></td>
                        <td><?php echo e($row['module_name']); ?></td>
                        <td><?php echo e($row['trainer_name']); ?></td>
                        <td><span style="background: <?php echo $row['status'] == 'completed' ? '#DCFCE7' : '#FEF3C7'; ?>; color: <?php echo $row['status'] == 'completed' ? '#10B981' : '#F59E0B'; ?>; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase;"><?php echo $row['status']; ?></span></td>
                        <td><?php echo $row['completion_date'] ? date('d M Y', strtotime($row['completion_date'])) : '-'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php elseif ($report_type == 'performance'): ?>
            <table class="employee-data-table">
                <thead><tr><th>Trainee</th><th>Exam</th><th>Score</th><th>Attempts</th><th>Result</th></tr></thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT r.*, u.full_name, e.title as exam_name FROM exam_results r JOIN users u ON r.trainee_id = u.id JOIN exams e ON r.exam_id = e.id");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td style="font-weight: 700; color: #0F172A;"><?php echo e($row['full_name']); ?></td>
                        <td><?php echo e($row['exam_name']); ?></td>
                        <td style="font-weight: 800;"><?php echo $row['score']; ?>%</td>
                        <td>1</td>
                        <td><span style="background: <?php echo $row['status'] == 'pass' ? '#DCFCE7' : '#FEE2E2'; ?>; color: <?php echo $row['status'] == 'pass' ? '#16A34A' : '#DC2626'; ?>; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase;"><?php echo $row['status']; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php elseif ($report_type == 'feedback'): ?>
            <table class="employee-data-table">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Overall</th>
                        <th>Skill</th>
                        <th>Explanation</th>
                        <th>Time</th>
                        <th>Comments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT f.*, u.full_name 
                        FROM feedback f 
                        JOIN users u ON f.trainee_id = u.id 
                        ORDER BY f.submitted_at DESC
                    ");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td style="font-weight: 700; color: #0F172A;"><?php echo e($row['full_name']); ?></td>
                        <td><span style="background: rgba(11, 112, 183, 0.1); color: #0B70B7; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.8rem;"><?php echo $row['rating_overall']; ?></span></td>
                        <td><span style="background: #F1F5F9; color: #475569; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;"><?php echo $row['rating_learning_skill']; ?></span></td>
                        <td><span style="background: #F1F5F9; color: #475569; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;"><?php echo $row['rating_explanation']; ?></span></td>
                        <td><span style="background: #F1F5F9; color: #475569; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;"><?php echo $row['rating_time']; ?></span></td>
                        <td style="font-size: 0.85rem; color: #64748B;"><?php echo e($row['comments']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($stmt->rowCount() == 0): ?>
                        <tr><td colspan="6" style="text-align: center; color: #94A3B8; padding: 40px;">No feedback submissions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- COMPULSORY FOOTER -->
            <div class="report-footer" style="margin-top: 40px;">
                <div>Confidential - Internal Use Only</div>
                <div class="powered-by-logo">
                    Powered By 
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg" alt="Learnlike" style="height: 22px;" onerror="this.outerHTML='<span style=\'color:#4A5568;font-weight:900;background:#E2E8F0;padding:4px 8px;border-radius:4px;display:inline-flex;align-items:center;gap:5px;\'><i class=\'fas fa-layer-group\'></i> Learnlike</span>';">
                </div>
            </div>
        </div>
        <?php elseif ($report_type == 'mis'): ?>
        <!-- ─── MIS DATA EXPORT ─── -->
        <?php
        // Fetch summary stats
        $mis_trainees = $pdo->query("SELECT COUNT(*) FROM users WHERE role='trainee' AND status='active'")->fetchColumn();
        $mis_completed = $pdo->query("SELECT COUNT(*) FROM assignments WHERE status='completed'")->fetchColumn();
        $mis_overdue  = $pdo->query("SELECT COUNT(*) FROM refresher_training WHERE status='overdue'")->fetchColumn();
        $mis_avg = $pdo->query("SELECT ROUND(AVG(score)) FROM exam_results")->fetchColumn();
        ?>
        <div class="dashboard-container">
            <div class="db-header" style="flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; width: 100%;">
                <div style="flex: 1; min-width: 250px;">
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" style="height: 30px; margin-bottom: 8px;" onerror="this.style.display='none';">
                    <div class="db-title" style="font-size: 1.3rem;"><?php echo __('MIS Data Export'); ?></div>
                    <div class="db-subtitle" style="font-size: 0.85rem;"><?php echo __('Management Information System — Complete Trainee Data'); ?></div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px; margin-top: 5px;">
                    <div style="font-weight:700; color:#0EA5E9; font-size:0.9rem;"><?php echo date('F d, Y'); ?></div>
                    <a href="?type=mis&download_csv=1" class="btn" style="background: linear-gradient(135deg, #059669, #10B981); color:#fff; padding: 10px 18px; font-weight:800; border-radius:10px; font-size:0.82rem; display:inline-flex; align-items:center; gap:8px; border: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                        <i class="fas fa-file-csv" style="font-size: 1rem;"></i> <?php echo __('Download CSV'); ?>
                    </a>
                </div>
            </div>

            <!-- Quick stats -->
            <div class="row g-4" style="margin-bottom:30px;">
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon icon-blue"><i class="fa-solid fa-users"></i></div>
                        <div class="kpi-label"><?php echo __('Active Trainees'); ?></div>
                        <div class="kpi-value"><?php echo $mis_trainees; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon icon-emerald"><i class="fa-solid fa-graduation-cap"></i></div>
                        <div class="kpi-label"><?php echo __('Completed Modules'); ?></div>
                        <div class="kpi-value" style="color:#10B981;"><?php echo $mis_completed; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon icon-amber"><i class="fa-solid fa-star"></i></div>
                        <div class="kpi-label"><?php echo __('Avg Exam Score'); ?></div>
                        <div class="kpi-value" style="color:#F59E0B;"><?php echo $mis_avg ?: 0; ?>%</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon icon-rose"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div class="kpi-label"><?php echo __('Overdue Refreshers'); ?></div>
                        <div class="kpi-value" style="color:#EF4444;"><?php echo $mis_overdue; ?></div>
                    </div>
                </div>
            </div>

            <!-- MIS Table Preview -->
            <?php
            $misPreview = $pdo->query("
                SELECT u.employee_id, u.full_name, u.department, u.batch_number, u.doj,
                       m.title as module_name, a.status as assign_status,
                       (SELECT er2.score FROM exam_results er2 JOIN exams e2 ON er2.exam_id = e2.id WHERE er2.trainee_id = u.id AND e2.module_id = a.module_id ORDER BY er2.id DESC LIMIT 1) as exam_score,
                       (SELECT er2.status FROM exam_results er2 JOIN exams e2 ON er2.exam_id = e2.id WHERE er2.trainee_id = u.id AND e2.module_id = a.module_id ORDER BY er2.id DESC LIMIT 1) as exam_result,
                       (SELECT COUNT(*) FROM trainee_checklist_progress tcp WHERE tcp.trainee_id = u.id) as ind_done,
                       (SELECT COUNT(*) FROM training_stages ts WHERE ts.assignment_id = a.id AND ts.type='otj') as otj_count,
                       rt.due_date as refresher_due, rt.status as refresher_status
                FROM users u
                LEFT JOIN assignments a ON a.trainee_id = u.id
                LEFT JOIN training_modules m ON a.module_id = m.id
                LEFT JOIN refresher_training rt ON rt.trainee_id = u.id AND rt.status != 'completed'
                WHERE u.role='trainee' AND u.status='active'
                ORDER BY u.full_name
                LIMIT 50
            ")->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <table class="employee-data-table" id="misTable">
                <thead>
                    <tr>
                        <th>Emp ID</th>
                        <th>Name</th>
                        <th>Dept</th>
                        <th>Batch</th>
                        <th>Module</th>
                        <th>Status</th>
                        <th>Exam</th>
                        <th>Induction</th>
                        <th>OJT Stages</th>
                        <th>Refresher</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($misPreview as $r):
                    $sc = $r['assign_status'] === 'completed' ? '#10b981' : ($r['assign_status'] === 'in_progress' ? '#f59e0b' : '#94a3b8');
                    $ec = $r['exam_result'] === 'pass' ? '#10b981' : ($r['exam_result'] === 'fail' ? '#ef4444' : '#94a3b8');
                ?>
                <tr>
                    <td style="font-weight:700; font-size:0.82rem;"><?php echo e($r['employee_id']); ?></td>
                    <td style="font-weight:700; color:#0f172a;"><?php echo e($r['full_name']); ?></td>
                    <td style="font-size:0.82rem;"><?php echo e($r['department'] ?? '-'); ?></td>
                    <td style="font-size:0.82rem;"><?php echo e($r['batch_number'] ?? '-'); ?></td>
                    <td style="font-size:0.82rem;"><?php echo e($r['module_name'] ?? 'N/A'); ?></td>
                    <td><span style="background:<?php echo $sc; ?>20; color:<?php echo $sc; ?>; padding:3px 8px; border-radius:6px; font-weight:800; font-size:0.72rem;"><?php echo strtoupper(str_replace('_',' ',$r['assign_status'] ?? 'N/A')); ?></span></td>
                    <td>
                        <?php if ($r['exam_score'] !== null): ?>
                        <span style="color:<?php echo $ec; ?>; font-weight:800;"><?php echo $r['exam_score']; ?>% <span style="font-size:0.7rem;">(<?php echo strtoupper($r['exam_result']); ?>)</span></span>
                        <?php else: ?><span style="color:#94a3b8; font-size:0.8rem;">N/A</span><?php endif; ?>
                    </td>
                    <td style="font-size:0.85rem;"><?php echo $r['ind_done']; ?> topics</td>
                    <td style="font-size:0.85rem; text-align:center;"><?php echo $r['otj_count'] ?? 0; ?></td>
                    <td style="font-size:0.8rem;">
                        <?php if ($r['refresher_due']): ?>
                        <span style="background:<?php echo $r['refresher_status']==='overdue'?'#fef2f2':'#fefce8'; ?>; color:<?php echo $r['refresher_status']==='overdue'?'#dc2626':'#d97706'; ?>; padding:2px 8px; border-radius:5px; font-weight:700; font-size:0.72rem;">
                            <?php echo date('d M Y', strtotime($r['refresher_due'])); ?>
                            <?php echo $r['refresher_status']==='overdue'?' ⚠️':''; ?>
                        </span>
                        <?php else: ?><span style="color:#94a3b8;">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($misPreview)): ?>
                <tr><td colspan="10" style="text-align:center; color:#94a3b8; padding:30px;">No trainee data found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if (count($misPreview) >= 50): ?>
            <div style="text-align:center; margin-top:15px; color:#64748b; font-size:0.85rem; font-weight:600;">
                <i class="fas fa-info-circle"></i> Showing first 50 records. Download CSV for complete data.
            </div>
            <?php endif; ?>

            <div class="report-footer" style="margin-top:30px;">
                <div>Confidential - Internal Use Only</div>
                <div class="powered-by-logo">
                    Powered By <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg" alt="Learnlike" style="height:22px;">
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePrintPreview() {
    document.body.classList.toggle('preview-mode-active');
    
    // If we just enabled preview, offer to trigger print
    if (document.body.classList.contains('preview-mode-active')) {
        Swal.fire({
            title: 'Print Preview Active',
            text: 'You can now see exactly how the report will be aligned. Press Ctrl+P or use the Print button below to save as PDF.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-print"></i> Open Print Dialog',
            cancelButtonText: 'Just Preview',
            confirmButtonColor: '#0F172A'
        }).then((result) => {
            if (result.isConfirmed) {
                window.print();
            }
        });
    }
}
function downloadPDF() {
    const element = document.getElementById('report-content');
    const container = element.querySelector('.dashboard-container');
    const sidebar = document.querySelector('.sidebar');
    const header = document.querySelector('.header');
    const mainContent = document.querySelector('.main-content');
    
    const isEmployeeReport = <?php echo $report_type == 'employee' || $report_type == 'completion' || $report_type == 'performance' || $report_type == 'feedback' ? 'true' : 'false'; ?>;
    
    const btn = event.currentTarget || document.querySelector('button[onclick="exportReport()"]');
    const origText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
    btn.disabled = true;
    btn.style.opacity = '0.7';

    // Save Original States
    const originalStyles = {
        sidebarDisplay: sidebar ? sidebar.style.display : '',
        headerDisplay: header ? header.style.display : '',
        mainMargin: mainContent ? mainContent.style.marginLeft : '',
        mainPadding: mainContent ? mainContent.style.padding : '',
        elementWidth: element.style.width
    };

    const targetWidth = isEmployeeReport ? 800 : 1100;

    // Temporary Clean Slate for Capture
    if (sidebar) sidebar.style.display = 'none';
    if (header) header.style.display = 'none';
    if (mainContent) {
        mainContent.style.marginLeft = '0';
        mainContent.style.padding = '0';
    }
    element.style.width = targetWidth + 'px';
    
    if (container) {
        container.classList.add('pdf-export-mode');
        if (isEmployeeReport) {
            container.classList.add('pdf-export-mode-portrait');
        }
    }
    
    // Shift view to top for consistent capture
    window.scrollTo(0, 0);

    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-file-pdf"></i> Capturing...';
        
        const rect = element.getBoundingClientRect();

        const opt = {
            margin:       [0.3, 0.3, 0.3, 0.3],
            filename:     'OTR_Analytical_Report.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { 
                scale: 2, 
                useCORS: true, 
                width: rect.width,
                height: rect.height + 20, // Add buffer for legends/borders
                windowWidth: rect.width,
                x: rect.left,
                y: rect.top,
                logging: false, 
                allowTaint: true 
            },
            jsPDF:        { unit: 'in', format: 'a4', orientation: isEmployeeReport ? 'portrait' : 'landscape' },
            pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            // Restore Original States
            if (sidebar) sidebar.style.display = originalStyles.sidebarDisplay;
            if (header) header.style.display = originalStyles.headerDisplay;
            if (mainContent) {
                mainContent.style.marginLeft = originalStyles.mainMargin;
                mainContent.style.padding = originalStyles.mainPadding;
            }
            element.style.width = originalStyles.elementWidth;
            
            if (container) {
                container.classList.remove('pdf-export-mode');
                container.classList.remove('pdf-export-mode-portrait');
            }
            
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Downloaded!';
            btn.style.background = 'linear-gradient(135deg, #059669, #10B981)';
            setTimeout(() => {
                btn.innerHTML = origText;
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.background = 'linear-gradient(135deg, #0F172A, #334155)';
            }, 2000);
        }).catch((err) => {
            console.error('PDF Export Error:', err);
            if (sidebar) sidebar.style.display = originalStyles.sidebarDisplay;
            if (header) header.style.display = originalStyles.headerDisplay;
            if (mainContent) {
                mainContent.style.marginLeft = originalStyles.mainMargin;
                mainContent.style.padding = originalStyles.mainPadding;
            }
            element.style.width = originalStyles.elementWidth;
            
            if (container) {
                container.classList.remove('pdf-export-mode');
                container.classList.remove('pdf-export-mode-portrait');
            }
            btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Retry';
            btn.disabled = false;
            btn.style.opacity = '1';
            setTimeout(() => { btn.innerHTML = origText; }, 3000);
        });
    }, 700); 
}
</script>

<?php renderFooter(); ?>
