<?php
// trainee/feedback.php
require_once '../includes/layout.php';
checkRole('trainee');

renderHeader('Training Feedback Form');
renderSidebar('trainee');

// Fetch all staff (except Trainees and Admins)
$stmt = $pdo->prepare("
    SELECT id, full_name, role 
    FROM users 
    WHERE role NOT IN ('trainee', 'admin') 
    ORDER BY full_name ASC
");
$stmt->execute();
$faculties = $stmt->fetchAll();

// Contextual Assignment ID
$assignment_id = $_GET['assignment_id'] ?? null;
$exam_id = $_GET['exam_id'] ?? null;

if (!$assignment_id && $exam_id) {
    // Resolve exam_id to assignment_id
    $stmt = $pdo->prepare("
        SELECT a.id 
        FROM assignments a
        JOIN exams e ON a.module_id = e.module_id
        WHERE e.id = ? AND a.trainee_id = ?
        LIMIT 1
    ");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    $assignment_id = $stmt->fetchColumn();
}
?>

<div class="card" style="max-width: 900px; margin: auto; padding: 40px; border-radius: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.05); border: none;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h2 style="font-weight: 800; color: #1a365d; margin-bottom: 10px;">Training Evaluation Form</h2>
        <p style="color: #718096; font-size: 1.1rem;">Please provide your feedback for the faculty/trainer.</p>
    </div>

    <form id="feedback-form" action="submit_feedback.php" method="POST">
        <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
        <div class="trainer-select-container" style="margin-bottom: 35px; background: #f7fafc; padding: 25px; border-radius: 20px; border: 1px solid #edf2f7;">
            <label style="display: block; font-weight: 700; color: #2d3748; margin-bottom: 12px; font-size: 1rem;">Select Faculty / Staff <span style="color: #e53e3e;">*</span></label>
            <select name="trainer_id" required style="width: 100%; padding: 15px; border-radius: 12px; border: 2px solid #e2e8f0; font-size: 1rem; font-weight: 600; background: white; appearance: none; cursor: pointer;">
                <option value="">-- Choose Instructor --</option>
                <?php foreach ($faculties as $f): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo e($f['full_name']); ?> (<?php echo ucfirst($f['role']); ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="background: #edf2f7; padding: 15px; border-radius: 12px; margin-bottom: 25px; text-align: center; border: 1px solid #e2e8f0;">
            <p style="margin: 0; font-size: 0.95rem; color: #4a5568; font-weight: 600;">
                <strong>A</strong>: Excellent &bull; <strong>B</strong>: Good &bull; <strong>C</strong>: Standard &bull; <strong>D</strong>: Needs Improvement
            </p>
        </div>

        <div class="feedback-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
            <?php
            $criteria = [
                ['key' => 'rating_learning_skill', 'label' => 'Learning of Skills / Techniques'],
                ['key' => 'rating_learning_knowledge', 'label' => 'Learning of Specialized Knowledge'],
                ['key' => 'rating_learning_attitude', 'label' => 'Improvement of Attitude'],
                ['key' => 'rating_explanation', 'label' => 'Faculties Explanation Clarity'],
                ['key' => 'rating_improvement', 'label' => 'Ability for Self-Improvement'],
                ['key' => 'rating_time', 'label' => 'Training Time Management'],
                ['key' => 'rating_overall', 'label' => 'Overall Training Effectiveness']
            ];

            foreach ($criteria as $c):
            ?>
            <div style="padding: 20px; border-radius: 16px; background: white; border: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                <span style="font-weight: 600; color: #4a5568; flex: 1;"><?php echo $c['label']; ?></span>
                <div style="display: flex; gap: 8px;">
                    <?php foreach (['A', 'B', 'C', 'D'] as $grade): ?>
                    <label class="rating-box" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 10px; border: 2px solid #e2e8f0; background: #f8fafc; cursor: pointer; transition: all 0.2s; font-weight: 800; color: #718096;">
                        <input type="radio" name="<?php echo $c['key']; ?>" value="<?php echo $grade; ?>" required style="display: none;">
                        <span><?php echo $grade; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 35px;">
            <label style="display: block; font-weight: 700; color: #2d3748; margin-bottom: 12px; font-size: 1rem;">Methodology & Additional Comments</label>
            <textarea name="comments" rows="4" placeholder="Share your experience or suggestions for improvement..." style="width: 100%; padding: 20px; border-radius: 16px; border: 2px solid #e2e8f0; font-size: 1rem; font-family: inherit; resize: none;"></textarea>
        </div>

        <div style="margin-top: 40px; text-align: center;">
            <button type="submit" class="btn btn-primary" style="padding: 15px 60px; font-weight: 800; font-size: 1.2rem; border-radius: 15px; background: #3182ce; border: none; box-shadow: 0 10px 25px rgba(49, 130, 206, 0.3);">
                SUBMIT FEEDBACK <i class="fas fa-paper-plane" style="margin-left: 10px;"></i>
            </button>
        </div>
    </form>
</div>

<style>
    .rating-box:has(input:checked) {
        border-color: #3182ce !important;
        background: #ebf8ff !important;
        color: #3182ce !important;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(49, 130, 206, 0.15);
    }
    .rating-box:hover {
        border-color: #cbd5e0;
        background: #edf2f7;
    }
    @media (max-width: 768px) {
        .trainer-select-container { padding: 15px !important; }
        select { font-size: 0.9rem !important; padding: 12px !important; }
    }
    textarea:focus, select:focus {
        outline: none;
        border-color: #3182ce !important;
        background: white !important;
        box-shadow: 0 0 0 4px rgba(49, 130, 206, 0.1);
    }
    
    @media (max-width: 768px) {
        .card { padding: 20px !important; border-radius: 20px !important; }
        .feedback-grid { gap: 12px !important; }
        .feedback-grid > div { flex-direction: column !important; align-items: flex-start !important; padding: 15px !important; }
        .feedback-grid > div > div { width: 100%; justify-content: space-between; margin-top: 10px; }
        .rating-box { width: 40px !important; height: 40px !important; }
        h2 { font-size: 1.4rem !important; }
    }
</style>

<script>
    document.getElementById('feedback-form').addEventListener('submit', function(e) {
        // Simple confirmation before submit
        if(!confirm('Submit this evaluation?')) e.preventDefault();
    });
</script>

<?php renderFooter(); ?>
