<?php
// admin/feedback_view.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin', 'trainer', 'management']);

$trainee_id = $_GET['tid'] ?? null;
if (!$trainee_id) die("Trainee ID required.");

// Fetch Trainee Details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();

// Fetch Latest Feedback
$stmt = $pdo->prepare("SELECT * FROM feedback WHERE trainee_id = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->execute([$trainee_id]);
$f = $stmt->fetch();

$no_data = !$f;

// Calculate totals for each rank if data exists
$ranks = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
if ($f) {
    $fields = ['rating_overall', 'rating_learning_skill', 'rating_learning_knowledge', 'rating_learning_attitude', 'rating_explanation', 'rating_improvement', 'rating_time'];
    foreach ($fields as $field) {
        if (isset($f[$field]) && isset($ranks[$f[$field]])) {
            $ranks[$f[$field]]++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback | <?php echo e($trainee['full_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f4f4f4; }
        .paper { width: 210mm; min-height: 297mm; padding: 20mm; margin: auto; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; position: relative; }
        .header { display: flex; justify-content: flex-start; align-items: center; margin-bottom: 20px; }
        .header img { height: 35px; margin-right: 15px; }
        .form-title { text-align: center; font-weight: bold; font-size: 1.2rem; margin: 30px 0; border-bottom: 2px solid #000; padding-bottom: 10px; }
        
        .feedback-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .feedback-table th, .feedback-table td { border: 1px solid #000; padding: 10px; font-size: 0.95rem; }
        .feedback-table th { background: #f0f0f0; text-align: left; }
        .rank-cell { text-align: center; width: 40px; font-weight: bold; }
        .check { font-family: DejaVu Sans, sans-serif; font-size: 1.2rem; }

        .summary-box { width: 60%; margin-top: 40px; border-collapse: collapse; }
        .summary-box td { border: 1px solid #000; padding: 8px 15px; font-weight: bold; }
        .summary-box td:first-child { width: 300px; }
        
        .trainee-info { margin-top: 60px; font-weight: bold; }
        .trainee-info div { margin-bottom: 15px; }
        .sig-line { border-bottom: 1px solid #000; display: inline-block; width: 200px; margin-left: 10px; }

        .footer { position: absolute; bottom: 40px; left: 50px; font-size: 0.85rem; font-weight: bold; }
        
        .no-print { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; }
        .btn { padding: 10px 20px; background: #0B70B7; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        @media print { body { background: white; padding: 0; } .paper { box-shadow: none; width: 100%; border: none; } .no-print { display: none; } }
    </style>
</head>
<body>

<div class="no-print">
    <a href="javascript:window.print()" class="btn">Print</a>
    <a href="induction_records.php?trainee_id=<?php echo $trainee_id; ?>" class="btn" style="background: #4A5568;">Back</a>
</div>

<div class="paper">
    <?php if ($no_data): ?>
        <div class="header" style="margin-bottom: 40px; border-bottom: 2px solid #F1F5F9; padding-bottom: 20px;">
            <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" alt="Syrma SGS">
            <div style="margin-left: auto; text-align: right;">
                <div style="font-weight: 800; color: #0F172A; font-size: 1.1rem;">Feedback Summary</div>
                <div style="color: #64748B; font-weight: 600; font-size: 0.85rem;"><?php echo date('d M Y'); ?></div>
            </div>
        </div>
        
        <div style="text-align: center; padding: 100px 20px;">
            <div style="width: 80px; height: 80px; background: #FEF3C7; color: #D97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 25px;">
                <i class="fa-solid fa-file-circle-exclamation"></i>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: #0F172A; margin-bottom: 10px;">No Feedback Records Found</h2>
            <p style="color: #64748B; font-weight: 600; font-size: 1rem; margin-bottom: 40px;">
                We couldn't find any feedback submissions for <strong><?php echo e($trainee['full_name']); ?></strong> (ID: <?php echo e($trainee['employee_id']); ?>) at this time.
            </p>
            <a href="induction_records.php?trainee_id=<?php echo $trainee_id; ?>" style="background: #0F172A; color: white; padding: 12px 30px; border-radius: 12px; font-weight: 800; text-decoration: none; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2); display: inline-flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </a>
        </div>
    <?php else: ?>
    <div class="header">
        <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" alt="Syrma SGS">
    </div>

    <div class="form-title">FEEDBACK</div>

    <table class="feedback-table">
        <thead>
            <tr>
                <th style="width: 50px;">S.No.</th>
                <th>Feedback Questions</th>
                <th class="rank-cell">A</th>
                <th class="rank-cell">B</th>
                <th class="rank-cell">C</th>
                <th class="rank-cell">D</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td>OVER ALL RATING</td>
                <?php foreach(['A','B','C','D'] as $r): ?>
                    <td class="rank-cell"><?php echo $f['rating_overall'] == $r ? '<span class="check">&#10003;</span>' : ''; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td style="text-align: center;">2</td>
                <td colspan="5"><strong>MY LEARNING FROM THIS PROGRAMME</strong><br><small>THE LEARNINGS OF THE PROGRAMME HAS ENRICHED ME IN THE FOLLOWING</small></td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-left: 30px;">A. SKILL DEVELOPMENT</td>
                <?php foreach(['A','B','C','D'] as $r): ?>
                    <td class="rank-cell"><?php echo $f['rating_learning_skill'] == $r ? '<span class="check">&#10003;</span>' : ''; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td></td>
                <td style="padding-left: 30px;">B. KNOWLEDGE DEVELOPMENT</td>
                <?php foreach(['A','B','C','D'] as $r): ?>
                    <td class="rank-cell"><?php echo $f['rating_learning_knowledge'] == $r ? '<span class="check">&#10003;</span>' : ''; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td></td>
                <td style="padding-left: 30px;">C. ATTITUDE CHANGE</td>
                <?php foreach(['A','B','C','D'] as $r): ?>
                    <td class="rank-cell"><?php echo $f['rating_learning_attitude'] == $r ? '<span class="check">&#10003;</span>' : ''; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td style="text-align: center;">3</td>
                <td>FACULTIES EXPLANATION ON QUERRIES</td>
                <?php foreach(['A','B','C','D'] as $r): ?>
                    <td class="rank-cell"><?php echo $f['rating_explanation'] == $r ? '<span class="check">&#10003;</span>' : ''; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td style="text-align: center;">4</td>
                <td>PROGRAMME IMPROVE MYSELF & TECHNICAL SKILL</td>
                <?php foreach(['A','B','C','D'] as $r): ?>
                    <td class="rank-cell"><?php echo $f['rating_improvement'] == $r ? '<span class="check">&#10003;</span>' : ''; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td style="text-align: center;">5</td>
                <td>PROGRAMME DURATION / TIME WAS SUFFICIENT</td>
                <?php foreach(['A','B','C','D'] as $r): ?>
                    <td class="rank-cell"><?php echo $f['rating_time'] == $r ? '<span class="check">&#10003;</span>' : ''; ?></td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>

    <div style="font-weight: bold; margin-bottom: 20px;">6. Any further comments / suggestion on the outcome based on training :</div>
    <div style="border-bottom: 1px solid #000; min-height: 50px; margin-bottom: 40px;"><?php echo e($f['comments'] ?? ''); ?></div>

    <table class="summary-box">
        <tr>
            <td>A - Excellent (Rank - 1)</td>
            <td style="text-align: center; width: 60px;"><?php echo $ranks['A']; ?></td>
        </tr>
        <tr>
            <td>B - Good (Rank - 2)</td>
            <td style="text-align: center;"><?php echo $ranks['B']; ?></td>
        </tr>
        <tr>
            <td>C - Can be better (Rank - 3)</td>
            <td style="text-align: center;"><?php echo $ranks['C']; ?></td>
        </tr>
        <tr>
            <td>D - Needs improvement (Rank - 4)</td>
            <td style="text-align: center;"><?php echo $ranks['D']; ?></td>
        </tr>
    </table>

    <div class="trainee-info">
        <div>Trainee Name : <?php echo e($trainee['full_name']); ?></div>
        <div>Signature : <div class="sig-line"></div></div>
    </div>

    <div class="footer">
        <span>SST3001FRM2097/D</span> | <span>Page 8 of 8</span>
    </div>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Premium Font Support */
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
</style>
</body>
</html>
