<?php
// trainer/results.php - Trainer can view exam results with answer review
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];

// Fetch all exam results for trainees assigned to this trainer
$stmt = $pdo->prepare("
    SELECT er.*, 
           u.full_name as trainee_name, u.employee_id, u.photo_path,
           e.title as exam_title, e.passing_score, e.duration_minutes,
           m.title as module_name
    FROM exam_results er
    JOIN users u ON er.trainee_id = u.id
    JOIN exams e ON er.exam_id = e.id
    JOIN training_modules m ON e.module_id = m.id
    JOIN assignments a ON (a.trainee_id = er.trainee_id AND a.module_id = e.module_id)
    WHERE a.trainer_id = ?
    ORDER BY er.exam_date DESC
");
$stmt->execute([$trainer_id]);
$results = $stmt->fetchAll();

$total    = count($results);
$passed   = array_filter($results, fn($r) => $r['status'] === 'pass');
$failed   = array_filter($results, fn($r) => $r['status'] === 'fail');
$avgScore = $total > 0 ? round(array_sum(array_column($results, 'score')) / $total) : 0;

renderHeader('Exam Results');
renderSidebar('trainer');
?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Attempts</div>
        <div class="stat-value"><?php echo $total; ?></div>
        <div class="stat-trend" style="color:var(--brand-royal);"><i class="fas fa-pen-alt"></i> All exam attempts</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Passed</div>
        <div class="stat-value" style="color:#059669;"><?php echo count($passed); ?></div>
        <div class="stat-trend" style="color:#059669;"><i class="fas fa-check-circle"></i> Successful trainees</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Failed</div>
        <div class="stat-value" style="color:#dc2626;"><?php echo count($failed); ?></div>
        <div class="stat-trend" style="color:#dc2626;"><i class="fas fa-times-circle"></i> Need improvement</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Avg Score</div>
        <div class="stat-value"><?php echo $avgScore; ?>%</div>
        <div class="stat-trend" style="color:var(--brand-sky);"><i class="fas fa-chart-line"></i> Class average</div>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h3><i class="fas fa-chart-bar" style="color:var(--brand-royal); margin-right:10px;"></i>Trainee Exam Results</h3>
        <span class="badge badge-info"><?php echo $total; ?> records</span>
    </div>

    <?php if (empty($results)): ?>
    <div style="text-align:center; padding:60px 20px;">
        <div style="width:80px; height:80px; border-radius:50%; background:rgba(11,112,183,0.1); display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
            <i class="fas fa-clipboard-list" style="font-size:2rem; color:var(--brand-royal);"></i>
        </div>
        <h4 style="color:var(--brand-navy); margin-bottom:8px;">No Results Yet</h4>
        <p style="color:var(--text-muted);">Your trainees haven't taken any exams yet.</p>
    </div>
    <?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Trainee</th>
                    <th>Module</th>
                    <th>Exam</th>
                    <th>Score</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Review Answers</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $row):
                $scoreColor = $row['status'] === 'pass' ? '#059669' : '#dc2626';
            ?>
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="user-avatar" style="width:38px; height:38px; border-radius:12px;">
                            <?php if (!empty($row['photo_path'])): ?>
                                <img src="<?php echo BASE_URL . $row['photo_path']; ?>">
                            <?php else: ?><i class="fas fa-user" style="font-size:0.9rem;"></i><?php endif; ?>
                        </div>
                        <div>
                            <div style="font-weight:700; color:var(--brand-navy); font-size:0.9rem;"><?php echo e($row['trainee_name']); ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo e($row['employee_id']); ?></div>
                        </div>
                    </div>
                </td>
                <td style="color:var(--text-muted); font-size:0.85rem;"><?php echo e($row['module_name']); ?></td>
                <td style="font-weight:600; color:var(--brand-navy);"><?php echo e($row['exam_title']); ?></td>
                <td>
                    <span style="font-size:1.2rem; font-weight:800; color:<?php echo $scoreColor; ?>; font-family:'Outfit',sans-serif;"><?php echo $row['score']; ?>%</span>
                    <div style="font-size:0.72rem; color:var(--text-muted);">Pass: <?php echo $row['passing_score']; ?>%</div>
                </td>
                <td style="width:130px;">
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" style="width:<?php echo min($row['score'],100); ?>%; background:<?php echo $row['status']==='pass'?'linear-gradient(90deg,#10b981,#059669)':'linear-gradient(90deg,#ef4444,#b91c1c)'; ?>;"></div>
                    </div>
                </td>
                <td>
                    <?php if ($row['status']==='pass'): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> PASS</span>
                    <?php else: ?>
                        <span class="badge badge-danger"><i class="fas fa-times"></i> FAIL</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted); font-size:0.85rem; white-space:nowrap;">
                    <?php echo date('d M Y, H:i', strtotime($row['exam_date'])); ?>
                </td>
                <td>
                    <button onclick="reviewAnswers(<?php echo $row['id']; ?>, '<?php echo e(addslashes($row['trainee_name'])); ?>', '<?php echo e(addslashes($row['exam_title'])); ?>')"
                            class="btn" style="background:rgba(99,102,241,0.1); color:#6366f1; font-size:0.75rem; padding:6px 12px; border:1px solid rgba(99,102,241,0.2);">
                        <i class="fas fa-search"></i> Review
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Answer Review Modal -->
<div id="answerModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:10000; overflow-y:auto; padding:30px 20px;">
    <div style="background:var(--card-bg); border-radius:24px; max-width:700px; margin:0 auto; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="background:linear-gradient(135deg,#1e293b,#334155); padding:25px 30px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="color:#fff; margin:0; font-size:1.2rem;" id="modal-trainee-name">Answer Review</h3>
                <div style="color:#94a3b8; font-size:0.85rem; margin-top:4px;" id="modal-exam-name"></div>
            </div>
            <button onclick="closeModal()" style="background:rgba(255,255,255,0.1); border:none; color:#fff; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:1.1rem;">×</button>
        </div>
        <div id="modal-loading" style="text-align:center; padding:50px; color:var(--text-muted);">
            <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;"></i><br>Loading answers…
        </div>
        <div id="modal-content" style="padding:25px 30px; display:none; max-height:70vh; overflow-y:auto;"></div>
    </div>
</div>

<script>
async function reviewAnswers(resultId, traineeName, examTitle) {
    document.getElementById('answerModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    document.getElementById('modal-trainee-name').textContent = '📋 ' + traineeName;
    document.getElementById('modal-exam-name').textContent = examTitle;
    document.getElementById('modal-loading').style.display = 'block';
    document.getElementById('modal-content').style.display = 'none';
    document.getElementById('modal-content').innerHTML = '';

    try {
        const resp = await fetch('<?php echo BASE_URL; ?>api/get_exam_answers.php?result_id=' + resultId);
        const data = await resp.json();

        if (!data.success) {
            document.getElementById('modal-loading').innerHTML = '<i class="fas fa-info-circle" style="color:#6366f1;margin-right:8px;"></i>' + (data.message || 'No detailed answer data available.');
            return;
        }
        if (!data.answers || !data.answers.length) {
            document.getElementById('modal-loading').innerHTML = '<i class="fas fa-info-circle" style="color:#6366f1;margin-right:8px;"></i>No answer breakdown available. This exam was taken before the feature was enabled.';
            return;
        }

        let html = `<div style="margin-bottom:18px; display:flex; gap:10px; flex-wrap:wrap;">
            <span style="background:#dcfce7; color:#16a34a; padding:6px 14px; border-radius:8px; font-weight:800; font-size:0.87rem;">✓ Correct: ${data.correct}</span>
            <span style="background:#fef2f2; color:#dc2626; padding:6px 14px; border-radius:8px; font-weight:800; font-size:0.87rem;">✗ Wrong: ${data.wrong}</span>
            <span style="background:rgba(99,102,241,0.1); color:#6366f1; padding:6px 14px; border-radius:8px; font-weight:800; font-size:0.87rem;">Score: ${data.score}%</span>
        </div>`;

        data.answers.forEach((a, i) => {
            const isCorrect = parseInt(a.is_correct) === 1;
            const bg     = isCorrect ? '#f0fdf4' : '#fef2f2';
            const border = isCorrect ? '#86efac' : '#fca5a5';
            const tAns   = a.trainee_answer;
            const tOpt   = tAns ? a['option_' + tAns.toLowerCase()] : null;
            const cOpt   = a['option_' + a.correct_option.toLowerCase()];

            html += `<div style="background:${bg}; border:1.5px solid ${border}; border-radius:14px; padding:14px 18px; margin-bottom:12px;">
                <div style="font-size:0.7rem; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:6px;">Question ${i+1}</div>
                <div style="font-weight:700; color:#0f172a; font-size:0.92rem; margin-bottom:10px; line-height:1.5;">${escHtml(a.question_text)}</div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <div style="padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; background:${isCorrect?'#dcfce7':'#fee2e2'}; color:${isCorrect?'#16a34a':'#dc2626'};">
                        ${isCorrect ? '✓' : '✗'} Trainee: <strong>${tAns || 'No answer'}</strong>${tOpt ? ' — ' + escHtml(tOpt) : ''}
                    </div>
                    ${!isCorrect ? `<div style="padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; background:#dcfce7; color:#16a34a;">
                        ✓ Correct: <strong>${a.correct_option}</strong> — ${escHtml(cOpt)}
                    </div>` : ''}
                </div>
            </div>`;
        });

        document.getElementById('modal-content').innerHTML = html;
        document.getElementById('modal-loading').style.display = 'none';
        document.getElementById('modal-content').style.display = 'block';
    } catch(e) {
        document.getElementById('modal-loading').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#ef4444;margin-right:8px;"></i>Error loading answer data.';
    }
}

function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function closeModal() {
    document.getElementById('answerModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('answerModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php renderFooter(); ?>
