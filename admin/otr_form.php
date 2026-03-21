<?php
// admin/otr_form.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole(['admin', 'trainer', 'management']);

$trainee_id = $_GET['tid'] ?? null;
if (!$trainee_id) die("Trainee ID required.");

// Fetch Trainee Details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'trainee'");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();

if (!$trainee) die("Trainee not found.");

// Fetch Induction Progress
$stmt = $pdo->prepare("
    SELECT ic.*, p.is_done 
    FROM induction_checklist ic 
    LEFT JOIN trainee_checklist_progress p ON ic.id = p.checklist_id AND p.trainee_id = ?
    ORDER BY ic.day_number, ic.id
");
$stmt->execute([$trainee_id]);
$checklist = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTR Form | <?php echo e($trainee['full_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 40px;
            background: #f4f4f4;
        }
        .paper {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
        }
        .header {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 40px;
        }
        .header img {
            height: 40px;
            margin-right: 20px;
        }
        .header-text {
            border-left: 2px solid #000;
            padding-left: 15px;
        }
        .header-text h1 {
            margin: 0;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-title {
            text-align: center;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 40px;
            text-decoration: underline;
        }
        .employee-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 50px;
        }
        .details-table {
            width: 70%;
            border-collapse: collapse;
        }
        .details-table td {
            padding: 8px 0;
            font-size: 0.95rem;
        }
        .details-table td:first-child {
            width: 140px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .details-table td:nth-child(2) {
            width: 10px;
        }
        .photo-box {
            width: 130px;
            height: 150px;
            border: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 0.8rem;
            font-weight: bold;
            overflow: hidden;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .checklist-section {
            margin-top: 30px;
        }
        .checklist-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .checklist-table {
            width: 100%;
            border-collapse: collapse;
        }
        .checklist-table th, .checklist-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-size: 0.9rem;
        }
        .checklist-table th {
            background: #f8f8f8;
            text-align: center;
        }
        .checklist-table td:first-child { width: 60px; text-align: center; }
        .checklist-table td:nth-child(3) { width: 100px; text-align: center; }
        .checklist-table td:nth-child(4) { width: 80px; text-align: center; }
        
        .footer-info {
            position: absolute;
            bottom: 40px;
            left: 50px;
            right: 50px;
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            background: #0B70B7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .paper { box-shadow: none; margin: 0; width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="javascript:window.print()" class="btn">Print Form</a>
    <a href="induction_records.php?trainee_id=<?php echo $trainee_id; ?>" class="btn" style="background: #4A5568;">Back</a>
</div>

<div class="paper">
    <div class="header">
        <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" alt="Syrma SGS">
        <div class="header-text">
            <h1>Operator Training Record</h1>
        </div>
    </div>

    <div class="form-title">EMPLOYEE DETAILS</div>

    <div class="employee-details">
        <table class="details-table">
            <tr>
                <td>Name</td>
                <td>:</td>
                <td><?php echo e($trainee['full_name']); ?></td>
            </tr>
            <tr>
                <td>T. No.</td>
                <td>:</td>
                <td><?php echo e($trainee['employee_id']); ?></td>
            </tr>
            <tr>
                <td>Qualification</td>
                <td>:</td>
                <td><?php echo e($trainee['qualification'] ?? ''); ?></td>
            </tr>
            <tr>
                <td>DOJ</td>
                <td>:</td>
                <td><?php echo $trainee['doj'] ? date('d/m/Y', strtotime($trainee['doj'])) : ''; ?></td>
            </tr>
            <tr>
                <td>Dept</td>
                <td>:</td>
                <td><?php echo e($trainee['department']); ?></td>
            </tr>
            <tr>
                <td>Category</td>
                <td>:</td>
                <td><?php echo e($trainee['category'] ?? ''); ?></td>
            </tr>
        </table>
        <div class="photo-box">
            <?php if ($trainee['photo_path']): ?>
                <img src="<?php echo BASE_URL . $trainee['photo_path']; ?>">
            <?php else: ?>
                AFFIX PHOTO
            <?php endif; ?>
        </div>
    </div>

    <div class="checklist-section">
        <div class="checklist-title">Training Module - Check List</div>
        <table class="checklist-table">
            <thead>
                <tr>
                    <th>S.NO.</th>
                    <th>INDUCTION TRAINING</th>
                    <th>MAN HRS</th>
                    <th>DONE</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checklist as $item): ?>
                <tr>
                    <td>DAY <?php echo $item['day_number']; ?></td>
                    <td><?php echo e($item['topic_name']); ?></td>
                    <td><?php echo $item['estimated_hours'] ? $item['estimated_hours'] . ' hrs' : ''; ?></td>
                    <td style="text-align: center;">
                        <?php if ($item['is_done']): ?>
                            <span style="font-family: DejaVu Sans, sans-serif;">&#10003;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="footer-info">
        <span>SST3001FRM2097/D</span>
        <span>Page 1 of 7</span>
    </div>
</div>

</body>
</html>
