<?php
// admin/sdc_form.php
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

// Fetch SDC Stages
$stmt = $pdo->prepare("SELECT * FROM training_stages WHERE assignment_id = ? AND type = 'sdc' ORDER BY certified_date ASC");
$stmt->execute([$assignment_id]);
$sdc_stages = $stmt->fetchAll();

// Fetch Recertification Stages
$stmt = $pdo->prepare("SELECT * FROM training_stages WHERE assignment_id = ? AND type = 'recertification' ORDER BY certified_date ASC");
$stmt->execute([$assignment_id]);
$recert_stages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PRACTICAL TRAINING-SDC | <?php echo e($assignment['trainee_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f4f4f4; }
        .paper { width: 210mm; min-height: 297mm; padding: 15mm; margin: auto; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; position: relative; }
        .header { display: flex; justify-content: flex-start; align-items: center; margin-bottom: 20px; }
        .header img { height: 35px; margin-right: 15px; }
        .title { margin-left: 50px; font-weight: bold; font-size: 1.1rem; border-bottom: 2px solid #000; padding: 2px 10px; }
        
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; font-weight: bold; font-size: 0.95rem; }
        .detail-item { display: flex; gap: 5px; }
        .detail-item span:first-child { width: 60px; }
        .val { border-bottom: 1px dotted #000; flex: 1; font-weight: normal; padding-left: 5px; }

        .training-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .training-table th, .training-table td { border: 1px solid #000; padding: 8px; font-size: 0.9rem; }
        .training-table th { background: #f0f0f0; text-align: center; }
        .sno { width: 40px; text-align: center; }
        .hours { width: 80px; text-align: center; }
        .date { width: 110px; text-align: center; }

        .section-divider { text-align: center; font-weight: bold; margin: 30px 0 15px; border-top: 1px solid #000; padding-top: 20px; text-transform: uppercase; letter-spacing: 1px; }
        
        .signature-row { margin-top: 50px; display: flex; justify-content: space-between; font-weight: bold; font-size: 0.95rem; }
        .sig-block { width: 40%; }
        .sig-line { border-bottom: 1px solid #000; margin-top: 10px; height: 25px; }

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
        <div class="title">PRACTICAL TRAINING-SDC</div>
    </div>

    <div class="details-grid">
        <div class="detail-item"><span>Name</span>: <div class="val"><?php echo e($assignment['trainee_name']); ?></div></div>
        <div class="detail-item"><span>T.No.</span>: <div class="val"><?php echo e($assignment['trainee_eno']); ?></div></div>
    </div>

    <table class="training-table">
        <thead>
            <tr>
                <th class="sno">S.No.</th>
                <th>Stages</th>
                <th class="hours">Man Hours</th>
                <th class="date">Certified Date</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            for($i=1; $i<=15; $i++): 
                $st = $sdc_stages[$i-1] ?? null;
            ?>
            <tr>
                <td class="sno"><?php echo $i; ?></td>
                <td><?php echo $st ? e($st['stage_name']) : ''; ?></td>
                <td class="hours"><?php echo $st ? $st['man_hours'] : ''; ?></td>
                <td class="date"><?php echo $st ? date('d/m/Y', strtotime($st['certified_date'])) : ''; ?></td>
                <td><?php echo $st ? e($st['remarks']) : ''; ?></td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="section-divider">RECERTIFICATION DETAILS</div>

    <table class="training-table">
        <thead>
            <tr>
                <th class="sno">S.No.</th>
                <th>Stages</th>
                <th class="hours">Man Hours</th>
                <th class="date">Recertification Date</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            for($i=1; $i<=5; $i++): 
                $st = $recert_stages[$i-1] ?? null;
            ?>
            <tr>
                <td class="sno"><?php echo $i; ?></td>
                <td><?php echo $st ? e($st['stage_name']) : ''; ?></td>
                <td class="hours"><?php echo $st ? $st['man_hours'] : ''; ?></td>
                <td class="date"><?php echo $st ? date('d/m/Y', strtotime($st['certified_date'])) : ''; ?></td>
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
        <span>Page 4 of 7</span>
    </div>
</div>

</body>
</html>
