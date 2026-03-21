-- seed_results.sql
-- Injected results for Trainee 1 (assuming ID 2)
INSERT INTO exam_results (trainee_id, exam_id, score, status, exam_date) VALUES 
(2, 1, 85, 'pass', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 95, 'pass', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO feedback (trainee_id, rating_overall, rating_learning_skill, rating_learning_knowledge, rating_learning_attitude, rating_explanation, rating_improvement, rating_time, comments, submitted_at) VALUES 
(2, 'A', 'A', 'A', 'B', 'A', 'A', 'A', 'Very thorough induction. The safety module was particularly helpful.', NOW());
