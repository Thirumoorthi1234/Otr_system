<?php
// admin/score_sheet_entry.php
require_once '../includes/layout.php';
checkRole(['admin', 'trainer']);

$trainee_id = $_GET['tid'] ?? null;
$message = '';

if (isset($_POST['save_scores'])) {
    $t_id = intval($_POST['trainee_id']);
    
    // Validate that the trainee exists
    if ($t_id <= 0) {
        $message = "Error: No trainee selected. Please go back and select a trainee first.";
    } else {
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $checkUser->execute([$t_id]);
        if (!$checkUser->fetch()) {
            $message = "Error: Trainee ID {$t_id} not found in the system.";
        } else {
            $s1 = $_POST['topic_1_score'];
            $s2 = $_POST['topic_2_score'];
            $s3 = $_POST['topic_3_score'];
            $s4 = $_POST['topic_4_score'];
            $s5 = $_POST['topic_5_score'];
            $s6 = $_POST['topic_6_score'];
            $trainer_id = $_SESSION['user_id'];

            $stmt = $pdo->prepare("SELECT id FROM induction_scores WHERE trainee_id = ?");
            $stmt->execute([$t_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare("UPDATE induction_scores SET topic_1_score=?, topic_2_score=?, topic_3_score=?, topic_4_score=?, topic_5_score=?, topic_6_score=?, trainer_id=? WHERE trainee_id=?");
                $stmt->execute([$s1, $s2, $s3, $s4, $s5, $s6, $trainer_id, $t_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO induction_scores (trainee_id, topic_1_score, topic_2_score, topic_3_score, topic_4_score, topic_5_score, topic_6_score, trainer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$t_id, $s1, $s2, $s3, $s4, $s5, $s6, $trainer_id]);
            }
            $message = "Scores saved successfully!";
        }
    }
}

$scores = ['topic_1_score' => '', 'topic_2_score' => '', 'topic_3_score' => '', 'topic_4_score' => '', 'topic_5_score' => '', 'topic_6_score' => ''];
if ($trainee_id) {
    $stmt = $pdo->prepare("SELECT * FROM induction_scores WHERE trainee_id = ?");
    $stmt->execute([$trainee_id]);
    $res = $stmt->fetch();
    if ($res) $scores = $res;
}

renderHeader('Induction Score Entry');
renderSidebar($_SESSION['role']);
?>

<div class="card" style="max-width: 800px; margin: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3>Record Induction Test Scores</h3>
        <a href="users.php" class="btn" style="background: #4A5568; color: white;">Back to Users</a>
    </div>

    <?php if ($message): ?>
        <div style="background: rgba(56, 161, 105, 0.1); color: #48BB78; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(56, 161, 105, 0.2);">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group" style="margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <label class="form-label" style="font-weight: 700; color: #1a365d;">Select Trainee to Record Scores</label>
            <select name="trainee_id" class="form-control" onchange="window.location.href='score_sheet_entry.php?tid=' + this.value" required>
                <option value="">-- Choose a Trainee --</option>
                <?php
                $trainees = $pdo->query("SELECT id, full_name, employee_id FROM users WHERE role = 'trainee' ORDER BY full_name ASC");
                while ($t = $trainees->fetch()) {
                    $selected = ($trainee_id == $t['id']) ? 'selected' : '';
                    echo "<option value='{$t['id']}' $selected>{$t['full_name']} ({$t['employee_id']})</option>";
                }
                ?>
            </select>
            <?php if (!$trainee_id): ?>
                <p style="margin-top: 10px; font-size: 0.85rem; color: #e53e3e;"><i class="fas fa-info-circle"></i> Please select a trainee first to enter scores.</p>
            <?php endif; ?>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 80px;">S.No.</th>
                        <th>Test Topic</th>
                        <th style="width: 150px;">Score (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: center;">1</td>
                        <td>About syrma - Oral</td>
                        <td><input type="number" name="topic_1_score" class="form-control" value="<?php echo $scores['topic_1_score']; ?>" min="0" max="100"></td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">2</td>
                        <td>Safety - Written Test</td>
                        <td><input type="number" name="topic_2_score" class="form-control" value="<?php echo $scores['topic_2_score']; ?>" min="0" max="100"></td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">3</td>
                        <td>PPE - Written Test</td>
                        <td><input type="number" name="topic_3_score" class="form-control" value="<?php echo $scores['topic_3_score']; ?>" min="0" max="100"></td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">4</td>
                        <td>5S - Written Test</td>
                        <td><input type="number" name="topic_4_score" class="form-control" value="<?php echo $scores['topic_4_score']; ?>" min="0" max="100"></td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">5</td>
                        <td>ESD - Written Test</td>
                        <td><input type="number" name="topic_5_score" class="form-control" value="<?php echo $scores['topic_5_score']; ?>" min="0" max="100"></td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">6</td>
                        <td>E-Module - Written Test</td>
                        <td><input type="number" name="topic_6_score" class="form-control" value="<?php echo $scores['topic_6_score']; ?>" min="0" max="100"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap;">
            <button type="submit" name="save_scores" class="btn btn-primary" style="flex: 1; min-width: 200px;">Save All Scores</button>
            <?php if ($trainee_id): ?>
                <a href="score_sheet_view.php?tid=<?php echo $trainee_id; ?>" target="_blank" class="btn" style="background: var(--dark-blue); color: white; flex: 1; text-align: center; min-width: 200px;"><i class="fas fa-print"></i> View Score Sheet</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php renderFooter(); ?>
