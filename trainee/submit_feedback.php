<?php
// trainee/submit_feedback.php
require_once '../includes/config.php';
// session_start(); // Already started in config.php

if ($_SESSION['role'] !== 'trainee') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainee_id = $_SESSION['user_id'];
    $trainer_id = $_POST['trainer_id'] ?? null;
    $rating_overall = $_POST['rating_overall'] ?? 'C';
    $rating_learning_skill = $_POST['rating_learning_skill'] ?? 'C';
    $rating_learning_knowledge = $_POST['rating_learning_knowledge'] ?? 'C';
    $rating_learning_attitude = $_POST['rating_learning_attitude'] ?? 'C';
    $rating_explanation = $_POST['rating_explanation'] ?? 'C';
    $rating_improvement = $_POST['rating_improvement'] ?? 'C';
    $rating_time = $_POST['rating_time'] ?? 'C';
    $assignment_id = !empty($_POST['assignment_id']) ? $_POST['assignment_id'] : null;
    $comments = $_POST['comments'] ?? '';

    if ($trainee_id && $trainer_id) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO feedback (
                    assignment_id, trainee_id, trainer_id, rating_overall, 
                    rating_skill, rating_learning_skill, rating_learning_knowledge, 
                    rating_learning_attitude, rating_explanation, rating_improvement, 
                    rating_time, comments
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $assignment_id, $trainee_id, $trainer_id, $rating_overall,
                $rating_learning_skill, // Map learning skill to rating_skill for constraint
                $rating_learning_skill, $rating_learning_knowledge, $rating_learning_attitude,
                $rating_explanation, $rating_improvement, $rating_time, $comments
            ]);
            
            $_SESSION['success_msg'] = "Thank you! Your feedback has been submitted.";

            // --- Notification Logic ---
            require_once '../includes/functions.php';
            $trainee_name = $_SESSION['full_name'] ?? 'A trainee';
            $title = "New Feedback Submitted";
            $message = "$trainee_name has submitted a new training evaluation feedback.";
            
            // Notify all Admins and Management
            $notify_stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'management')");
            $admins = $notify_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($admins as $admin_id) {
                addNotification($admin_id, $title, $message, 'info', "admin/feedback_view.php?tid=$trainee_id");
            }

            // --- Re-evaluate Module Completion ---
            // Now that feedback is submitted, any module where all tests are passed should be marked COMPLETED
            $stmt = $pdo->prepare("SELECT id, module_id FROM assignments WHERE trainee_id = ? AND status != 'completed'");
            $stmt->execute([$trainee_id]);
            $pending_assignments = $stmt->fetchAll();

            foreach ($pending_assignments as $pa) {
                $mid = $pa['module_id'];
                // Count exams for this module
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE module_id = ?");
                $stmt_count->execute([$mid]);
                $total_exams = (int)$stmt_count->fetchColumn();
                
                if ($total_exams > 0) {
                    // Count passed exams
                    $stmt_passed = $pdo->prepare("SELECT COUNT(DISTINCT exam_id) FROM exam_results WHERE trainee_id = ? AND status = 'pass' AND exam_id IN (SELECT id FROM exams WHERE module_id = ?)");
                    $stmt_passed->execute([$trainee_id, $mid]);
                    $passed_count = (int)$stmt_passed->fetchColumn();
                    
                    if ($passed_count >= $total_exams) {
                        $pdo->prepare("UPDATE assignments SET status = 'completed', completion_date = CURDATE() WHERE id = ?")->execute([$pa['id']]);
                    }
                }
            }
            
            header("Location: results.php");
            exit;
        } catch (PDOException $e) {
            die("Error submitting feedback: " . $e->getMessage());
        }
    }
}

header("Location: feedback.php");
exit;
