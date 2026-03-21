-- seed_training_data.sql

-- 1. Create Training Modules
INSERT IGNORE INTO training_modules (id, title, description, category) VALUES 
(1, 'Induction Training', 'Foundational training covering Safety, PPE, 5S, and ESD.', 'Induction'),
(2, 'Practical Training-SDC', 'Practical skill development center training.', 'SDC'),
(3, 'On the job training- shop floor', 'Hands-on training on the production floor.', 'OTJ');

-- 2. Create Exams
INSERT IGNORE INTO exams (id, module_id, title, duration_minutes, passing_score) VALUES 
(1, 1, '5S Test', 15, 80),
(2, 1, 'Safety Test', 15, 80),
(3, 1, 'PPE Test', 15, 80),
(4, 1, 'ESD Test', 15, 80);

-- 3. Insert Questions for 5S Test (from Image 1)
INSERT IGNORE INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES 
(1, '5S stands for', 'Sort, Set, Shine, Standardize and Sustain', 'Sort, Shine, Set, Sustain, Standardize', 'Shine, Spotless, Sanitize & Safety on Saturdays', 'NONE', 'A'),
(1, 'Who is responsible for 5S', 'Cleaning team and maintenance', 'Operators and cleaners', 'Cleaners, operators, maintenance and management', 'NONE', 'C'),
(1, 'Where to do 5S', '5S is best only done on the plant floor', '5S is best done in areas that are messy', '5S is best done on the plant floor, maintenance, shipping and offices', 'NONE', 'C'),
(1, 'When to do 5S', '5S is best done every day as part of good practices', '5S is best to be done when the plant is not busy', '5S is best done at the end of the day or before a shutdown', 'NONE', 'A'),
(1, 'Which one of these best describes 5S', 'Nothing out of place and nothing missing', 'A place for everything and everything in its place identified and ready for use', 'A home for everything', 'NONE', 'B');

-- 4. Insert Questions for Safety Test (from Image 2)
INSERT IGNORE INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES 
(2, 'When you hear the evacuation alarm you must...', 'Stop to get your belongings', 'Call your supervisor', 'Await further instruction', 'Evacuate immediately to the emergency assembly location', 'D'),
(2, 'What factor is not relevant in the event of an emergency?', 'Who your fire wardens are', 'Completing an experiment', 'The Emergency Procedures', 'Where to assemble', 'B'),
(2, 'Who is the first person to inform if you have an incident or identify a hazard even if no-one is injured?', 'Your supervisor', 'The director', 'First aider', 'Reception', 'A'),
(2, 'If you are injured at work, no matter how insignificant it is, you must...', 'Go home', 'Report it to your supervisor', 'Go to the hospital', 'Tell your family', 'B'),
(2, 'Which of the following activities are considered "hot work"?', 'Welding', 'Cutting', 'Grinding Ferrous Metals', 'All of the Above', 'D');

-- 5. Insert Questions for PPE Test (from Image 3)
INSERT IGNORE INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES 
(3, 'PPE Stands for', 'Personal Product Equipment', 'Personal Protective Equipment', 'Personal Production Equipment', 'Personal Perfect Equipment', 'B'),
(3, 'Which of the following are considered PPE?', 'Safety glasses', 'Ear plugs', 'Gloves', 'All of the above', 'D'),
(3, 'Which of the following can cause a severe eye injury if the proper protection is not worn?', 'Flying metal chips', 'Nails', 'Chemicals', 'All of the above', 'D'),
(3, 'When working with chemicals, safety glasses offer adequate eye protection.', 'True', 'False', '', '', 'A'),
(3, 'PPE offers little or no protection if worn improperly.', 'True', 'False', '', '', 'A'),
(3, 'All gloves offer the same basic level of protection.', 'True', 'False', '', '', 'B'),
(3, 'How often should you inspect your PPE to ensure its in good condition and functioning properly?', 'Before each use', 'once a week', 'Once a month', 'When need', 'A');

-- 6. Insert Questions for ESD Test (from Image 4)
INSERT IGNORE INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES 
(4, 'Give Expansion of ESD', 'Electronic static Display', 'Electrical Static Display', 'Electro static Discharge', 'Electro Magnetic Discharge', 'C'),
(4, 'ESD Safe Materials for Human Body', 'Shoe, Gloves Helmet, Band.', 'ESD Shoe, ESD gloves, ESD Coat & Wrist', 'Safety Glass, Helmet, Wrist Watch', 'Face Mask, Safety Glass, Hand Gloves', 'B'),
(4, 'Function of wrist band', 'ground the charges in our body', 'to avoid shock', 'to reduce temperature in our body', 'All the above', 'A'),
(4, 'What is the purpose of ESD safe materials?', 'Maintain quality', 'Increase lifetime of the product', 'Avoid Latent failures', 'All the above', 'D'),
(4, 'List out the ESD Safe materails.', 'Wrist strap, ESD shoes, ESD apron', 'Normal shoes, cotton shirt', 'Plastic bags, rubber bands', 'NONE', 'A'); -- Adding options for MCQ consistency
