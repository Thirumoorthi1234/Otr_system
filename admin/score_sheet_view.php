<?php
// admin/score_sheet_view.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin', 'trainer', 'management']);

$trainee_id = $_GET['tid'] ?? null;
if (!$trainee_id) die("Trainee ID required.");

// Fetch Trainee Details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();

// Fetch Scores
$stmt = $pdo->prepare("SELECT s.*, u.full_name as trainer_name FROM induction_scores s LEFT JOIN users u ON s.trainer_id = u.id WHERE s.trainee_id = ?");
$stmt->execute([$trainee_id]);
$scores = $stmt->fetch();

if (!$scores) {
    $scores = ['topic_1_score' => '', 'topic_2_score' => '', 'topic_3_score' => '', 'topic_4_score' => '', 'topic_5_score' => '', 'topic_6_score' => '', 'trainer_name' => ''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Score Sheet | <?php echo e($trainee['full_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f4f4f4; }
        .paper { max-width: 210mm; width: 100%; min-height: 297mm; padding: 20mm; margin: auto; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; position: relative; }
        .header { display: flex; justify-content: flex-start; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 20px; }
        .header img { height: 35px; }
        .title-box { border: 2px solid #000; padding: 5px 25px; border-radius: 15px; display: inline-block; }
        .title-box h1 { margin: 0; font-size: 1.4rem; text-transform: uppercase; }
        
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin: 40px 0; }
        .detail-item { display: flex; gap: 10px; margin-bottom: 15px; font-weight: bold; align-items: flex-end; }
        .detail-item span:first-child { width: 80px; flex-shrink: 0; }
        .detail-item .val { border-bottom: 1px dotted #000; flex: 1; min-width: 0; font-weight: normal; word-break: break-word; }

        .scores-table { width: 80%; margin: 40px auto; border-collapse: collapse; }
        .scores-table th, .scores-table td { border: 2px solid #000; padding: 12px; font-size: 1.1rem; }
        .scores-table th { background: #f0f0f0; font-weight: bold; }
        .scores-table td:first-child { width: 60px; text-align: center; }
        .scores-table td:last-child { width: 120px; text-align: center; font-weight: bold; }
        
        .signature-section { margin-top: 80px; }
        .sig-item { display: flex; gap: 15px; align-items: flex-end; margin-bottom: 20px; font-weight: bold; flex-wrap: wrap; }
        .sig-line { border-bottom: 1px solid #000; flex: 1; min-width: 200px; max-width: 250px; min-height: 25px; }

        .footer { font-size: 0.85rem; font-weight: bold; margin-top: 60px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        
        .no-print { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 100; }
        .btn { padding: 10px 20px; background: #0B70B7; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }

        /* Responsive Styles for Screens */
        @media screen and (max-width: 768px) {
            body { padding: 15px; }
            .paper { padding: 15px; min-height: auto; }
            .header { justify-content: center; text-align: center; }
            .details-grid { grid-template-columns: 1fr; gap: 10px; margin: 25px 0; }
            .scores-table { width: 100%; margin: 25px 0; }
            .scores-table th, .scores-table td { padding: 8px; font-size: 0.95rem; }
            .signature-section { margin-top: 40px; }
            .no-print { top: auto; bottom: 20px; right: 20px; flex-direction: column; }
        }

        /* Print Override */
        @media print { 
            body { background: white; padding: 0; } 
            .paper { box-shadow: none; width: 100%; max-width: 100%; padding: 20mm; margin: 0; } 
            .header { justify-content: flex-start; text-align: left; }
            .title-box { margin-left: 50px; }
            .details-grid { grid-template-columns: 1fr 1fr; gap: 40px; margin: 40px 0; }
            .scores-table { width: 80%; margin: 40px auto; }
            .scores-table th, .scores-table td { font-size: 1.1rem; padding: 12px; }
            .no-print { display: none; } 
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="javascript:window.print()" class="btn">Print</a>
    <a href="score_sheet_entry.php?tid=<?php echo $trainee_id; ?>" class="btn" style="background: #4A5568;">Back</a>
</div>

<div class="paper">
    <div class="header">
        <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" alt="Syrma SGS">
        <div class="title-box">
            <h1>Score Sheet</h1>
        </div>
    </div>

    <div class="details-grid">
        <div>
            <div class="detail-item"><span>Name</span>: <div class="val"><?php echo e($trainee['full_name']); ?></div></div>
            <div class="detail-item"><span>T.No.</span>: <div class="val"><?php echo e($trainee['employee_id']); ?></div></div>
        </div>
        <div>
            <div class="detail-item"><span>Dept</span>: <div class="val"><?php echo e($trainee['department']); ?></div></div>
            <div class="detail-item"><span>Date</span>: <div class="val"><?php echo date('d/m/Y'); ?></div></div>
        </div>
    </div>

    <table class="scores-table">
        <thead>
            <tr>
                <th>S.No.</th>
                <th>Test Topics</th>
                <th>Scores (%)</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>1</td><td>About syrma - Oral</td><td><?php echo $scores['topic_1_score']; ?></td></tr>
            <tr><td>2</td><td>Safety - Written Test</td><td><?php echo $scores['topic_2_score']; ?></td></tr>
            <tr><td>3</td><td>PPE - Written Test</td><td><?php echo $scores['topic_3_score']; ?></td></tr>
            <tr><td>4</td><td>5S - Written Test</td><td><?php echo $scores['topic_4_score']; ?></td></tr>
            <tr><td>5</td><td>ESD - Written Test</td><td><?php echo $scores['topic_5_score']; ?></td></tr>
            <tr><td>6</td><td>E - Module - Written Test</td><td><?php echo $scores['topic_6_score']; ?></td></tr>
        </tbody>
    </table>

    <div class="signature-section">
        <div class="sig-item">Trainer Name : <div class="sig-line" style="border:none;"><?php echo e($scores['trainer_name']); ?></div></div>
        <div class="sig-item">Signature : <div class="sig-line"></div></div>
    </div>

    <div class="footer">
        <span>Page 7 of 8</span>
        <span>SST3001FRM2097/D</span>
    </div>
</div>

</body>
</html>
