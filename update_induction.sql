SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE otr_system.induction_checklist;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO otr_system.induction_checklist (day_number, section_name, topic_name, estimated_hours, estimated_mins) VALUES 
-- DAY 1: JOINING FORMALITIES (5 HRS)
(1, 'JOINING FORMALITIES', 'Joining Formalities', NULL, 90),
(1, 'JOINING FORMALITIES', 'Self Introduction', NULL, 90),
(1, 'JOINING FORMALITIES', 'About SYRMA', NULL, 30),
(1, 'JOINING FORMALITIES', 'Product and Process Details', NULL, 90),

-- DAY 2 - BLOCK 1: BASICS-AWARENESS & E-MODULE (5 HRS)
(2, 'A. SAFETY (2 hrs)', 'Basics of safety', NULL, NULL),
(2, 'A. SAFETY (2 hrs)', 'Industrial safety', NULL, NULL),
(2, 'A. SAFETY (2 hrs)', 'PPE', NULL, NULL),
(2, 'A. SAFETY (2 hrs)', 'EHS', NULL, NULL),
(2, 'A. SAFETY (2 hrs)', 'Safety E-module', NULL, NULL),
(2, 'A. SAFETY (2 hrs)', 'Sexual Harassment', NULL, NULL),

(2, 'B. BASIC CONCEPTS (3 hrs)', 'Kaizen', NULL, NULL),
(2, 'B. BASIC CONCEPTS (3 hrs)', 'Lean Manufacturing', NULL, NULL),
(2, 'B. BASIC CONCEPTS (3 hrs)', '6S', NULL, NULL),
(2, 'B. BASIC CONCEPTS (3 hrs)', 'IPC - Basics Awareness', NULL, NULL),
(2, 'B. BASIC CONCEPTS (3 hrs)', 'Industrial E-Module', NULL, NULL),
(2, 'B. BASIC CONCEPTS (3 hrs)', 'ISO Standards', NULL, NULL),
(2, 'B. BASIC CONCEPTS (3 hrs)', 'PCB Handling', NULL, NULL),
(2, 'B. BASIC CONCEPTS (3 hrs)', 'ROHS', NULL, NULL),

-- DAY 2 - BLOCK 2: BASICS-AWARENESS, E-MODULE (3 HRS)
(2, 'C. ESD TRAINING (2 hrs)', 'Why ESD?', NULL, NULL),
(2, 'C. ESD TRAINING (2 hrs)', 'Causes and Effect', NULL, NULL),
(2, 'C. ESD TRAINING (2 hrs)', 'How to use ESD?', NULL, NULL),
(2, 'C. ESD TRAINING (2 hrs)', 'How to check ESD?', NULL, NULL),

(2, 'D. FIRST AID TRAINING (1 hr)', 'Health checkup', NULL, NULL),
(2, 'D. FIRST AID TRAINING (1 hr)', 'Health Awareness', NULL, NULL),

-- DAY 3: DEPARTMENT SPECIFIC TRAINING
(3, 'A. DEPARTMENT DETAILS', 'Process Emodule', NULL, NULL),
(3, 'A. DEPARTMENT DETAILS', 'Work Instruction Detail', NULL, NULL),
(3, 'A. DEPARTMENT DETAILS', 'Product Handling Method', NULL, NULL),

(3, 'B. ACTIVITIES (2 hrs)', '5S Game', NULL, NULL),
(3, 'B. ACTIVITIES (2 hrs)', 'Group Activity', NULL, NULL),
(3, 'B. ACTIVITIES (2 hrs)', 'Multi Tasking', NULL, NULL),
(3, 'B. ACTIVITIES (2 hrs)', 'Skill Competition', NULL, NULL),

(3, 'C. TEST - WRITTEN & ORAL (2 hrs)', 'About syrma - Oral', NULL, NULL),
(3, 'C. TEST - WRITTEN & ORAL (2 hrs)', 'Safety - Written Test', NULL, NULL),
(3, 'C. TEST - WRITTEN & ORAL (2 hrs)', 'PPE - Written Test', NULL, NULL),
(3, 'C. TEST - WRITTEN & ORAL (2 hrs)', '5S - Written Test', NULL, NULL),
(3, 'C. TEST - WRITTEN & ORAL (2 hrs)', 'ESD - Written Test', NULL, NULL),
(3, 'C. TEST - WRITTEN & ORAL (2 hrs)', 'E - Module - Written Test', NULL, NULL);
