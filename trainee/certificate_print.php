<?php
// trainee/certificate_print.php - Standalone print/download page
require_once '../includes/config.php';
require_once '../includes/functions.php';
checkRole('trainee');

$result_id = $_GET['id'] ?? null;
$trainee_id = $_SESSION['user_id'];

if (!$result_id) { header('Location: results.php'); exit; }

$stmt = $pdo->prepare("
    SELECT r.*, e.title as exam_name, m.title as module_name 
    FROM exam_results r 
    JOIN exams e ON r.exam_id = e.id 
    JOIN training_modules m ON e.module_id = m.id
    WHERE r.id = ? AND r.trainee_id = ? AND r.status = 'pass'
");
$stmt->execute([$result_id, $trainee_id]);
$result = $stmt->fetch();

if (!$result) { header('Location: results.php'); exit; }

$trainee_name = e($_SESSION['full_name'] ?? 'Trainee');
$exam_date    = date('F d, Y', strtotime($result['exam_date']));
$module_name  = e($result['module_name']);
$exam_name    = e($result['exam_name']);
$score        = $result['score'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo $trainee_name; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #1e293b;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .print-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .print-controls button {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-print {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
        }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(14,165,233,0.4); }

        .btn-back {
            background: #334155;
            color: white;
        }
        .btn-back:hover { background: #475569; }

        /* ─── Certificate ─── */
        .cert-wrapper {
            width: 1123px;
            height: 794px;
            position: relative;
            background: white;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }

        /* Gradient background */
        .cert-bg {
            position: absolute; inset: 0;
            background: radial-gradient(circle at center, #ffffff 40%, #f0f9ff 100%);
            z-index: 1;
        }

        /* Border layers */
        .cert-border-outer {
            position: absolute;
            top: 25px; left: 25px; right: 25px; bottom: 25px;
            border: 12px solid #0f172a;
            z-index: 2;
        }
        .cert-border-inner {
            position: absolute;
            top: 42px; left: 42px; right: 42px; bottom: 42px;
            border: 2px solid #0ea5e9;
            z-index: 2;
        }

        /* Corner ornaments */
        .corner { position: absolute; width: 25px; height: 25px; z-index: 3; }
        .corner-tl { top: 38px; left: 38px; border-top: 4px solid #0ea5e9; border-left: 4px solid #0ea5e9; }
        .corner-tr { top: 38px; right: 38px; border-top: 4px solid #0ea5e9; border-right: 4px solid #0ea5e9; }
        .corner-bl { bottom: 38px; left: 38px; border-bottom: 4px solid #0ea5e9; border-left: 4px solid #0ea5e9; }
        .corner-br { bottom: 38px; right: 38px; border-bottom: 4px solid #0ea5e9; border-right: 4px solid #0ea5e9; }

        /* Main content */
        .cert-body {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 50px 70px 40px;
            text-align: center;
        }

        .cert-logo img { height: 55px; }

        .cert-title {
            font-size: 3.4rem;
            color: #0f172a;
            font-family: 'Outfit', serif;
            text-transform: uppercase;
            letter-spacing: 6px;
            font-weight: 900;
            margin: 0 0 5px;
        }

        .cert-subtitle {
            font-size: 1.6rem;
            color: #0ea5e9;
            font-family: 'Outfit', sans-serif;
            text-transform: uppercase;
            letter-spacing: 4px;
            font-weight: 600;
            margin: 0 0 20px;
        }

        .cert-presented {
            font-size: 1.2rem;
            color: #64748b;
            font-style: italic;
            font-family: serif;
            margin-bottom: 15px;
        }

        .cert-name {
            font-size: 3.5rem;
            color: #0f172a;
            border-bottom: 4px solid #e2e8f0;
            display: inline-block;
            padding: 0 40px 10px;
            margin: 0 0 20px;
            font-family: 'Outfit', serif;
            font-weight: 800;
            min-width: 60%;
        }

        .cert-desc {
            font-size: 1.3rem;
            color: #475569;
            line-height: 1.6;
            padding: 0 100px;
            margin: 0 0 10px;
        }

        .cert-exam {
            font-size: 1.2rem;
            color: #64748b;
        }

        /* Footer */
        .cert-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 15px;
        }

        .cert-powered {
            text-align: center;
            width: 250px;
        }
        .cert-powered-label {
            font-size: 0.8rem;
            color: #94a3b8;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        .cert-powered img { height: 35px; }
        .cert-powered .powered-fallback { font-weight: 900; color: #0f172a; font-size: 1.5rem; }

        /* Score badge */
        .cert-score-badge {
            width: 120px;
            height: 120px;
            background: #0ea5e9;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border: 5px solid white;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4);
            position: relative;
            transform: translateY(-5px);
        }
        .cert-score-badge::before {
            content: '';
            position: absolute;
            top: 4px; left: 4px; right: 4px; bottom: 4px;
            border: 1px dashed rgba(255,255,255,0.7);
            border-radius: 50%;
        }
        .cert-score-label {
            font-size: 0.75rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }
        .cert-score-value {
            font-size: 2.3rem;
            color: white;
            font-weight: 900;
            font-family: 'Outfit', sans-serif;
            line-height: 1;
            position: relative;
            z-index: 2;
        }

        /* Date */
        .cert-date { text-align: center; width: 250px; }
        .cert-date-value {
            font-size: 1.3rem;
            color: #0f172a;
            font-weight: 800;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 8px;
            margin-bottom: 8px;
            font-family: 'Outfit', sans-serif;
        }
        .cert-date-label {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* ─── Print styles ─── */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }

            body {
                background: white !important;
                padding: 0 !important;
                display: block !important;
                min-height: unset !important;
            }

            .print-controls { display: none !important; }

            .cert-wrapper {
                width: 100%;
                height: 100vh;
                box-shadow: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- Controls (hidden during print) -->
    <div class="print-controls">
        <button class="btn-back" onclick="window.close()">← Back</button>
        <button class="btn-print" onclick="window.print()">⬇ Save / Print as PDF</button>
    </div>

    <!-- Certificate -->
    <div class="cert-wrapper">
        <div class="cert-bg"></div>
        <div class="cert-border-outer"></div>
        <div class="cert-border-inner"></div>
        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>

        <div class="cert-body">
            <!-- Header -->
            <div>
                <div class="cert-logo" style="margin-bottom:20px;">
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" onerror="this.style.display='none'">
                </div>
                <h1 class="cert-title">Certificate</h1>
                <h2 class="cert-subtitle">of Completion</h2>
                <p class="cert-presented">This is proudly presented to</p>
            </div>

            <!-- Name -->
            <div>
                <div class="cert-name"><?php echo $trainee_name; ?></div>
            </div>

            <!-- Achievement -->
            <div>
                <p class="cert-desc">
                    for successfully completing the training requirements and demonstrating proficiency in
                    <strong style="color:#0f172a; font-size:1.5rem; display:block; margin-top:8px;">"<?php echo $module_name; ?>"</strong>
                </p>
                <p class="cert-exam" style="margin-top:15px;">
                    having passed the <strong style="color:#0f172a;">"<?php echo $exam_name; ?>"</strong> examination.
                </p>
            </div>

            <!-- Footer -->
            <div class="cert-footer">
                <div class="cert-powered">
                    <div class="cert-powered-label">Powered By</div>
                    <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg"
                         onerror="this.outerHTML='<span class=\'powered-fallback\'>Learnlike</span>'">
                </div>

                <div class="cert-score-badge">
                    <div class="cert-score-label">Score</div>
                    <div class="cert-score-value"><?php echo $score; ?>%</div>
                </div>

                <div class="cert-date">
                    <div class="cert-date-value"><?php echo $exam_date; ?></div>
                    <div class="cert-date-label">Date of Certification</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto open print dialog on load
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 500);
        });
    </script>
</body>
</html>
