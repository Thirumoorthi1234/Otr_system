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
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
            <div style="text-align: right; margin-bottom: 20px;">
                <button onclick="downloadCertificate()" id="downloadCertBtn" class="btn" style="background: linear-gradient(135deg, #0F172A, #334155); color: white; padding: 10px 24px; font-weight: 700; border-radius: 8px; border: none; cursor: pointer;">
                    <i class="fas fa-file-pdf"></i> Download Certificate
                </button>
            </div>
            
            <div style="display: flex; justify-content: center; overflow: hidden; padding: 5px;">
                <div id="certificate-content" style="background: white; width: 100%; max-width: 1050px; border: 15px solid #0F172A; padding: 30px; position: relative; font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; box-shadow: 0 10px 30px rgba(0,0,0,0.1); page-break-inside: avoid;">
                    <!-- Inner border -->
                    <div style="border: 2px solid #E2E8F0; padding: 25px; text-align: center; position: relative; height: 100%; box-sizing: border-box;">
                        
                        <!-- Top Center Logo -->
                        <div style="margin-bottom: 20px;">
                            <img src="<?php echo BASE_URL; ?>assets/img/profiles/logo.svg" style="height: 45px;" onerror="this.style.display='none';">
                        </div>
                        
                        <h1 style="font-size: 3.2rem; color: #0EA5E9; margin: 10px 0 15px; font-family: 'Outfit', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-weight: 900;">Certificate of Completion</h1>
                        <p style="font-size: 1.15rem; color: #64748B; margin-bottom: 15px;">This proudly certifies that</p>
                        
                        <h2 style="font-size: 2.6rem; color: #0F172A; border-bottom: 3px solid #0EA5E9; display: inline-block; padding-bottom: 8px; margin: 0 0 15px; font-family: 'Outfit', sans-serif;">
                            <?php echo e($_SESSION['full_name'] ?? 'Trainee'); ?>
                        </h2>
                        
                        <p style="font-size: 1.2rem; color: #64748B; margin: 10px 0;">has successfully completed the training module requirements for</p>
                        <h3 style="font-size: 1.8rem; color: #1E293B; margin: 10px 0 20px;"><?php echo e($result['module_name'] ?? 'Module'); ?></h3>
                        
                        <p style="font-size: 1.15rem; color: #64748B; margin-bottom: 5px;">and obtained a passing grade on the</p>
                        <p style="font-size: 1.4rem; color: #0F172A; font-weight: 800; margin: 5px 0 15px;">"<?php echo e($result['exam_name']); ?>"</p>
                        
                        <div style="display: inline-flex; align-items: center; justify-content: center; gap: 15px; margin: 10px 0 20px; background: #F8FAFC; padding: 12px 25px; border-radius: 50px; border: 1px solid #E2E8F0;">
                            <div style="font-size: 0.85rem; color: #64748B; font-weight: 700; text-transform: uppercase;">Final Score</div>
                            <div style="font-size: 2.2rem; font-weight: 900; color: #10B981; font-family: 'Outfit', sans-serif; line-height: 1;"><?php echo $result['score']; ?>%</div>
                        </div>
                        
                        <!-- Bottom Area: Powered By & Date -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 20px; padding-top: 15px;">
                            <div style="text-align: left;">
                                <div style="font-size: 0.75rem; color: #94A3B8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">Powered By</div>
                                <img src="<?php echo BASE_URL; ?>assets/img/profiles/powered_by.svg" style="height: 28px;" onerror="this.outerHTML='<span style=\'font-weight:bold;color:#4A5568;\'>Learnlike</span>';">
                            </div>
                            
                            <div style="text-align: center;">
                                <div style="font-size: 1.1rem; color: #0F172A; font-weight: 700; border-bottom: 2px solid #CBD5E1; padding-bottom: 5px; width: 180px; margin-bottom: 8px;">
                                    <?php echo date('F d, Y', strtotime($result['exam_date'])); ?>
                                </div>
                                <div style="font-size: 0.85rem; color: #64748B; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Date of Certification</div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: center; gap: 15px; margin-top: 30px;">
                <a href="dashboard.php" class="btn btn-dashboard">Dashboard</a>
                <a href="feedback.php?exam_id=<?php echo $result['id']; ?>" class="btn btn-primary">Submit Feedback</a>
            </div>
            
            <script>
            function downloadCertificate() {
                const element = document.getElementById('certificate-content');
                const btn = document.getElementById('downloadCertBtn');
                const origText = btn.innerHTML;
                
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
                btn.disabled = true;
                btn.style.opacity = '0.7';
                
                const opt = {
                    margin:       0.2,
                    filename:     'Certificate_<?php echo str_replace(' ', '_', $_SESSION['full_name'] ?? 'Trainee'); ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 1.5, useCORS: true, letterRendering: true, logging: false },
                    jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' },
                    pagebreak:    { mode: 'avoid-all' }
                };
                
                html2pdf().set(opt).from(element).save().then(() => {
                    btn.innerHTML = '<i class="fas fa-check-circle"></i> Downloaded!';
                    btn.style.background = '#10B981';
                    setTimeout(() => {
                        btn.innerHTML = origText;
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.style.background = 'linear-gradient(135deg, #0F172A, #334155)';
                    }, 3000);
                }).catch(() => {
                    btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Retry';
                    btn.disabled = false;
                    setTimeout(() => { btn.innerHTML = origText; }, 3000);
                });
            }
            </script>
            
        <?php else: ?>
            <!-- FAILED EXAM VIEW -->
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

                <div style="display: flex; gap: 15px; justify-content: center;">
                    <a href="dashboard.php" class="btn btn-dashboard">Dashboard</a>
                    <a href="exam.php?id=<?php echo $result['exam_id']; ?>" class="btn btn-primary">Retry Exam</a>
                </div>
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
