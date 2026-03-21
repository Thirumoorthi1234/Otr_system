<?php
// admin/training_record.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Auth check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'trainer', 'management'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$assignment_id = $_GET['id'] ?? null;
$trainee_id_param = $_GET['tid'] ?? null;
$data = null;

if ($assignment_id) {
    $stmt = $pdo->prepare("
        SELECT a.*, t.full_name as trainee_name, t.employee_id as trainee_eno, t.department as trainee_dept, t.photo_path,
               tr.full_name as trainer_name, m.title as module_name
        FROM assignments a
        JOIN users t ON a.trainee_id = t.id
        JOIN users tr ON a.trainer_id = tr.id
        JOIN training_modules m ON a.module_id = m.id
        WHERE a.id = ?
    ");
    $stmt->execute([$assignment_id]);
    $data = $stmt->fetch();
} elseif ($trainee_id_param) {
    $stmt = $pdo->prepare("SELECT full_name as trainee_name, employee_id as trainee_eno, department as trainee_dept, id as trainee_id, photo_path FROM users WHERE id = ?");
    $stmt->execute([$trainee_id_param]);
    $data = $stmt->fetch();
    $data['assigned_date'] = date('Y-m-d');
    $data['trainer_name'] = "System Administrator";
    $data['module_name'] = "General Induction";
}

if (!$data) die("Record not found.");

// Fetch Performance Stats
$stmt = $pdo->prepare("SELECT SUM(man_hours) FROM training_stages WHERE assignment_id = ?");
$stmt->execute([$assignment_id]);
$total_hours = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trainee_checklist_progress WHERE trainee_id = ?");
$stmt->execute([$data['trainee_id']]);
$indu_done = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM induction_checklist");
$stmt->execute();
$indu_total = $stmt->fetchColumn();
$indu_percent = $indu_total > 0 ? round(($indu_done / $indu_total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Training Report | <?php echo e($data['trainee_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0B70B7;
            --success: #38A169;
            --bg: #F5F7FA;
            --card: #ffffff;
            --text: #121212;
            --muted: #718096;
            --border: #E2E8F0;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            margin: 0; padding: 40px; 
            line-height: 1.5;
        }

        .report-page {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--card);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            position: relative;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--bg);
            padding-bottom: 30px;
            margin-bottom: 30px;
        }

        .company-brand { display: flex; align-items: center; gap: 15px; }
        .logo { width: 50px; height: 50px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 1.2rem; }
        .brand-text h2 { margin: 0; font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px; }
        .brand-text p { margin: 0; font-size: 0.8rem; color: var(--muted); text-transform: uppercase; font-weight: 600; }

        .report-title { text-align: right; }
        .report-title h1 { margin: 0; font-size: 1.8rem; color: var(--primary); font-weight: 800; }
        .report-title span { color: var(--muted); font-size: 0.9rem; font-weight: 500; }

        .summary-banner {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }

        .stat-val { display: block; font-size: 1.4rem; font-weight: 800; color: var(--text); }
        .stat-lbl { font-size: 0.75rem; color: var(--muted); font-weight: 600; text-transform: uppercase; margin-top: 5px; }

        .profile-section {
            display: flex;
            gap: 30px;
            background: #f8fafc;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 40px;
            border: 1px solid var(--border);
        }

        .photo-box { width: 100px; height: 100px; border-radius: 12px; overflow: hidden; background: #E2E8F0; flex-shrink: 0; }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; }

        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px 40px; flex: 1; }
        .info-item { display: flex; justify-content: space-between; font-size: 0.9rem; border-bottom: 1px solid #edf2f7; padding-bottom: 5px; }
        .info-label { color: var(--muted); font-weight: 600; }
        .info-value { color: var(--text); font-weight: 700; }

        .section-header { 
            display: flex; align-items: center; gap: 10px; 
            margin: 40px 0 20px; font-size: 1.1rem; font-weight: 800; 
            color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;
        }
        .section-header::after { content: ""; flex: 1; height: 1px; background: var(--border); }

        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 30px; }
        th { background: #f8fafc; color: var(--muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; padding: 12px 15px; text-align: left; border-bottom: 2px solid var(--border); }
        td { padding: 15px; font-size: 0.9rem; border-bottom: 1px solid var(--border); }
        tr:last-child td { border-bottom: none; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-info { background: #EBF8FF; color: #2B6CB0; }
        .badge-success { background: #F0FFF4; color: #2F855A; }

        .signature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-top: 60px;
        }

        .sig-box { text-align: center; }
        .sig-line { border-top: 2px solid var(--text); margin-bottom: 10px; }
        .sig-label { font-size: 0.8rem; font-weight: 700; color: var(--muted); text-transform: uppercase; }

        .no-print { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px; }
        .btn { padding: 12px 24px; background: var(--primary); color: white; border: none; border-radius: 10px; cursor: pointer; text-decoration: none; font-weight: 700; box-shadow: 0 10px 15px rgba(11, 112, 183, 0.2); font-size: 0.9rem; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }

        @media print {
            body { background: white; padding: 0; }
            .report-page { box-shadow: none; border: none; padding: 0; width: 100% !important; max-width: 100% !important; }
            .no-print { display: none; }
            .section-header { break-inside: avoid-page; }
            table { break-inside: auto; }
            tr { break-inside: avoid; break-after: auto; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="javascript:window.print()" class="btn"><i class="fas fa-print"></i></a>
    <?php if ($assignment_id): ?>
        <a href="<?php echo ($_SESSION['role'] == 'admin') ? 'training_hub.php?id='.$assignment_id : '../trainer/progress.php?assignment_id='.$assignment_id; ?>" class="btn" style="background:#555"><i class="fas fa-arrow-left"></i> Back to Hub</a>
    <?php else: ?>
        <a href="induction_records.php" class="btn" style="background:#555"><i class="fas fa-arrow-left"></i> Back to Records</a>
    <?php endif; ?>
</div>

<div class="report-page">
    <div class="header">
        <div class="company-brand">
            <div style="display: flex; gap: 15px; align-items: center;">
                <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" style="height: 50px;" alt="Syrma SGS">
                <div style="width: 2px; height: 35px; background: #E2E8F0;"></div>
                <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo copy.svg" style="height: 40px;" alt="Learnlike">
            </div>
        </div>
        <div class="report-title">
            <h1>Training Performance Record</h1>
            <span>Generated on <?php echo date('d M, Y'); ?></span>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="summary-banner">
        <div class="stat-card">
            <span class="stat-val"><?php echo $total_hours; ?>.0</span>
            <span class="stat-lbl">Practical Hrs</span>
        </div>
        <div class="stat-card">
            <span class="stat-val"><?php echo $indu_percent; ?>%</span>
            <span class="stat-lbl">Induction Completion</span>
        </div>
        <div class="stat-card">
            <span class="stat-val"><?php echo e($data['status'] == 'completed' ? 'Certified' : 'In Progress'); ?></span>
            <span class="stat-lbl">Training Status</span>
        </div>
        <div class="stat-card">
            <span class="stat-val"><?php echo date('M Y', strtotime($data['assigned_date'])); ?></span>
            <span class="stat-lbl">Onboarding</span>
        </div>
    </div>

    <!-- Profile Section -->
    <div class="profile-section">
        <div class="photo-box">
            <?php if (!empty($data['photo_path'])): ?>
                <img src="<?php echo BASE_URL . $data['photo_path']; ?>">
            <?php else: ?>
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #cbd5e0;"><i class="fas fa-user" style="font-size: 2.5rem;"></i></div>
            <?php endif; ?>
        </div>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Full Name</span><span class="info-value"><?php echo e($data['trainee_name']); ?></span></div>
            <div class="info-item"><span class="info-label">Employee ID</span><span class="info-value"><?php echo e($data['trainee_eno']); ?></span></div>
            <div class="info-item"><span class="info-label">Department</span><span class="info-value"><?php echo e($data['trainee_dept']); ?></span></div>
            <div class="info-item"><span class="info-label">Primary Module</span><span class="info-value"><?php echo e($data['module_name']); ?></span></div>
            <div class="info-item"><span class="info-label">Certified By</span><span class="info-value"><?php echo e($data['trainer_name']); ?></span></div>
            <div class="info-item"><span class="info-label">Report ID</span><span class="info-value">TR-<?php echo str_pad($data['trainee_id'], 5, '0', STR_PAD_LEFT); ?></span></div>
        </div>
    </div>

    <!-- Practical Training -->
    <div class="section-header">Practical & Shop Floor Training</div>
    <table>
        <thead>
            <tr>
                <th style="width: 150px;">Certification Date</th>
                <th>Training Milestone / Stage</th>
                <th style="width: 100px;">Duration</th>
                <th>Assessment Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM training_stages WHERE assignment_id = ? ORDER BY certified_date ASC");
            $stmt->execute([$assignment_id]);
            $stages = $stmt->fetchAll();
            foreach ($stages as $st):
            ?>
            <tr>
                <td><strong><?php echo date('d M, Y', strtotime($st['certified_date'])); ?></strong></td>
                <td><?php echo e($st['stage_name']); ?></td>
                <td><span class="badge badge-info"><?php echo e($st['man_hours']); ?> Hours</span></td>
                <td style="color: var(--muted); font-size: 0.85rem;"><?php echo e($st['remarks'] ?: 'Competency achieved as per standards.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($stages)): ?>
                <tr><td colspan="4" style="text-align: center; color: var(--muted); padding: 40px;">No practical stages have been certified yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Induction Summary -->
    <div class="section-header">Induction Process Summary</div>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <?php
        $stmt = $pdo->prepare("SELECT day_number, COUNT(*) as total FROM induction_checklist GROUP BY day_number");
        $stmt->execute();
        $totals = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmt = $pdo->prepare("SELECT ic.day_number, COUNT(*) as done 
                              FROM trainee_checklist_progress p 
                              JOIN induction_checklist ic ON p.checklist_id = ic.id 
                              WHERE p.trainee_id = ? GROUP BY ic.day_number");
        $stmt->execute([$data['trainee_id']]);
        $done = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        for ($d=1; $d<=3; $d++):
            $d_total = $totals[$d] ?? 0;
            $d_done = $done[$d] ?? 0;
            $d_pct = $d_total > 0 ? round(($d_done / $d_total) * 100) : 0;
        ?>
        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom:10px;">
                <span style="font-weight: 800; font-size: 0.8rem; color: var(--primary);">DAY <?php echo $d; ?> TOPICS</span>
                <span class="badge <?php echo $d_pct == 100 ? 'badge-success' : 'badge-info'; ?>"><?php echo $d_pct; ?>%</span>
            </div>
            <div style="height: 6px; background: #E2E8F0; border-radius: 10px; overflow: hidden;">
                <div style="width: <?php echo $d_pct; ?>%; height: 100%; background: <?php echo $d_pct == 100 ? 'var(--success)' : 'var(--primary)'; ?>;"></div>
            </div>
            <p style="margin: 10px 0 0; font-size: 0.75rem; color: var(--muted); font-weight: 600;"><?php echo $d_done; ?> of <?php echo $d_total; ?> Modules Validated</p>
        </div>
        <?php endfor; ?>
    </div>

    <!-- Final Authorization -->
    <div class="signature-grid">
        <div class="sig-box">
            <div class="sig-line"></div>
            <span class="sig-label">Trainee Signature</span>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <span class="sig-label">Certified Instructor</span>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <span class="sig-label">Training Coordinator</span>
        </div>
    </div>

    <div style="margin-top: 60px; text-align: center; color: var(--muted); font-size: 0.75rem; font-weight: 500;">
        This is a digitally generated performance record from the SYRMA SGS Learning Management System.<br>
        Validation ID: <?php echo hash('crc32', $data['trainee_eno'] . $data['trainee_id']); ?>
    </div>
</div>

</body>
</html>
