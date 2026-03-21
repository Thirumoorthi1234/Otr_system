-- seed_exams.sql
INSERT INTO exams (module_id, title, duration_minutes, passing_score) VALUES 
(1, 'General Safety & EHS', 15, 80),
(1, 'ESD Awareness & Prevention', 10, 90),
(1, 'Basics of Manufacturing (5S/Kaizen)', 15, 75);

-- Questions for Exam 1 (General Safety)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES 
(1, 'What does EHS stand for?', 'Environment Health & Safety', 'Energy Heat & Steam', 'Electrical High Security', 'Engine Hose System', 'A'),
(1, 'In case of fire, what is the first priority?', 'Evacuation', 'Saving assets', 'Calling friends', 'Taking photos', 'A'),
(1, 'Which color is used for emergency exits?', 'Green', 'Red', 'Blue', 'Yellow', 'A');

-- Questions for Exam 2 (ESD)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES 
(2, 'What does ESD stand for?', 'Electrostatic Discharge', 'Electronic System Data', 'Electric Surge Device', 'Engine Speed Detector', 'A'),
(2, 'Which of these is used to prevent ESD?', 'Wrist Strap', 'Silk scarf', 'Plactic gloves', 'Woolen coat', 'A');

-- Questions for Exam 3 (Basics)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES 
(3, 'What is the first step in 5S?', 'Sort', 'Shine', 'Standardize', 'Sustain', 'A'),
(3, 'Kaizen means...', 'Continuous Improvement', 'King of Zen', 'Keep it simple', 'Knowledge zone', 'A');
