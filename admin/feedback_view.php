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

if (!$f) die("No feedback found for this trainee.");

// Calculate totals for each rank
$ranks = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
$fields = ['rating_overall', 'rating_learning_skill', 'rating_learning_knowledge', 'rating_learning_attitude', 'rating_explanation', 'rating_improvement', 'rating_time'];
foreach ($fields as $field) {
    if (isset($f[$field])) {
        $ranks[$f[$field]]++;
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
                <td>COMMENT ABOUT THE FACULTY AND METHODOLOGY<br>OF THIS PROGRAMME</td>
                <td colspan="4" style="font-size: 0.85rem; font-style: italic; vertical-align: top; height: 60px;">
                    <?php echo e($f['comments']); ?>
                </td>
            </tr>
            <tr>
                <td style="text-align: center;">5</td>
                <td>THE PROGRAMME HAS IMPROVED MY ABILITY TO PERFORM CURRENT / FUTURE TASKS</td>
                <?php foreach(['A','B','C','D'] as $r): ?>
                    <td class="rank-cell"><?php echo $f['rating_improvement'] == $r ? '<span class="check">&#10003;</span>' : ''; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td style="text-align: center;">6</td>
                <td>TIME MANAGEMENT DURING TRAINING</td>
                <?php foreach(['A','B','C','D'] as $r): ?>
                    <td class="rank-cell"><?php echo $f['rating_time'] == $r ? '<span class="check">&#10003;</span>' : ''; ?></td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>

    <table class="summary-box">
        <tr><td>TOTAL OF RANK "A" - EXCELLENT</td><td><?php echo $ranks['A']; ?></td></tr>
        <tr><td>TOTAL OF RANK "B" - GOOD</td><td><?php echo $ranks['B']; ?></td></tr>
        <tr><td>TOTAL OF RANK "C" - UNDERSTANDABLE</td><td><?php echo $ranks['C']; ?></td></tr>
        <tr><td>TOTAL OF RANK "D" - CAN'T UNDERSTANDABLE</td><td><?php echo $ranks['D']; ?></td></tr>
    </table>

    <div class="trainee-info">
        <div>Trainee Name : <?php echo e($trainee['full_name']); ?></div>
        <div>Signature : <div class="sig-line"></div></div>
    </div>

    <div class="footer">
        <span>SST3001FRM2097/D</span>
    </div>
</div>

</body>
</html>
