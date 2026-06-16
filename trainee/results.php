<?php
// trainee/results.php
require_once '../includes/layout.php';
checkRole('trainee');

$result_id = $_GET['id'] ?? null;
$trainee_id = $_SESSION['user_id'];

if ($result_id) {
    $stmt = $pdo->prepare("
        SELECT r.*, e.title as exam_name, m.title as module_name 
        FROM exam_results r 
        JOIN exams e ON r.exam_id = e.id 
        JOIN training_modules m ON e.module_id = m.id
        WHERE r.id = ? AND r.trainee_id = ?
    ");
    $stmt->execute([$result_id, $trainee_id]);
    $result = $stmt->fetch();
} else {
    // List all results
    $stmt = $pdo->prepare("
        SELECT r.*, e.title as exam_name 
        FROM exam_results r 
        JOIN exams e ON r.exam_id = e.id 
        WHERE r.trainee_id = ? 
        ORDER BY r.exam_date DESC
    ");
    $stmt->execute([$trainee_id]);
    $results = $stmt->fetchAll();
}

renderHeader('Exam Results');
renderSidebar('trainee');
?>

<style>
    .btn-dashboard {
        background: #0F172A !important;
        color: white !important;
        padding: 10px 24px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        transition: all 0.3s ease !important;
    }
    .btn-dashboard:hover {
        background: #1e293b !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
    }
</style>

<div class="card">
    <?php if ($result_id && isset($result)): ?>
        <?php if ($result['status'] == 'pass'): ?>
            <!-- CERTIFICATE VIEW -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <a href="javascript:history.back()" class="btn" style="background: #4A5568; color: white; padding: 10px 24px; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="certificate_print.php?id=<?php echo $result_id; ?>" target="_blank"
                   class="btn" style="background: linear-gradient(135deg, #0F172A, #334155); color: white; padding: 10px 24px; font-weight: 700; border-radius: 8px; text-decoration: none;">
                    <i class="fas fa-file-pdf"></i> Download Certificate
                </a>
            </div>
            
            <div id="cert-container" style="width: 100%; display: flex; justify-content: center; overflow: hidden; padding: 20px 0; background: #f8fafc; border-radius: 12px;">
                <div id="cert-scale-wrapper" style="position: relative; transition: width 0.2s ease, height 0.2s ease; box-shadow: 0 15px 40px rgba(0,0,0,0.15);">
                    <!-- Fixed A4 Landscape dimensions (1123x794px at 96dpi) to prevent 2nd page issues -->
                    <div id="certificate-content" style="background: white; width: 1123px; height: 794px; position: absolute; top: 0; left: 0; transform-origin: top left; transition: transform 0.2s ease; font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; margin: 0; -webkit-print-color-adjust: exact; color-adjust: exact;">
                    
                    <!-- Decorative Background -->
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at center, #ffffff 40%, #f0f9ff 100%); z-index: 1;"></div>
                    
                    <!-- Elegant Border System -->
                    <div style="position: absolute; top: 25px; left: 25px; right: 25px; bottom: 25px; border: 12px solid #0f172a; z-index: 2;"></div>
                    <div style="position: absolute; top: 42px; left: 42px; right: 42px; bottom: 42px; border: 2px solid #0ea5e9; z-index: 2;"></div>
                    
                    <!-- Corner Ornaments -->
                    <div style="position: absolute; top: 38px; left: 38px; width: 25px; height: 25px; border-top: 4px solid #0ea5e9; border-left: 4px solid #0ea5e9; z-index: 3;"></div>
                    <div style="position: absolute; top: 38px; right: 38px; width: 25px; height: 25px; border-top: 4px solid #0ea5e9; border-right: 4px solid #0ea5e9; z-index: 3;"></div>
                    <div style="position: absolute; bottom: 38px; left: 38px; width: 25px; height: 25px; border-bottom: 4px solid #0ea5e9; border-left: 4px solid #0ea5e9; z-index: 3;"></div>
                    <div style="position: absolute; bottom: 38px; right: 38px; width: 25px; height: 25px; border-bottom: 4px solid #0ea5e9; border-right: 4px solid #0ea5e9; z-index: 3;"></div>

                    <!-- Main Content Container -->
                    <div style="position: relative; z-index: 10; height: 100%; display: flex; flex-direction: column; justify-content: space-between; padding: 50px 70px 40px; text-align: center; box-sizing: border-box;">
                        
                        <!-- Header -->
                        <div>
                            <div style="margin-bottom: 20px;">
                                <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" style="height: 55px;" onerror="this.style.display='none';">
                            </div>
                            <h1 style="font-size: 3.4rem; color: #0f172a; margin: 0 0 5px; font-family: 'Outfit', serif; text-transform: uppercase; letter-spacing: 6px; font-weight: 900;">Certificate</h1>
                            <h2 style="font-size: 1.6rem; color: #0ea5e9; margin: 0 0 20px; font-family: 'Outfit', sans-serif; text-transform: uppercase; letter-spacing: 4px; font-weight: 600;">of Completion</h2>
                            <p style="font-size: 1.2rem; color: #64748b; margin-bottom: 15px; font-style: italic; font-family: serif;">This is proudly presented to</p>
                        </div>
                        
                        <!-- Trainee Name -->
                        <div>
                            <h2 style="font-size: 3.5rem; color: #0f172a; border-bottom: 4px solid #e2e8f0; display: inline-block; padding: 0 40px 10px; margin: 0 0 20px; font-family: 'Outfit', serif; font-weight: 800; min-width: 60%;">
                                <?php echo e($_SESSION['full_name'] ?? 'Trainee'); ?>
                            </h2>
                        </div>
                        
                        <!-- Achievement Text -->
                        <div>
                            <p style="font-size: 1.3rem; color: #475569; margin: 0 0 15px; line-height: 1.6; padding: 0 100px;">
                                for successfully completing the training requirements and demonstrating proficiency in 
                                <strong style="color: #0f172a; font-size: 1.5rem; display: block; margin-top: 8px;">"<?php echo e($result['module_name'] ?? 'Module'); ?>"</strong>
                            </p>
                            <p style="font-size: 1.2rem; color: #64748b; margin: 15px 0 0;">
                                having passed the <strong style="color: #0f172a;">"<?php echo e($result['exam_name']); ?>"</strong> examination.
                            </p>
                        </div>
                        
                        <!-- Bottom Footer: Signatures, Score & Date -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: auto; padding-top: 15px;">
                            
                            <!-- Powered By / Left Side -->
                            <div style="text-align: center; width: 250px;">
                                <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px;">Powered By</div>
                                <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg" style="height: 35px;" onerror="this.outerHTML='<span style=\'font-weight:900;color:#0f172a;font-size:1.5rem;\'>Learnlike</span>';">
                            </div>
                            
                            <!-- Score Badge (Center) -->
                            <div style="width: 120px; height: 120px; background: #0ea5e9; border-radius: 50%; display: flex; flex-direction: column; justify-content: center; align-items: center; border: 5px solid white; box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4); position: relative; transform: translateY(-5px);">
                                <div style="position: absolute; top: 4px; left: 4px; right: 4px; bottom: 4px; border: 1px dashed rgba(255,255,255,0.7); border-radius: 50%;"></div>
                                <div style="font-size: 0.75rem; color: white; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 2px; z-index: 2;">Score</div>
                                <div style="font-size: 2.3rem; color: white; font-weight: 900; font-family: 'Outfit', sans-serif; line-height: 1; z-index: 2;"><?php echo $result['score']; ?>%</div>
                            </div>
                            
                            <!-- Date / Right Side -->
                            <div style="text-align: center; width: 250px;">
                                <div style="font-size: 1.3rem; color: #0f172a; font-weight: 800; border-bottom: 2px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 8px; font-family: 'Outfit', sans-serif;">
                                    <?php echo date('F d, Y', strtotime($result['exam_date'])); ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 2px;">Date of Certification</div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: center; gap: 15px; margin-top: 30px; margin-bottom: 40px;">
                <a href="dashboard.php" class="btn btn-dashboard">Dashboard</a>
                <a href="feedback.php?exam_id=<?php echo $result['id']; ?>" class="btn btn-primary">Submit Feedback</a>
            </div>

            <?php
            // Fetch Detailed Answers for Passed Exam View
            try {
                if (!isset($answers)) {
                    $stmt_ans = $pdo->prepare("
                        SELECT a.trainee_answer, a.is_correct, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option 
                        FROM exam_result_answers a 
                        JOIN questions q ON a.question_id = q.id 
                        WHERE a.result_id = ?
                    ");
                    $stmt_ans->execute([$result_id]);
                    $answers = $stmt_ans->fetchAll();
                }
            } catch(Exception $e) { $answers = []; }
            
            if (!empty($answers)):
            ?>
            <div style="text-align: left; max-width: 800px; margin: 0 auto 40px; background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="color: var(--brand-navy); font-size: 1.5rem; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                    <i class="fas fa-clipboard-check"></i> Answer Review
                </h3>
                
                <?php foreach ($answers as $index => $ans): 
                    $is_corr = (bool)$ans['is_correct'];
                    $bg = $is_corr ? '#f0fdf4' : '#fef2f2';
                    $border = $is_corr ? '#bbf7d0' : '#fecaca';
                    $iconUrl = $is_corr ? '<i class="fas fa-check-circle" style="color: #22c55e;"></i>' : '<i class="fas fa-times-circle" style="color: #ef4444;"></i>';
                    
                    $map = ['a'=>$ans['option_a'], 'b'=>$ans['option_b'], 'c'=>$ans['option_c'], 'd'=>$ans['option_d']];
                    $given = $ans['trainee_answer'] ? ($map[$ans['trainee_answer']] ?? $ans['trainee_answer']) : '<span style="color:#94a3b8; font-style:italic;">Not Answered</span>';
                    $correct = $map[$ans['correct_option']] ?? $ans['correct_option'];
                ?>
                <div style="background: <?php echo $bg; ?>; border: 1px solid <?php echo $border; ?>; border-radius: 12px; padding: 20px; margin-bottom: 15px;">
                    <div style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="font-size: 1.5rem; margin-top: 2px;">
                            <?php echo $iconUrl; ?>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 10px; font-size: 1.1rem; color: #1e293b;"><?php echo ($index + 1) . ". " . e($ans['question_text']); ?></h4>
                            <div style="display: flex; flex-wrap: wrap; gap: 20px; font-size: 0.95rem;">
                                <div style="flex: 1; min-width: 200px;">
                                    <div style="font-weight: 700; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Your Answer</div>
                                    <div style="color: <?php echo $is_corr ? '#166534' : '#991b1b'; ?>; font-weight: 600;"><?php echo $given; ?></div>
                                </div>
                                <?php if (!$is_corr): ?>
                                <div style="flex: 1; min-width: 200px;">
                                    <div style="font-weight: 700; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Correct Answer</div>
                                    <div style="color: #166534; font-weight: 600;"><?php echo e($correct); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <script>
            function resizeCert() {
                const container = document.getElementById('cert-container');
                const wrapper = document.getElementById('cert-scale-wrapper');
                const content = document.getElementById('certificate-content');
                if (!container || !wrapper || !content) return;
                
                // Calculate available width (subtracting a little padding)
                const availableWidth = container.clientWidth - 40;
                // Calculate scale required to fit the 1123px certificate
                const scale = Math.min(1, availableWidth / 1123);
                
                content.style.transform = `scale(${scale})`;
                // Adjust wrapper bounding box so flexbox centers it perfectly
                wrapper.style.width = `${1123 * scale}px`;
                wrapper.style.height = `${794 * scale}px`;
            }
            
            // Resize on load and window resize
            window.addEventListener('resize', resizeCert);
            document.addEventListener('DOMContentLoaded', resizeCert);
            // Run once immediately just in case
            resizeCert();

            function downloadCertificate() {
                const element = document.getElementById('certificate-content');
                const btn = document.getElementById('downloadCertBtn');
                const origText = btn.innerHTML;
                
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
                btn.disabled = true;
                btn.style.opacity = '0.7';
                
                // Clone the certificate at full 1123x794 resolution and place it off-screen
                // html2canvas cannot capture position:absolute elements inside overflow:hidden wrappers
                const clone = element.cloneNode(true);
                clone.style.position = 'fixed';
                clone.style.top = '-2000px';
                clone.style.left = '-2000px';
                clone.style.width = '1123px';
                clone.style.height = '794px';
                clone.style.transform = 'none';
                clone.style.zIndex = '-9999';
                clone.style.transition = 'none';
                document.body.appendChild(clone);
                
                const opt = {
                    margin:       0,
                    filename:     'Certificate_<?php echo str_replace(' ', '_', $_SESSION['full_name'] ?? 'Trainee'); ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true, logging: false, allowTaint: true, backgroundColor: '#ffffff' },
                    jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
                };
                
                setTimeout(() => {
                    html2pdf().set(opt).from(clone).save().then(() => {
                        document.body.removeChild(clone);
                        btn.innerHTML = '<i class="fas fa-check-circle"></i> Downloaded!';
                        btn.style.background = '#10B981';
                        setTimeout(() => {
                            btn.innerHTML = origText;
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            btn.style.background = 'linear-gradient(135deg, #0F172A, #334155)';
                        }, 3000);
                    }).catch((e) => {
                        console.error("PDF Generation Error: ", e);
                        document.body.removeChild(clone);
                        btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Retry';
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        setTimeout(() => { btn.innerHTML = origText; }, 3000);
                    });
                }, 150);
            }
            </script>
            
        <?php else: ?>
            <!-- FAILED EXAM VIEW -->
            <div style="text-align: left; margin-bottom: 20px;">
                <a href="javascript:history.back()" class="btn" style="background: #4A5568; color: white; padding: 10px 24px; font-weight: 700; border-radius: 8px;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            <div style="text-align: center; padding: 40px;">
                <div style="font-size: 5rem; color: var(--danger); margin-bottom: 20px;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h2 style="font-size: 2.5rem; margin-bottom: 10px;">KEEP TRYING</h2>
                <p style="color: var(--text-muted); margin-bottom: 30px;">
                    You have not cleared the <strong><?php echo e($result['exam_name']); ?></strong>.
                </p>
                
                <div style="display: flex; justify-content: center; gap: 50px; margin-bottom: 40px;">
                    <div>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 5px; font-weight: 700;">YOUR SCORE</p>
                        <p style="font-size: 2.5rem; font-weight: 800; color: var(--danger); font-family: 'Outfit', sans-serif;"><?php echo $result['score']; ?>%</p>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 40px;">
                    <a href="dashboard.php" class="btn btn-dashboard">Dashboard</a>
                    <a href="exam.php?id=<?php echo $result['exam_id']; ?>" class="btn btn-primary">Retry Exam</a>
                </div>

                <?php
                // Fetch Detailed Answers
                try {
                    $stmt_ans = $pdo->prepare("
                        SELECT a.trainee_answer, a.is_correct, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option 
                        FROM exam_result_answers a 
                        JOIN questions q ON a.question_id = q.id 
                        WHERE a.result_id = ?
                    ");
                    $stmt_ans->execute([$result_id]);
                    $answers = $stmt_ans->fetchAll();
                } catch(Exception $e) { $answers = []; }
                
                if (!empty($answers)):
                ?>
                <div style="text-align: left; max-width: 800px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                    <h3 style="color: var(--brand-navy); font-size: 1.5rem; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                        <i class="fas fa-clipboard-check"></i> Answer Review
                    </h3>
                    
                    <?php foreach ($answers as $index => $ans): 
                        $is_corr = (bool)$ans['is_correct'];
                        $bg = $is_corr ? '#f0fdf4' : '#fef2f2';
                        $border = $is_corr ? '#bbf7d0' : '#fecaca';
                        $iconUrl = $is_corr ? '<i class="fas fa-check-circle" style="color: #22c55e;"></i>' : '<i class="fas fa-times-circle" style="color: #ef4444;"></i>';
                        
                        $map = ['a'=>$ans['option_a'], 'b'=>$ans['option_b'], 'c'=>$ans['option_c'], 'd'=>$ans['option_d']];
                        $given = $ans['trainee_answer'] ? ($map[$ans['trainee_answer']] ?? $ans['trainee_answer']) : '<span style="color:#94a3b8; font-style:italic;">Not Answered</span>';
                        $correct = $map[$ans['correct_option']] ?? $ans['correct_option'];
                    ?>
                    <div style="background: <?php echo $bg; ?>; border: 1px solid <?php echo $border; ?>; border-radius: 12px; padding: 20px; margin-bottom: 15px;">
                        <div style="display: flex; gap: 15px; align-items: flex-start;">
                            <div style="font-size: 1.5rem; margin-top: 2px;">
                                <?php echo $iconUrl; ?>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 10px; font-size: 1.1rem; color: #1e293b;"><?php echo ($index + 1) . ". " . e($ans['question_text']); ?></h4>
                                <div style="display: flex; flex-wrap: wrap; gap: 20px; font-size: 0.95rem;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <div style="font-weight: 700; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Your Answer</div>
                                        <div style="color: <?php echo $is_corr ? '#166534' : '#991b1b'; ?>; font-weight: 600;"><?php echo $given; ?></div>
                                    </div>
                                    <?php if (!$is_corr): ?>
                                    <div style="flex: 1; min-width: 200px;">
                                        <div style="font-weight: 700; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Correct Answer</div>
                                        <div style="color: #166534; font-weight: 600;"><?php echo e($correct); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <h3 style="margin-bottom: 25px;">My Academic History</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Exam Title</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $res): ?>
                    <tr>
                        <td><?php echo formatDate($res['exam_date']); ?></td>
                        <td><strong><?php echo e($res['exam_name']); ?></strong></td>
                        <td><?php echo $res['score']; ?>%</td>
                        <td><span class="badge <?php echo $res['status'] == 'pass' ? 'badge-success' : 'badge-warning'; ?>"><?php echo strtoupper($res['status']); ?></span></td>
                        <td><a href="results.php?id=<?php echo $res['id']; ?>" style="color: var(--primary-blue);"><i class="fas fa-eye"></i> View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
