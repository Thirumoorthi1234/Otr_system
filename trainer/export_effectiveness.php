<?php
/**
 * trainer/export_effectiveness.php
 * Generates a professional PDF for the Trainee Effectiveness Report (Syrma SGS)
 * Clean server-side generation — no screenshot, no browser-capture artifacts.
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
checkRole('trainer');

$trainer_id   = $_SESSION['user_id'];
$trainer_name = $_SESSION['full_name'] ?? 'Trainer';

// ── Fetch All Trainee Performance Data ──────────────────────────────
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.employee_id, u.department,
           a.status as assignment_status, a.assigned_date, a.completion_date,
           m.title as module_name,
           ROUND(AVG(er.score), 1) as avg_score,
           COUNT(er.id) as exam_count,
           SUM(CASE WHEN er.status = 'pass' THEN 1 ELSE 0 END) as pass_count,
           MAX(er.score) as best_score,
           (SELECT COALESCE(SUM(ts.man_hours), 0) FROM training_stages ts WHERE ts.assignment_id = a.id AND ts.type = 'ojt') as ojt_hours,
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
$stmt->execute([$trainer_id]);
$traineeList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Aggregate Stats ──────────────────────────────────────────────────
$totalTrainees   = count($traineeList);
$completedCount  = count(array_filter($traineeList, fn($t) => $t['assignment_status'] == 'completed'));
$withScores      = array_filter($traineeList, fn($t) => $t['avg_score'] > 0);
$avgTeamScore    = count($withScores) > 0 ? round(array_sum(array_column($withScores, 'avg_score')) / count($withScores), 1) : 0;
$totalPasses     = array_sum(array_column($traineeList, 'pass_count'));
$totalExams      = array_sum(array_column($traineeList, 'exam_count'));
$overallPassRate = $totalExams > 0 ? round(($totalPasses / $totalExams) * 100) : 0;
$highRiskCount   = count(array_filter($traineeList, fn($t) => $t['avg_score'] !== null && $t['avg_score'] < 60 && $t['exam_count'] > 0));

$logoPath        = $_SERVER['DOCUMENT_ROOT'] . '/otr/assets/img/profiles/logo.svg';
$logoSrc         = BASE_URL . 'assets/img/profiles/logo.svg';
$poweredByPath   = $_SERVER['DOCUMENT_ROOT'] . '/otr/assets/img/profiles/powered_by.svg';
$poweredBySrc    = BASE_URL . 'assets/img/profiles/powered_by.svg';
// Embed as data URI so it renders in print/PDF without URL issues
$poweredByData   = file_exists($poweredByPath)
    ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($poweredByPath))
    : $poweredBySrc;
$exportDate = date('d M Y');
$exportTime = date('h:i A');

// ── Color helper ─────────────────────────────────────────────────────
function scoreColor(float|null $score): string {
    if ($score === null || $score == 0) return '#94A3B8';
    if ($score >= 80) return '#059669';
    if ($score >= 60) return '#D97706';
    return '#DC2626';
}
function scoreBg(float|null $score): string {
    if ($score === null || $score == 0) return '#F8FAFC';
    if ($score >= 80) return '#ECFDF5';
    if ($score >= 60) return '#FFFBEB';
    return '#FEF2F2';
}
function scoreLabel(float|null $score): string {
    if ($score === null || $score == 0) return 'No Data';
    if ($score >= 80) return 'Excellent';
    if ($score >= 60) return 'Good';
    if ($score >= 40) return 'Average';
    return 'Needs Improvement';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Effectiveness Report – <?php echo htmlspecialchars($trainer_name); ?> | <?php echo $exportDate; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Inter', 'Segoe UI', sans-serif;
        background: #F8FAFC;
        color: #0F172A;
        font-size: 13px;
        line-height: 1.5;
    }

    /* ── Print / Page Layout ── */
    @media print {
        body { background: #fff !important; }
        .no-print { display: none !important; }
        .page-break { page-break-before: always; }
        .card { box-shadow: none !important; border: 1px solid #E2E8F0 !important; }
    }

    .page-wrapper {
        max-width: 1100px;
        margin: 0 auto;
        padding: 32px 40px;
        background: #fff;
        min-height: 100vh;
    }

    /* ── Toolbar (Print / Download) ── */
    .toolbar {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-bottom: 24px;
    }
    .toolbar button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-print  { background: #1E40AF; color: #fff; }
    .btn-print:hover  { background: #1E3A8A; }
    .btn-close  { background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; }
    .btn-close:hover  { background: #E2E8F0; }

    /* ── Report Header ── */
    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 28px 32px;
        background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%);
        border-radius: 16px;
        color: #fff;
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
    }
    .report-header::after {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
    }
    .rh-logo { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; }
    .rh-logo img { height: 42px; filter: brightness(0) invert(1); }
    .rh-company { font-size: 15px; font-weight: 900; letter-spacing: 1px; color: #93C5FD; }
    .rh-title { font-size: 22px; font-weight: 900; color: #fff; margin-bottom: 4px; }
    .rh-subtitle { font-size: 13px; color: #93C5FD; font-weight: 500; }
    .rh-meta { text-align: right; }
    .rh-meta-label { font-size: 11px; color: #93C5FD; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
    .rh-meta-value { font-size: 13px; color: #fff; font-weight: 700; margin-bottom: 8px; }

    /* ── Summary Cards ── */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }
    .summary-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px 22px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        position: relative;
        overflow: hidden;
    }
    .summary-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        border-radius: 14px 14px 0 0;
    }
    .sc-blue::before   { background: linear-gradient(90deg, #1E40AF, #3B82F6); }
    .sc-green::before  { background: linear-gradient(90deg, #059669, #10B981); }
    .sc-amber::before  { background: linear-gradient(90deg, #D97706, #F59E0B); }
    .sc-red::before    { background: linear-gradient(90deg, #DC2626, #EF4444); }
    .sc-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748B; margin-bottom: 8px; }
    .sc-value { font-size: 32px; font-weight: 900; line-height: 1; margin-bottom: 4px; }
    .sc-blue  .sc-value { color: #1E40AF; }
    .sc-green .sc-value { color: #059669; }
    .sc-amber .sc-value { color: #D97706; }
    .sc-red   .sc-value { color: #DC2626; }
    .sc-desc { font-size: 11px; color: #94A3B8; font-weight: 500; }

    /* ── Section Heading ── */
    .section-title {
        font-size: 15px;
        font-weight: 800;
        color: #0F172A;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title span {
        width: 4px; height: 18px;
        background: linear-gradient(180deg, #1E40AF, #3B82F6);
        border-radius: 2px;
        display: inline-block;
    }

    /* ── Performance Table ── */
    .perf-table-wrap {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        overflow: hidden;
        margin-bottom: 28px;
    }
    .perf-table { width: 100%; border-collapse: collapse; }
    .perf-table thead tr {
        background: linear-gradient(90deg, #0F172A 0%, #1E3A8A 100%);
    }
    .perf-table thead th {
        padding: 12px 14px;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        text-align: left;
        white-space: nowrap;
    }
    .perf-table tbody tr { border-bottom: 1px solid #F1F5F9; }
    .perf-table tbody tr:last-child { border-bottom: none; }
    .perf-table tbody tr:nth-child(even) { background: #F8FAFC; }
    .perf-table tbody tr:hover { background: #EFF6FF; }
    .perf-table tbody td {
        padding: 11px 14px;
        font-size: 12.5px;
        vertical-align: middle;
        color: #334155;
    }
    .trainee-name { font-weight: 700; color: #0F172A; }
    .emp-id { font-size: 11px; color: #64748B; margin-top: 2px; }
    .dept-tag {
        display: inline-block;
        padding: 2px 8px;
        background: #EFF6FF;
        color: #1E40AF;
        border-radius: 20px;
        font-size: 10.5px;
        font-weight: 700;
    }
    .score-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }
    .progress-bar-wrap {
        background: #E2E8F0;
        border-radius: 20px;
        height: 6px;
        width: 70px;
        display: inline-block;
        vertical-align: middle;
        margin-right: 6px;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 20px;
    }
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
    }
    .badge-completed { background: #ECFDF5; color: #059669; }
    .badge-in_progress { background: #FFFBEB; color: #D97706; }
    .badge-not_started { background: #F1F5F9; color: #64748B; }

    /* ── Footer ── */
    .report-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        margin-top: 24px;
        font-size: 11.5px;
        color: #64748B;
        font-weight: 600;
    }
    .footer-powered { display: flex; align-items: center; gap: 8px; font-weight: 800; color: #334155; }
    .footer-powered img { height: 20px; }

    .note-box {
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-left: 4px solid #F59E0B;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 20px;
        font-size: 12px;
        color: #78350F;
        font-weight: 500;
    }
</style>
</head>
<body>
<div class="page-wrapper">

    <!-- ── Toolbar ── -->
    <div class="toolbar no-print">
        <button class="btn-close" onclick="window.history.back()">← Back</button>
        <button class="btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
    </div>

    <!-- ── Report Header ── -->
    <div class="report-header">
        <div>
            <div class="rh-logo">
                <img src="<?php echo $logoSrc; ?>" alt="Syrma SGS" onerror="this.style.display='none'">
                <div class="rh-company">SYRMA SGS</div>
            </div>
            <div class="rh-title">Trainee Effectiveness Report</div>
            <div class="rh-subtitle">Training Performance &amp; Skill Assessment Summary</div>
        </div>
        <div class="rh-meta">
            <div class="rh-meta-label">Trainer</div>
            <div class="rh-meta-value"><?php echo htmlspecialchars($trainer_name); ?></div>
            <div class="rh-meta-label">Generated</div>
            <div class="rh-meta-value"><?php echo $exportDate; ?>, <?php echo $exportTime; ?></div>
            <div class="rh-meta-label">Period</div>
            <div class="rh-meta-value">All Time</div>
        </div>
    </div>

    <!-- ── Summary Cards ── -->
    <div class="summary-grid">
        <div class="summary-card sc-blue">
            <div class="sc-label">Total Trainees</div>
            <div class="sc-value"><?php echo $totalTrainees; ?></div>
            <div class="sc-desc">Across all modules</div>
        </div>
        <div class="summary-card sc-green">
            <div class="sc-label">Completed</div>
            <div class="sc-value"><?php echo $completedCount; ?></div>
            <div class="sc-desc">Training completed</div>
        </div>
        <div class="summary-card sc-amber">
            <div class="sc-label">Avg Team Score</div>
            <div class="sc-value"><?php echo $avgTeamScore; ?>%</div>
            <div class="sc-desc">Average performance</div>
        </div>
        <div class="summary-card sc-red">
            <div class="sc-label">Overall Pass Rate</div>
            <div class="sc-value"><?php echo $overallPassRate; ?>%</div>
            <div class="sc-desc"><?php echo $totalPasses; ?> / <?php echo $totalExams; ?> exams passed</div>
        </div>
    </div>

    <?php if ($highRiskCount > 0): ?>
    <div class="note-box">
        ⚠ <strong><?php echo $highRiskCount; ?> trainee<?php echo $highRiskCount > 1 ? 's' : ''; ?></strong> 
        scored below 60% and may require additional support or re-training.
    </div>
    <?php endif; ?>

    <!-- ── Performance Table ── -->
    <div class="section-title"><span></span> Individual Trainee Performance</div>
    <div class="perf-table-wrap">
        <table class="perf-table">
            <thead>
                <tr>
                    <th style="width:28px;">#</th>
                    <th>Trainee</th>
                    <th>Module</th>
                    <th>Status</th>
                    <th>Avg Score</th>
                    <th>Pass Rate</th>
                    <th>OJT / Total</th>
                    <th>Effectiveness</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($traineeList)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding: 40px; color: #94A3B8; font-weight: 600;">
                        No trainee data available.
                    </td>
                </tr>
                <?php else: ?>
                <?php $sno = 0; foreach ($traineeList as $t): $sno++; ?>
                <?php
                    $avgScore   = $t['avg_score'];
                    $passRate   = $t['exam_count'] > 0 ? round(($t['pass_count'] / $t['exam_count']) * 100) : 0;
                    $ojtEff     = $t['total_hours'] > 0 ? round(($t['ojt_hours'] / $t['total_hours']) * 100) : 0;
                    $trainingEff = $t['exam_count'] > 0 ? round($avgScore) : null;
                    $effectiveness = $trainingEff !== null ? round(($trainingEff + $passRate) / 2) : 0;

                    $scoreC  = scoreColor($avgScore);
                    $scoreBg = scoreBg($avgScore);
                    $sLabel  = scoreLabel($avgScore);

                    $effColor = $effectiveness >= 80 ? '#059669' : ($effectiveness >= 60 ? '#D97706' : '#DC2626');
                    $effBg    = $effectiveness >= 80 ? '#ECFDF5' : ($effectiveness >= 60 ? '#FFFBEB' : '#FEF2F2');

                    $statusClass = 'badge-' . $t['assignment_status'];
                ?>
                <tr>
                    <td style="color:#94A3B8; font-weight:700; font-size:11px;"><?php echo $sno; ?></td>
                    <td>
                        <div class="trainee-name"><?php echo htmlspecialchars($t['full_name']); ?></div>
                        <div class="emp-id"><?php echo htmlspecialchars($t['employee_id']); ?></div>
                        <?php if ($t['department']): ?>
                        <div style="margin-top:3px;"><span class="dept-tag"><?php echo htmlspecialchars($t['department']); ?></span></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:600; color:#334155; max-width:160px;">
                        <?php echo htmlspecialchars($t['module_name']); ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo str_replace('_', ' ', $t['assignment_status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($avgScore > 0): ?>
                        <span class="score-pill" style="background:<?php echo $scoreBg; ?>; color:<?php echo $scoreC; ?>;">
                            <?php echo $avgScore; ?>%
                        </span>
                        <div style="font-size:10.5px; color:<?php echo $scoreC; ?>; font-weight:600; margin-top:2px;">
                            <?php echo $sLabel; ?>
                        </div>
                        <?php else: ?>
                        <span style="color:#94A3B8; font-size:12px;">No data</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($t['exam_count'] > 0): ?>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill" style="width:<?php echo $passRate; ?>%; background:<?php echo $passRate >= 70 ? '#10B981' : '#EF4444'; ?>;"></div>
                            </div>
                            <span style="font-weight:700; font-size:12px; color:<?php echo $passRate >= 70 ? '#059669' : '#DC2626'; ?>;">
                                <?php echo $passRate; ?>%
                            </span>
                        </div>
                        <div style="font-size:10.5px; color:#94A3B8; margin-top:2px;">
                            <?php echo $t['pass_count']; ?>/<?php echo $t['exam_count']; ?> exams
                        </div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="font-size:12px; font-weight:600; color:#475569;">
                        <?php echo $t['ojt_hours']; ?>h / <?php echo $t['total_hours']; ?>h
                    </td>
                    <td>
                        <span class="score-pill" style="background:<?php echo $effBg; ?>; color:<?php echo $effColor; ?>;">
                            <?php echo $effectiveness; ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Legend ── -->
    <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:24px; font-size:11.5px; font-weight:600; color:#475569;">
        <div>🟢 <strong>80%+</strong> Excellent</div>
        <div>🟡 <strong>60–79%</strong> Good</div>
        <div>🔴 <strong>&lt;60%</strong> Needs Improvement</div>
        <div style="margin-left:auto; color:#94A3B8;">
            Total records: <?php echo $totalTrainees; ?> &nbsp;|&nbsp; Generated: <?php echo $exportDate . ', ' . $exportTime; ?>
        </div>
    </div>

    <!-- ── Footer ── -->
    <div class="report-footer">
        <div>⚠ Confidential — Internal Use Only &nbsp;·&nbsp; SYRMA SGS Training Department</div>
        <div class="footer-powered">
            Powered by 
            <img src="<?php echo $poweredByData; ?>" alt="Learnlike" style="height:28px; display:inline-block; vertical-align:middle;" onerror="this.outerHTML='<strong style=\'color:#1E40AF;\'>Learnlike</strong>'">
        </div>
    </div>

</div>

<script>
// Auto-trigger print dialog if ?print=1 is in the URL
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 600));
}
</script>
</body>
</html>
