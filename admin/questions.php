<?php
// admin/questions.php
require_once '../includes/layout.php';
checkRole('admin');

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) { header("Location: exams.php"); exit(); }

$stmt = $pdo->prepare("SELECT title FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

$action = $_GET['action'] ?? 'list';
$message = '';

if (isset($_POST['save_question'])) {
    $text = $_POST['question_text'];
    $a = $_POST['option_a'];
    $b = $_POST['option_b'];
    $c = $_POST['option_c'];
    $d = $_POST['option_d'];
    $correct = $_POST['correct_option'];
    
    if ($_POST['q_id']) {
        $stmt = $pdo->prepare("UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=? WHERE id=?");
        $stmt->execute([$text, $a, $b, $c, $d, $correct, $_POST['q_id']]);
        $message = "Question updated!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$exam_id, $text, $a, $b, $c, $d, $correct]);
        $message = "Question added!";
    }
    $action = 'list';
}

renderHeader('Question Bank');
renderSidebar('admin');
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: centre; margin-bottom: 25px;">
        <div>
            <h3><?php echo e($exam['title']); ?></h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Manage MCQ questions for this exam</p>
        </div>
        <?php if ($action == 'list'): ?>
            <div>
                <a href="exams.php" class="btn" style="background: #4A5568; color: white;">Back to Exams</a>
                <a href="questions.php?exam_id=<?php echo $exam_id; ?>&action=add" class="btn btn-primary">Add Question</a>
            </div>
        <?php else: ?>
            <a href="questions.php?exam_id=<?php echo $exam_id; ?>" class="btn" style="background: #4A5568; color: white;">Back to List</a>
        <?php endif; ?>
    </div>

    <?php if ($action == 'list'): ?>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        $count = 1;
        while ($q = $stmt->fetch()):
        ?>
        <div style="background: #2D3748; padding: 20px; border-radius: 12px; margin-bottom: 15px; border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between;">
                <strong>Q<?php echo $count++; ?>: <?php echo e($q['question_text']); ?></strong>
                <div>
                    <a href="questions.php?exam_id=<?php echo $exam_id; ?>&action=edit&id=<?php echo $q['id']; ?>" style="color: var(--primary-blue); margin-right: 15px;"><i class="fas fa-edit"></i></a>
                    <a href="questions.php?exam_id=<?php echo $exam_id; ?>&action=delete&id=<?php echo $q['id']; ?>" style="color: var(--danger);"><i class="fas fa-trash"></i></a>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px; font-size: 0.9rem;">
                <div style="<?php echo $q['correct_option'] == 'A' ? 'color: var(--success); font-weight: bold;' : ''; ?>">A) <?php echo e($q['option_a']); ?></div>
                <div style="<?php echo $q['correct_option'] == 'B' ? 'color: var(--success); font-weight: bold;' : ''; ?>">B) <?php echo e($q['option_b']); ?></div>
                <div style="<?php echo $q['correct_option'] == 'C' ? 'color: var(--success); font-weight: bold;' : ''; ?>">C) <?php echo e($q['option_c']); ?></div>
                <div style="<?php echo $q['correct_option'] == 'D' ? 'color: var(--success); font-weight: bold;' : ''; ?>">D) <?php echo e($q['option_d']); ?></div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: 
        $q = ['id' => '', 'question_text' => '', 'option_a' => '', 'option_b' => '', 'option_c' => '', 'option_d' => '', 'correct_option' => 'A'];
        if ($action == 'edit' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $q = $stmt->fetch();
        }
    ?>
    <form method="POST">
        <input type="hidden" name="q_id" value="<?php echo $q['id']; ?>">
        <div class="form-group">
            <label class="form-label">Question Text</label>
            <textarea name="question_text" class="form-control" rows="3" required><?php echo e($q['question_text']); ?></textarea>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Option A</label>
                <input type="text" name="option_a" class="form-control" value="<?php echo e($q['option_a']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Option B</label>
                <input type="text" name="option_b" class="form-control" value="<?php echo e($q['option_b']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Option C</label>
                <input type="text" name="option_c" class="form-control" value="<?php echo e($q['option_c']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Option D</label>
                <input type="text" name="option_d" class="form-control" value="<?php echo e($q['option_d']); ?>" required>
            </div>
        </div>
        <div class="form-group" style="width: 200px;">
            <label class="form-label">Correct Option</label>
            <select name="correct_option" class="form-control" style="background: #2D3748;">
                <option value="A" <?php echo $q['correct_option'] == 'A' ? 'selected' : ''; ?>>Option A</option>
                <option value="B" <?php echo $q['correct_option'] == 'B' ? 'selected' : ''; ?>>Option B</option>
                <option value="C" <?php echo $q['correct_option'] == 'C' ? 'selected' : ''; ?>>Option C</option>
                <option value="D" <?php echo $q['correct_option'] == 'D' ? 'selected' : ''; ?>>Option D</option>
            </select>
        </div>
        <button type="submit" name="save_question" class="btn btn-primary">Save Question</button>
    </form>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
