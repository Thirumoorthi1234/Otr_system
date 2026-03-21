<?php
// trainer/questions.php
require_once '../includes/layout.php';
checkRole('trainer');

$trainer_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle question save
if (isset($_POST['save_question'])) {
    $exam_id = $_POST['exam_id'];
    $question_text = $_POST['question_text'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct_option = $_POST['correct_option'];

    // Security check: trainer must be assigned to the module of this exam
    $stmt = $pdo->prepare("
        SELECT e.id FROM exams e 
        JOIN assignments a ON e.module_id = a.module_id 
        WHERE e.id = ? AND a.trainer_id = ?
    ");
    $stmt->execute([$exam_id, $trainer_id]);
    if ($stmt->fetch()) {
        if ($_POST['question_id']) {
            $stmt = $pdo->prepare("UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=? WHERE id=?");
            $stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $_POST['question_id']]);
            $message = "Question updated!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$exam_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option]);
            $message = "Question added to bank!";
        }
    }
    $action = 'list';
}

// Handle new topic/exam creation
if (isset($_POST['save_topic'])) {
    $title = $_POST['topic_title'];
    $module_id = $_POST['module_id'];
    $duration = intval($_POST['duration'] ?? 30);
    $passing_score = intval($_POST['passing_score'] ?? 70);
    $camera_enabled = isset($_POST['camera_enabled']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO exams (title, module_id, duration_minutes, passing_score, camera_enabled, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $module_id, $duration, $passing_score, $camera_enabled, $_SESSION['user_id']]);
        $message = "New exam topic '" . htmlspecialchars($title) . "' created successfully!";
    } catch (PDOException $e) {
        $message = "Error creating topic: " . $e->getMessage();
    }
    $action = 'list';
}

renderHeader('Question Bank');
renderSidebar('trainer');
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3><?php echo $action == 'add' ? 'Add Question' : ($action == 'add_topic' ? 'Add New Exam Topic' : 'Exams & Questions'); ?></h3>
        <?php if ($action == 'list'): ?>
            <a href="questions.php?action=add_topic" class="btn btn-primary" style="font-size: 0.85rem;"><i class="fas fa-plus" style="margin-right: 8px;"></i>Add Topic</a>
        <?php else: ?>
            <a href="questions.php" class="btn" style="background: #4A5568; color: white;">Back</a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div style="background: rgba(56, 161, 105, 0.1); color: #48BB78; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(56, 161, 105, 0.2);">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
            <?php
            $stmt = $pdo->prepare("
                SELECT DISTINCT e.*, m.title as module_name 
                FROM exams e 
                JOIN training_modules m ON e.module_id = m.id
                JOIN assignments a ON m.id = a.module_id 
                WHERE a.trainer_id = ?
            ");
            $stmt->execute([$trainer_id]);
            $exams = $stmt->fetchAll();
            
            foreach ($exams as $exam):
                $qCount = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE exam_id = ?");
                $qCount->execute([$exam['id']]);
                $count = $qCount->fetchColumn();
            ?>
            <div class="card" style="border: 1px solid var(--border-color); background: var(--white); box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 style="margin-bottom: 5px; color: var(--primary-blue);"><?php echo e($exam['title']); ?></h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted);"><?php echo e($exam['module_name']); ?></p>
                    </div>
                    <span class="badge badge-info"><?php echo $count; ?> Qs</span>
                </div>
                <div style="margin-top: 20px;">
                    <a href="questions.php?action=add&exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 5px 15px;">Add Question</a>
                    <a href="questions.php?action=view&exam_id=<?php echo $exam['id']; ?>" class="btn" style="font-size: 0.8rem; padding: 5px 15px; background: #EDF2F7; color: var(--text-main); border: 1px solid var(--border-color);">View All</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($action == 'add' || $action == 'edit'): 
        $exam_id = $_GET['exam_id'] ?? '';
        $question = ['id' => '', 'question_text' => '', 'option_a' => '', 'option_b' => '', 'option_c' => '', 'option_d' => '', 'correct_option' => 'A'];
        if ($action == 'edit' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $question = $stmt->fetch();
            $exam_id = $question['exam_id'];
        }
    ?>
    <form method="POST">
        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
        <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
        
        <div class="form-group">
            <label class="form-label">Question Text</label>
            <textarea name="question_text" class="form-control" rows="3" required><?php echo e($question['question_text']); ?></textarea>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Option A</label>
                <input type="text" name="option_a" class="form-control" value="<?php echo e($question['option_a']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Option B</label>
                <input type="text" name="option_b" class="form-control" value="<?php echo e($question['option_b']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Option C</label>
                <input type="text" name="option_c" class="form-control" value="<?php echo e($question['option_c']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Option D</label>
                <input type="text" name="option_d" class="form-control" value="<?php echo e($question['option_d']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Correct Option</label>
            <select name="correct_option" class="form-control">
                <?php foreach(['A', 'B', 'C', 'D'] as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo $question['correct_option'] == $opt ? 'selected' : ''; ?>>Option <?php echo $opt; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="save_question" class="btn btn-primary" style="margin-top: 10px;">Save Question</button>
    </form>
    <?php elseif ($action == 'add_topic'): ?>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Exam Topic Title</label>
            <input type="text" name="topic_title" class="form-control" placeholder="e.g. Safety Test, 5S Test" required>
        </div>
        <div class="form-group">
            <label class="form-label">Training Module</label>
            <select name="module_id" class="form-control" required>
                <option value="">-- Select Module --</option>
                <?php
                $modules = $pdo->query("SELECT id, title FROM training_modules ORDER BY title");
                while ($m = $modules->fetch()) {
                    echo "<option value='{$m['id']}'>{$m['title']}</option>";
                }
                ?>
            </select>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Duration (Minutes)</label>
                <input type="number" name="duration" class="form-control" value="30" required>
            </div>
            <div class="form-group">
                <label class="form-label">Passing Score (%)</label>
                <input type="number" name="passing_score" class="form-control" value="70" required>
            </div>
        </div>
        <div class="form-group" style="margin-top: 10px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">
                <input type="checkbox" name="camera_enabled" value="1" style="width: 18px; height: 18px; accent-color: var(--primary-blue);">
                <i class="fas fa-video" style="color: var(--primary-blue);"></i>
                Enable camera proctoring
            </label>
        </div>
        <button type="submit" name="save_topic" class="btn btn-primary" style="margin-top: 15px; width: 100%;">
            <i class="fas fa-plus" style="margin-right: 8px;"></i>Create Exam Topic
        </button>
    </form>

    <?php elseif ($action == 'view'): 
        $exam_id = $_GET['exam_id'];
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        $questions = $stmt->fetchAll();
    ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Question</th>
                    <th>Options</th>
                    <th>Correct</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                <tr>
                    <td><?php echo e($q['question_text']); ?></td>
                    <td>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            A: <?php echo e($q['option_a']); ?><br>
                            B: <?php echo e($q['option_b']); ?>
                        </div>
                    </td>
                    <td><span class="badge badge-success"><?php echo $q['correct_option']; ?></span></td>
                    <td>
                        <a href="questions.php?action=edit&id=<?php echo $q['id']; ?>" style="color: var(--primary-blue);"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
