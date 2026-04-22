<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
checkRole(['admin','management']);

$type = $_GET['type'] ?? 'overview';

// Fetch all needed data
$stats = [
    'completion' => $pdo->query("SELECT status, COUNT(*) as c FROM assignments GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR),
    'scores'     => $pdo->query("SELECT AVG(score) as avg, COUNT(*) as total FROM exam_results")->fetch(),
    'modules'    => $pdo->query("SELECT m.title, AVG(r.score) as avg_score FROM exam_results r JOIN exams e ON r.exam_id=e.id JOIN training_modules m ON e.module_id=m.id GROUP BY m.id ORDER BY avg_score DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC),
    'feedback'   => $pdo->query("SELECT rating_overall, COUNT(*) as c FROM feedback GROUP BY rating_overall")->fetchAll(PDO::FETCH_KEY_PAIR),
];
$total = array_sum($stats['completion']);
$comp  = $stats['completion']['completed'] ?? 0;
$compRate = $total > 0 ? round(($comp/$total)*100,1) : 0;
$avgScore = round($stats['scores']['avg'] ?? 0, 1);
$totalTrainees = $pdo->query("SELECT COUNT(*) FROM users WHERE role='trainee' AND status='active'")->fetchColumn();
$totalTrainers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='trainer' AND status='active'")->fetchColumn();

$reportTitle = match($type) {
    'completion'  => 'Training Completion Report',
    'performance' => 'Performance Log Report',
    'feedback'    => 'Feedback Summary Report',
    'employee'    => 'Employee Training Profile',
    'mis'         => 'MIS Data Report',
    default       => 'System Analysis Report'
};
$generatedAt = date('d M Y, h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $reportTitle; ?> — Digital OTR</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  @page { size: A4 portrait; margin: 18mm 15mm; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1e293b; background:#fff; }

  /* ── Header ── */
  .report-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #0f172a; padding-bottom:14px; margin-bottom:20px; }
  .report-header .logo-area h1 { font-size:18pt; font-weight:800; color:#0f172a; }
  .report-header .logo-area p  { font-size:9pt; color:#64748b; margin-top:3px; }
  .report-header .meta { text-align:right; }
  .report-header .meta .date { font-size:10pt; font-weight:700; color:#0ea5e9; }
  .report-header .meta .generated { font-size:8pt; color:#94a3b8; margin-top:4px; }

  /* ── KPI Grid ── */
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:22px; }
  .kpi { border:1px solid #e2e8f0; border-radius:8px; padding:14px; background:#f8fafc; }
  .kpi .lbl { font-size:7.5pt; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
  .kpi .val { font-size:18pt; font-weight:800; color:#0f172a; }
  .kpi.blue  { border-left:4px solid #0ea5e9; }
  .kpi.green { border-left:4px solid #10b981; }
  .kpi.amber { border-left:4px solid #f59e0b; }
  .kpi.rose  { border-left:4px solid #ef4444; }

  /* ── Section heading ── */
  .section-title { font-size:12pt; font-weight:800; color:#0f172a; margin:20px 0 10px; padding-bottom:6px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:8px; }

  /* ── Tables ── */
  table { width:100%; border-collapse:collapse; margin-bottom:18px; font-size:9.5pt; }
  thead tr { background:#0f172a; color:#fff; }
  thead th { padding:9px 10px; text-align:left; font-weight:700; font-size:8.5pt; text-transform:uppercase; letter-spacing:.4px; }
  tbody tr { border-bottom:1px solid #f1f5f9; }
  tbody tr:nth-child(even) { background:#f8fafc; }
  tbody td { padding:8px 10px; color:#334155; }
  tbody td.bold { font-weight:700; color:#0f172a; }
  .badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:8pt; font-weight:700; text-transform:uppercase; }
  .badge.pass, .badge.completed { background:#dcfce7; color:#16a34a; }
  .badge.fail  { background:#fee2e2; color:#dc2626; }
  .badge.in_progress { background:#fef3c7; color:#d97706; }
  .badge.not_started { background:#f1f5f9; color:#64748b; }

  /* ── Module bar chart ── */
  .bar-row { display:flex; align-items:center; gap:10px; margin-bottom:8px; font-size:9pt; }
  .bar-label { width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#334155; font-weight:600; }
  .bar-track { flex:1; background:#e2e8f0; border-radius:4px; height:12px; overflow:hidden; }
  .bar-fill  { height:100%; border-radius:4px; background:linear-gradient(90deg,#0ea5e9,#6366f1); }
  .bar-pct   { width:42px; text-align:right; font-weight:700; color:#0f172a; font-size:9pt; }

  /* ── Completion donut fallback ── */
  .stat-legend { display:flex; gap:20px; flex-wrap:wrap; margin-top:10px; }
  .stat-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:5px; }

  /* ── Logos ── */
  .logo-syrma { height: 38px; object-fit: contain; }
  .logo-learnlike { height: 22px; object-fit: contain; }

  /* ── Footer ── */
  .report-footer { margin-top:30px; padding-top:12px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; font-size:8pt; color:#94a3b8; }
  .footer-brand { display:flex; align-items:center; gap:8px; }

  /* ── Signatures ── */
  .signatures { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-top:40px; text-align:center; }
  .sig-line { border-top:2px dashed #cbd5e1; padding-top:8px; font-size:9pt; color:#64748b; font-weight:600; margin:0 20px; }

  /* Print controls hidden */
  .print-controls { text-align:center; margin-bottom:20px; }
  .btn-print { background:#0f172a; color:#fff; border:none; padding:10px 28px; border-radius:8px; font-size:11pt; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
  .btn-close  { background:#64748b; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-size:11pt; font-weight:700; cursor:pointer; margin-left:10px; }
  @media print {
    .print-controls { display:none !important; }
    body { print-color-adjust:exact; -webkit-print-color-adjust:exact; }
    .logo-syrma, .logo-learnlike { display:block !important; }
    .report-header { page-break-inside: avoid; }
    .report-footer { page-break-inside: avoid; margin-top: 20px; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; page-break-after: auto; }
    thead { display: table-header-group; }
    .section-title { page-break-after: avoid; }
    .kpi-grid { page-break-inside: avoid; }
  }
</style>
</head>
<body>

<!-- Print Controls (hidden when printing) -->
<div class="print-controls">
  <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
  <button class="btn-close" onclick="window.close()">✕ Close</button>
</div>

<!-- Report Header -->
<div class="report-header">
  <div class="logo-area">
    <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg"
         class="logo-syrma"
         onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<span style=\'font-size:16pt;font-weight:900;color:#0f172a;\'>SYRMA SGS</span>')"
         alt="Syrma SGS">
    <p style="margin-top:5px;font-size:9pt;color:#64748b;font-weight:600;"><?php echo htmlspecialchars($reportTitle); ?></p>
  </div>
  <div class="meta">
    <div class="date"><?php echo date('d M Y'); ?></div>
    <div class="generated">Generated: <?php echo $generatedAt; ?></div>
    <div class="generated">Ref: OTR-<?php echo strtoupper($type); ?>-<?php echo date('Ymd'); ?></div>
  </div>
</div>

<?php if ($type === 'overview' || $type === 'mis'): ?>
<!-- KPI Cards -->
<div class="kpi-grid">
  <div class="kpi blue">
    <div class="lbl">Total Active Trainees</div>
    <div class="val"><?php echo $totalTrainees; ?></div>
  </div>
  <div class="kpi green">
    <div class="lbl">Training Completion Rate</div>
    <div class="val"><?php echo $compRate; ?>%</div>
  </div>
  <div class="kpi amber">
    <div class="lbl">Average Exam Score</div>
    <div class="val"><?php echo $avgScore; ?>%</div>
  </div>
  <div class="kpi rose">
    <div class="lbl">Total Trainers</div>
    <div class="val"><?php echo $totalTrainers; ?></div>
  </div>
</div>

<!-- Module Performance -->
<?php if (!empty($stats['modules'])): ?>
<div class="section-title">📊 Module Performance (Top <?php echo count($stats['modules']); ?>)</div>
<?php foreach ($stats['modules'] as $m): $pct = round(floatval($m['avg_score']),1); ?>
<div class="bar-row">
  <div class="bar-label"><?php echo htmlspecialchars($m['title']); ?></div>
  <div class="bar-track"><div class="bar-fill" style="width:<?php echo min($pct,100); ?>%"></div></div>
  <div class="bar-pct"><?php echo $pct; ?>%</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Completion Breakdown -->
<div class="section-title">✅ Assignment Completion Breakdown</div>
<div class="stat-legend">
<?php foreach ($stats['completion'] as $status => $count): $clr = $status==='completed'?'#10b981':($status==='in_progress'?'#f59e0b':'#94a3b8'); ?>
  <span><span class="stat-dot" style="background:<?php echo $clr; ?>"></span><?php echo ucwords(str_replace('_',' ',$status)); ?>: <strong><?php echo $count; ?></strong></span>
<?php endforeach; ?>
</div>

<!-- Completion Table -->
<div class="section-title" style="margin-top:16px;">📋 All Assignments</div>
<table>
  <thead><tr><th>Trainee</th><th>Module</th><th>Trainer</th><th>Status</th><th>Completed</th></tr></thead>
  <tbody>
<?php
$rows = $pdo->query("SELECT a.*, u.full_name as tname, tr.full_name as trainer, m.title as mname
  FROM assignments a JOIN users u ON a.trainee_id=u.id JOIN users tr ON a.trainer_id=tr.id
  JOIN training_modules m ON a.module_id=m.id ORDER BY a.assigned_date DESC LIMIT 100")->fetchAll();
foreach ($rows as $r): ?>
  <tr>
    <td class="bold"><?php echo htmlspecialchars($r['tname']); ?></td>
    <td><?php echo htmlspecialchars($r['mname']); ?></td>
    <td><?php echo htmlspecialchars($r['trainer']); ?></td>
    <td><span class="badge <?php echo $r['status']; ?>"><?php echo $r['status']; ?></span></td>
    <td><?php echo $r['completion_date'] ? date('d M Y', strtotime($r['completion_date'])) : '—'; ?></td>
  </tr>
<?php endforeach; if(empty($rows)): ?><tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;">No data found.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php elseif ($type === 'completion'): ?>
<div class="section-title">📋 Training Completion List</div>
<table>
  <thead><tr><th>Trainee</th><th>Module</th><th>Trainer</th><th>Status</th><th>Date Completed</th></tr></thead>
  <tbody>
<?php
$rows = $pdo->query("SELECT a.*, u.full_name as tname, tr.full_name as trainer, m.title as mname
  FROM assignments a JOIN users u ON a.trainee_id=u.id JOIN users tr ON a.trainer_id=tr.id
  JOIN training_modules m ON a.module_id=m.id ORDER BY a.completion_date DESC")->fetchAll();
foreach ($rows as $r): ?>
  <tr>
    <td class="bold"><?php echo htmlspecialchars($r['tname']); ?></td>
    <td><?php echo htmlspecialchars($r['mname']); ?></td>
    <td><?php echo htmlspecialchars($r['trainer']); ?></td>
    <td><span class="badge <?php echo $r['status']; ?>"><?php echo strtoupper($r['status']); ?></span></td>
    <td><?php echo $r['completion_date'] ? date('d M Y', strtotime($r['completion_date'])) : '—'; ?></td>
  </tr>
<?php endforeach; ?>
  </tbody>
</table>

<?php elseif ($type === 'performance'): ?>
<div class="section-title">🎓 Performance Log</div>
<table>
  <thead><tr><th>Trainee</th><th>Exam</th><th>Score</th><th>Date</th><th>Result</th></tr></thead>
  <tbody>
<?php
$rows = $pdo->query("SELECT r.*, u.full_name, e.title as exam_name FROM exam_results r JOIN users u ON r.trainee_id=u.id JOIN exams e ON r.exam_id=e.id ORDER BY r.exam_date DESC")->fetchAll();
foreach ($rows as $r): ?>
  <tr>
    <td class="bold"><?php echo htmlspecialchars($r['full_name']); ?></td>
    <td><?php echo htmlspecialchars($r['exam_name']); ?></td>
    <td style="font-weight:800;"><?php echo $r['score']; ?>%</td>
    <td><?php echo date('d M Y', strtotime($r['exam_date'])); ?></td>
    <td><span class="badge <?php echo $r['status']; ?>"><?php echo strtoupper($r['status']); ?></span></td>
  </tr>
<?php endforeach; if(empty($rows)): ?><tr><td colspan="5" style="text-align:center;color:#94a3b8;">No data.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php elseif ($type === 'feedback'): ?>
<div class="section-title">💬 Feedback Summary</div>
<table>
  <thead><tr><th>Trainee</th><th>Overall</th><th>Skill</th><th>Explanation</th><th>Time</th><th>Comments</th></tr></thead>
  <tbody>
<?php
$rows = $pdo->query("SELECT f.*, u.full_name FROM feedback f JOIN users u ON f.trainee_id=u.id ORDER BY f.submitted_at DESC")->fetchAll();
foreach ($rows as $r): ?>
  <tr>
    <td class="bold"><?php echo htmlspecialchars($r['full_name']); ?></td>
    <td style="font-weight:800;"><?php echo $r['rating_overall']; ?>/5</td>
    <td><?php echo $r['rating_learning_skill']; ?>/5</td>
    <td><?php echo $r['rating_explanation']; ?>/5</td>
    <td><?php echo $r['rating_time']; ?>/5</td>
    <td style="font-size:8.5pt;color:#64748b;"><?php echo htmlspecialchars($r['comments'] ?? ''); ?></td>
  </tr>
<?php endforeach; if(empty($rows)): ?><tr><td colspan="6" style="text-align:center;color:#94a3b8;">No feedback yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php elseif ($type === 'employee'):
  $empId = $_GET['emp_id'] ?? '';
  if (!$empId) { echo '<p style="color:red;padding:20px;">No employee selected.</p>'; }
  else {
    $emp = $pdo->prepare("SELECT * FROM users WHERE employee_id=?"); $emp->execute([$empId]); $emp = $emp->fetch();
    if ($emp):
      $asgn = $pdo->prepare("SELECT a.*,m.title as mname FROM assignments a JOIN training_modules m ON a.module_id=m.id WHERE a.trainee_id=?"); $asgn->execute([$emp['id']]); $asgn=$asgn->fetchAll();
      $exams= $pdo->prepare("SELECT r.*,e.title as ename FROM exam_results r JOIN exams e ON r.exam_id=e.id WHERE r.trainee_id=?"); $exams->execute([$emp['id']]); $exams=$exams->fetchAll();
?>
<div class="section-title">👤 Employee Profile</div>
<table style="margin-bottom:16px;">
  <tbody>
    <tr><td class="bold" style="width:160px;">Full Name</td><td><?php echo htmlspecialchars($emp['full_name']); ?></td><td class="bold" style="width:140px;">Employee ID</td><td><?php echo htmlspecialchars($emp['employee_id']); ?></td></tr>
    <tr><td class="bold">Department</td><td><?php echo htmlspecialchars($emp['department']??'N/A'); ?></td><td class="bold">Role</td><td><?php echo ucfirst($emp['role']); ?></td></tr>
    <tr><td class="bold">Batch No.</td><td><?php echo htmlspecialchars($emp['batch_number']??'N/A'); ?></td><td class="bold">Joining Date</td><td><?php echo ($emp['doj']&&$emp['doj']!='0000-00-00')?date('d M Y',strtotime($emp['doj'])):'N/A'; ?></td></tr>
    <tr><td class="bold">Qualification</td><td><?php echo htmlspecialchars($emp['qualification']??'N/A'); ?></td><td class="bold">Status</td><td><?php echo ucfirst($emp['status']); ?></td></tr>
  </tbody>
</table>

<div class="section-title">📚 Module Assignments</div>
<table>
  <thead><tr><th>Module</th><th>Assigned Date</th><th>Status</th><th>Completed</th></tr></thead>
  <tbody>
    <?php foreach($asgn as $a): ?><tr>
      <td class="bold"><?php echo htmlspecialchars($a['mname']); ?></td>
      <td><?php echo date('d M Y',strtotime($a['assigned_date'])); ?></td>
      <td><span class="badge <?php echo $a['status']; ?>"><?php echo strtoupper($a['status']); ?></span></td>
      <td><?php echo $a['completion_date']?date('d M Y',strtotime($a['completion_date'])):'—'; ?></td>
    </tr><?php endforeach; if(empty($asgn)): ?><tr><td colspan="4" style="text-align:center;color:#94a3b8;">No modules assigned.</td></tr><?php endif; ?>
  </tbody>
</table>

<div class="section-title">🏆 Exam Results</div>
<table>
  <thead><tr><th>Exam</th><th>Date</th><th>Score</th><th>Result</th></tr></thead>
  <tbody>
    <?php foreach($exams as $ex): ?><tr>
      <td class="bold"><?php echo htmlspecialchars($ex['ename']); ?></td>
      <td><?php echo date('d M Y',strtotime($ex['exam_date'])); ?></td>
      <td style="font-weight:800;"><?php echo $ex['score']; ?>%</td>
      <td><span class="badge <?php echo $ex['status']; ?>"><?php echo strtoupper($ex['status']); ?></span></td>
    </tr><?php endforeach; if(empty($exams)): ?><tr><td colspan="4" style="text-align:center;color:#94a3b8;">No exams taken.</td></tr><?php endif; ?>
  </tbody>
</table>

<div class="signatures">
  <div class="sig-line">Signature of Trainer</div>
  <div class="sig-line">Signature of Manager</div>
  <div class="sig-line">Signature of Employee</div>
</div>
<?php endif; } ?>
<?php endif; ?>

<!-- Footer -->
<div class="report-footer">
  <span>Confidential — Internal Use Only &nbsp;|&nbsp; Digital OTR System</span>
  <div class="footer-brand">
    <span>Powered by</span>
    <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg"
         class="logo-learnlike"
         onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<strong style=\'color:#0f172a;\'>Learnlike</strong>')"
         alt="Learnlike">
    <span>&nbsp;|&nbsp; <?php echo $generatedAt; ?></span>
  </div>
</div>

<script>
  // Auto-trigger print dialog after a short delay
  window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 600);
  });
</script>
</body>
</html>
