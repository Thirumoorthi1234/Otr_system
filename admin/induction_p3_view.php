<?php
// admin/induction_p3_view.php
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
    WHERE ic.day_number IN (2, 3) 
    AND ic.section_name NOT IN ('A. SAFETY (2 hrs)', 'B. BASIC CONCEPTS (3 hrs)')
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
    <title>Induction Training P3 | <?php echo e($trainee['full_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f4f4f4; }
        .paper { width: 210mm; min-height: 297mm; padding: 15mm; margin: auto; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; position: relative; }
        .header { display: flex; justify-content: flex-start; align-items: center; margin-bottom: 10px; }
        .header img { height: 35px; margin-right: 15px; }
        .title-box { text-align: center; font-weight: bold; font-size: 1.2rem; margin: 5px 0; border-bottom: 2px solid #000; padding-bottom: 5px; text-transform: uppercase; }
        
        .block-header { display: grid; grid-template-columns: 80px 1.5fr 1fr 1.5fr; border: 1px solid #000; margin-top: 15px; }
        .block-header > div { border-right: 1px solid #000; padding: 6px; text-align: center; font-weight: bold; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; background: #f9f9f9; }
        .block-header > div:last-child { border-right: none; }

        .topics-grid { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #000; border-top: none; }
        .topic-col { padding: 8px; border-right: 1px solid #000; }
        .topic-col:last-child { border-right: none; }
        
        .topic-item { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; font-size: 0.8rem; }
        .topic-num { width: 18px; text-align: right; }
        .topic-name { flex: 1; }
        .check-box { width: 14px; height: 14px; border: 1px solid #000; display: inline-flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: bold; }

        .sign-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px; font-size: 0.8rem; font-weight: bold; }
        .sig-val { border-bottom: 1px solid #000; min-height: 18px; flex: 1; margin-left: 5px; font-weight: normal; }
        .sig-item { display: flex; align-items: flex-end; margin-bottom: 8px; }

        .footer { font-size: 0.85rem; font-weight: bold; text-align: right; position: absolute; bottom: 30px; right: 40px; }
        
        .no-print { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; }
        .btn { padding: 8px 16px; background: #0B70B7; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.9rem; }
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

    <div class="title-box">Induction Training</div>

    <!-- DAY 2 BLOCK 2 -->
    <div class="block-header">
        <div>DAY2</div>
        <div>BASICS - AWARENESS, E-MODULE</div>
        <div>MAN HRS : 3 HRS</div>
        <div>FACULTY NAME</div>
    </div>
    <div class="topics-grid">
        <!-- C. ESD TRAINING -->
        <div class="topic-col">
            <div style="font-weight: bold; margin-bottom: 5px;">C. ESD TRAINING - 2 hrs</div>
            <?php 
            $i = 1;
            foreach (getTopicsBySection($progress, 'C. ESD TRAINING (2 hrs)') as $t): 
            ?>
            <div class="topic-item">
                <span class="topic-num"><?php echo $i++; ?></span>
                <span class="topic-name"><?php echo e($t['topic_name']); ?></span>
                <div class="check-box"><?php echo $t['is_done'] ? '&#10003;' : ''; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- D. FIRST AID TRAINING -->
        <div class="topic-col">
            <div style="font-weight: bold; margin-bottom: 5px;">D. FIRST AID TRAINING - 1 hr</div>
            <?php 
            $i = 1;
            foreach (getTopicsBySection($progress, 'D. FIRST AID TRAINING (1 hr)') as $t): 
            ?>
            <div class="topic-item">
                <span class="topic-num"><?php echo $i++; ?></span>
                <span class="topic-name"><?php echo e($t['topic_name']); ?></span>
                <div class="check-box"><?php echo $t['is_done'] ? '&#10003;' : ''; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div style="border: 1px solid #000; border-top: none; padding: 10px;">
        <div class="sign-row">
            <div>
                <div class="sig-item">Trainee Name: <div class="sig-val"><?php echo e($trainee['full_name']); ?></div></div>
                <div class="sig-item">Signature: <div class="sig-val"></div></div>
            </div>
            <div>
                <?php 
                $day2_2 = array_filter($progress, function($p){ return $p['day_number'] == 2 && ($p['section_name'] == 'C. ESD TRAINING (2 hrs)' || $p['section_name'] == 'D. FIRST AID TRAINING (1 hr)') && $p['trainer_name']; });
                $trainer2 = !empty($day2_2) ? reset($day2_2)['trainer_name'] : '';
                ?>
                <div class="sig-item">Trainer Name: <div class="sig-val"><?php echo e($trainer2); ?></div></div>
                <div class="sig-item">Signature: <div class="sig-val"></div></div>
            </div>
        </div>
    </div>

    <!-- DAY 3 SECTION -->
    <div class="block-header" style="margin-top: 30px;">
        <div>DAY3</div>
        <div>DEPARTMENT SPECIFIC TRAINING</div>
        <div>MAN HRS : </div>
        <div>FACULTY NAME</div>
    </div>
    <div class="topics-grid">
        <div class="topic-col">
            <div style="font-weight: bold; margin-bottom: 5px;">A. DEPARTMENT DETAILS</div>
            <?php 
            $i = 1;
            foreach (getTopicsBySection($progress, 'A. DEPARTMENT DETAILS') as $t): 
            ?>
            <div class="topic-item">
                <span class="topic-num"><?php echo $i++; ?></span>
                <span class="topic-name"><?php echo e($t['topic_name']); ?></span>
                <div class="check-box"><?php echo $t['is_done'] ? '&#10003;' : ''; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="topic-col" style="border-right: none;">
            <div style="font-weight: bold; margin-bottom: 5px;">B. ACTIVITIES - 2 hrs</div>
            <?php 
            $i = 1;
            foreach (getTopicsBySection($progress, 'B. ACTIVITIES (2 hrs)') as $t): 
            ?>
            <div class="topic-item">
                <span class="topic-num"><?php echo $i++; ?></span>
                <span class="topic-name"><?php echo e($t['topic_name']); ?></span>
                <div class="check-box"><?php echo $t['is_done'] ? '&#10003;' : ''; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="topics-grid" style="grid-template-columns: 1fr; border-top: none;">
        <div class="topic-col" style="padding: 10px 40px;">
            <div style="font-weight: bold; margin-bottom: 10px;">C. TEST - WRITTEN & ORAL - 2hrs</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <?php 
                    $test_topics = getTopicsBySection($progress, 'C. TEST - WRITTEN & ORAL (2 hrs)');
                    $test_items = array_values($test_topics);
                    for($j=0; $j<3; $j++): if(!isset($test_items[$j])) continue; $t = $test_items[$j];
                    ?>
                    <div class="topic-item">
                        <span class="topic-num"><?php echo $j+1; ?></span>
                        <span class="topic-name"><?php echo e($t['topic_name']); ?></span>
                        <div class="check-box"><?php echo $t['is_done'] ? '&#10003;' : ''; ?></div>
                    </div>
                    <?php endfor; ?>
                </div>
                <div>
                    <?php 
                    for($j=3; $j<6; $j++): if(!isset($test_items[$j])) continue; $t = $test_items[$j];
                    ?>
                    <div class="topic-item">
                        <span class="topic-num"><?php echo $j+1; ?></span>
                        <span class="topic-name"><?php echo e($t['topic_name']); ?></span>
                        <div class="check-box"><?php echo $t['is_done'] ? '&#10003;' : ''; ?></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div style="border: 1px solid #000; border-top: none; padding: 10px;">
        <div class="sign-row">
            <div>
                <div class="sig-item">Trainee Name: <div class="sig-val"><?php echo e($trainee['full_name']); ?></div></div>
                <div class="sig-item">Signature: <div class="sig-val"></div></div>
            </div>
            <div>
                <?php 
                $day3 = array_filter($progress, function($p){ return $p['day_number'] == 3 && $p['trainer_name']; });
                $trainer3 = !empty($day3) ? reset($day3)['trainer_name'] : '';
                ?>
                <div class="sig-item">Trainer Name: <div class="sig-val"><?php echo e($trainer3); ?></div></div>
                <div class="sig-item">Signature: <div class="sig-val"></div></div>
            </div>
        </div>
    </div>

    <div class="footer">Page 3 of 8</div>
</div>

</body>
</html>
