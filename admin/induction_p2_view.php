<?php
// admin/induction_p2_view.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin', 'trainer', 'management']);

$trainee_id = $_GET['tid'] ?? null;
if (!$trainee_id) die("Trainee ID required.");

// Fetch Trainee Details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();

// Fetch Progress and Trainer details
$stmt = $pdo->prepare("
    SELECT ic.id, ic.day_number, ic.section_name, ic.topic_name, ic.estimated_hours, ic.estimated_mins, 
           tcp.is_done, tcp.completed_at, u.full_name as trainer_name
    FROM induction_checklist ic
    LEFT JOIN trainee_checklist_progress tcp ON ic.id = tcp.checklist_id AND tcp.trainee_id = ?
    LEFT JOIN users u ON tcp.trainer_id = u.id
    WHERE ic.day_number IN (1, 2)
    ORDER BY ic.id
");
$stmt->execute([$trainee_id]);
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getTopicsBySection($progress, $section) {
    return array_filter($progress, function($p) use ($section) {
        return $p['section_name'] === $section;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Induction Training P2 | <?php echo e($trainee['full_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f4f4f4; }
        .paper { width: 210mm; min-height: 297mm; padding: 15mm; margin: auto; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; position: relative; }
        .header { display: flex; justify-content: flex-start; align-items: center; margin-bottom: 20px; }
        .header img { height: 35px; margin-right: 15px; }
        .title-box { text-align: center; font-weight: bold; font-size: 1.2rem; margin: 10px 0; border-bottom: 2px solid #000; padding-bottom: 5px; text-transform: uppercase; }
        
        .block-header { display: grid; grid-template-columns: 80px 1.5fr 1fr 1.5fr; border: 1px solid #000; margin-top: 20px; }
        .block-header > div { border-right: 1px solid #000; padding: 8px; text-align: center; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; background: #f9f9f9; }
        .block-header > div:last-child { border-right: none; }

        .topics-grid { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #000; border-top: none; }
        .topic-col { padding: 10px; border-right: 1px solid #000; }
        .topic-col:last-child { border-right: none; }
        
        .topic-item { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 0.85rem; }
        .topic-num { width: 20px; text-align: right; }
        .topic-name { flex: 1; }
        .topic-val { width: 60px; text-align: right; border-bottom: 1px dotted #000; }
        .check-box { width: 15px; height: 15px; border: 1px solid #000; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold; }

        .sign-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; font-size: 0.85rem; font-weight: bold; }
        .sig-val { border-bottom: 1px solid #000; min-height: 20px; flex: 1; margin-left: 5px; font-weight: normal; }
        .sig-item { display: flex; align-items: flex-end; margin-bottom: 10px; }

        .footer { font-size: 0.85rem; font-weight: bold; text-align: right; position: absolute; bottom: 30px; right: 40px; }
        
        .no-print { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; }
        .btn { padding: 8px 16px; background: #0B70B7; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9rem; }
        @media print { body { background: white; padding: 0; } .paper { box-shadow: none; width: 100%; border: none; } .no-print { display: none; } }
        
        .day-label { writing-mode: vertical-rl; transform: rotate(180deg); }
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

    <div class="title-box">Induction Training</div>

    <!-- DAY 1 SECTION -->
    <div class="block-header">
        <div>DAY 1</div>
        <div>JOINING FORMALITIES</div>
        <div>MAN HRS : 5 HRS</div>
        <div>FACULTY NAME</div>
    </div>
    <div class="topics-grid" style="grid-template-columns: 1fr;">
        <div class="topic-col" style="padding: 20px 40px;">
            <?php 
            $i = 1;
            foreach (getTopicsBySection($progress, 'JOINING FORMALITIES') as $t): 
            ?>
            <div class="topic-item">
                <span class="topic-num"><?php echo $i++; ?></span>
                <span class="topic-name"><?php echo e($t['topic_name']); ?></span>
                <span class="topic-val"><?php echo $t['estimated_mins']; ?> mins</span>
                <div class="check-box"><?php echo $t['is_done'] ? '&#10003;' : ''; ?></div>
            </div>
            <?php endforeach; ?>
            
            <div class="sign-row" style="margin-top: 30px;">
                <div>
                    <div class="sig-item">Trainee Name: <div class="sig-val"><?php echo e($trainee['full_name']); ?></div></div>
                    <div class="sig-item">Signature: <div class="sig-val"></div></div>
                </div>
                <div>
                    <?php 
                    $day1 = array_filter($progress, function($p){ return $p['day_number'] == 1 && $p['trainer_name']; });
                    $trainer1 = !empty($day1) ? reset($day1)['trainer_name'] : '';
                    ?>
                    <div class="sig-item">Trainer Name: <div class="sig-val"><?php echo e($trainer1); ?></div></div>
                    <div class="sig-item">Signature: <div class="sig-val"></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- DAY 2 BLOCK 1 -->
    <div class="block-header" style="margin-top: 40px;">
        <div>DAY 2</div>
        <div>BASICS- AWARENESS & E-MODULE</div>
        <div>MAN HRS : 5 HRS</div>
        <div>FACULTY NAME</div>
    </div>
    <div class="topics-grid">
        <!-- A. SAFETY -->
        <div class="topic-col">
            <div style="font-weight: bold; margin-bottom: 10px;">A. SAFETY - 2 hrs</div>
            <?php 
            $i = 1;
            foreach (getTopicsBySection($progress, 'A. SAFETY (2 hrs)') as $t): 
            ?>
            <div class="topic-item">
                <span class="topic-num"><?php echo $i++; ?></span>
                <span class="topic-name"><?php echo e($t['topic_name']); ?></span>
                <div class="check-box"><?php echo $t['is_done'] ? '&#10003;' : ''; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- B. BASIC CONCEPTS -->
        <div class="topic-col">
            <div style="font-weight: bold; margin-bottom: 10px;">B. BASIC CONCEPTS - 3 hrs</div>
            <?php 
            $i = 1;
            foreach (getTopicsBySection($progress, 'B. BASIC CONCEPTS (3 hrs)') as $t): 
            ?>
            <div class="topic-item">
                <span class="topic-num"><?php echo $i++; ?></span>
                <span class="topic-name"><?php echo e($t['topic_name']); ?></span>
                <div class="check-box"><?php echo $t['is_done'] ? '&#10003;' : ''; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div style="border: 1px solid #000; border-top: none; padding: 15px;">
        <div class="sign-row">
            <div>
                <div class="sig-item">Trainee Name: <div class="sig-val"><?php echo e($trainee['full_name']); ?></div></div>
                <div class="sig-item">Signature: <div class="sig-val"></div></div>
            </div>
            <div>
                <?php 
                $day2_1 = array_filter($progress, function($p){ return $p['day_number'] == 2 && ($p['section_name'] == 'A. SAFETY (2 hrs)' || $p['section_name'] == 'B. BASIC CONCEPTS (3 hrs)') && $p['trainer_name']; });
                $trainer2 = !empty($day2_1) ? reset($day2_1)['trainer_name'] : '';
                ?>
                <div class="sig-item">Trainer Name: <div class="sig-val"><?php echo e($trainer2); ?></div></div>
                <div class="sig-item">Signature: <div class="sig-val"></div></div>
            </div>
        </div>
    </div>

    <div class="footer">Page 2 of 7</div>
</div>

</body>
</html>
