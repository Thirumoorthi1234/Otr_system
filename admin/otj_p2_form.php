<?php
// admin/otj_p2_form.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin', 'trainer', 'management']);

$assignment_id = $_GET['id'] ?? null;
if (!$assignment_id) die("Assignment ID required.");

// Fetch Assignment Details
$stmt = $pdo->prepare("
    SELECT a.*, t.full_name as trainee_name, t.employee_id as trainee_eno, t.department as trainee_dept
    FROM assignments a
    JOIN users t ON a.trainee_id = t.id
    WHERE a.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

if (!$assignment) die("Assignment not found.");

// Fetch OTJ Stages (21-40)
$stmt = $pdo->prepare("SELECT s.*, u.full_name as trainer_name FROM training_stages s LEFT JOIN users u ON s.trainer_id = u.id WHERE s.assignment_id = ? AND s.type = 'otj' ORDER BY s.certified_date ASC LIMIT 20 OFFSET 20");
$stmt->execute([$assignment_id]);
$otj_stages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTJ TRAINING PAGE 2 | <?php echo e($assignment['trainee_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f4f4f4; }
        .paper { width: 210mm; min-height: 297mm; padding: 15mm; margin: auto; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; position: relative; }
        .header { display: flex; justify-content: flex-start; align-items: center; margin-bottom: 20px; }
        .header img { height: 35px; margin-right: 15px; }
        .title { margin-left: 50px; font-weight: bold; font-size: 1.1rem; border-bottom: 2px solid #000; padding: 2px 10px; text-transform: uppercase; }
        
        .details-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; margin: 20px 0; font-weight: bold; font-size: 0.95rem; }
        .detail-item { display: flex; gap: 5px; margin-bottom: 10px; }
        .detail-item span:first-child { width: 60px; }
        .val { border-bottom: 1px dotted #000; flex: 1; font-weight: normal; padding-left: 5px; }

        .training-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .training-table th, .training-table td { border: 1px solid #000; padding: 10px; font-size: 0.9rem; }
        .training-table th { background: #f0f0f0; text-align: center; }
        .sno { width: 50px; text-align: center; }
        .hours { width: 100px; text-align: center; }
        .certified { width: 180px; text-align: center; }

        .signature-row { margin-top: 80px; display: flex; justify-content: space-between; font-weight: bold; font-size: 0.95rem; }
        .sig-block { width: 45%; }
        .sig-line { border-bottom: 1px solid #000; margin-top: 15px; height: 30px; }

        .footer { font-size: 0.85rem; font-weight: bold; margin-top: 60px; display: flex; justify-content: space-between; position: absolute; bottom: 30px; width: calc(100% - 30mm); }
        
        .no-print { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; }
        .btn { padding: 8px 16px; background: #0B70B7; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9rem; }
        @media print { body { background: white; padding: 0; } .paper { box-shadow: none; width: 100%; border: none; } .no-print { display: none; } }
    </style>
</head>
<body>

<div class="no-print">
    <a href="javascript:window.print()" class="btn">Print</a>
    <a href="training_hub.php?id=<?php echo $assignment_id; ?>" class="btn" style="background: #4A5568;">Back</a>
</div>

<div class="paper">
    <div class="header">
        <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" alt="Syrma SGS">
        <div class="title">ON THE JOB TRAINING - SHOP FLOOR (PAGE 2)</div>
    </div>

    <div class="details-grid">
        <div>
            <div class="detail-item"><span>Name</span>: <div class="val"><?php echo e($assignment['trainee_name']); ?></div></div>
            <div class="detail-item"><span>T.No.</span>: <div class="val"><?php echo e($assignment['trainee_eno']); ?></div></div>
        </div>
        <div>
            <div class="detail-item"><span>Dept</span>: <div class="val"><?php echo e($assignment['trainee_dept']); ?></div></div>
            <div class="detail-item"><span>Date</span>: <div class="val"><?php echo date('d/m/Y'); ?></div></div>
        </div>
    </div>

    <table class="training-table">
        <thead>
            <tr>
                <th class="sno">S.No.</th>
                <th>Stages</th>
                <th class="hours">Man Hours</th>
                <th class="certified">Certified by</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            for($i=21; $i<=40; $i++): 
                $st = $otj_stages[$i-21] ?? null;
            ?>
            <tr>
                <td class="sno"><?php echo $i; ?></td>
                <td><?php echo $st ? e($st['stage_name']) : ''; ?></td>
                <td class="hours"><?php echo $st ? $st['man_hours'] : ''; ?></td>
                <td class="certified" style="font-size: 0.8rem;"><?php echo $st ? e($st['trainer_name']) . ' (' . date('d/m/y', strtotime($st['certified_date'])) . ')' : ''; ?></td>
                <td><?php echo $st ? e($st['remarks']) : ''; ?></td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="signature-row">
        <div class="sig-block">Trainee Name: <?php echo e($assignment['trainee_name']); ?><div class="sig-line">Signature:</div></div>
        <div class="sig-block">Trainer Name: <div class="sig-line">Signature:</div></div>
    </div>

    <div class="footer">
        <span>Page 6 of 8</span>
    </div>
</div>

</body>
</html>
