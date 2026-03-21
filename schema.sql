-- Digital OTR System Database Schema

CREATE DATABASE IF NOT EXISTS otr_system;
USE otr_system;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    employee_id VARCHAR(50) UNIQUE,
    email VARCHAR(100),
    role ENUM('admin', 'trainer', 'trainee', 'management') NOT NULL,
    department VARCHAR(100),
    qualification VARCHAR(100),
    category VARCHAR(50),
    photo_path VARCHAR(255),
    doj DATE, -- Date of Joining
    dol DATE, -- Date of Leaving
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Induction Checklist Topics
CREATE TABLE IF NOT EXISTS induction_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_number INT NOT NULL, -- 1, 2, 3
    section_name VARCHAR(100), -- e.g., SAFETY, BASIC CONCEPTS
    topic_name VARCHAR(255) NOT NULL,
    estimated_hours DECIMAL(4,2),
    estimated_mins INT -- or just hours
);

-- Trainee Induction Progress
CREATE TABLE IF NOT EXISTS trainee_checklist_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainee_id INT NOT NULL,
    checklist_id INT NOT NULL,
    is_done BOOLEAN DEFAULT FALSE,
    trainer_id INT, -- Who signed it off
    completed_at TIMESTAMP,
    FOREIGN KEY (trainee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (checklist_id) REFERENCES induction_checklist(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Training Modules
CREATE TABLE IF NOT EXISTS training_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50), -- e.g., Safety, Induction, Lean
    total_hours INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Training Assignments (Link Trainee, Trainer, and Module)
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainee_id INT NOT NULL,
    trainer_id INT NOT NULL,
    module_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    assigned_date DATE,
    completion_date DATE,
    FOREIGN KEY (trainee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES training_modules(id) ON DELETE CASCADE
);

-- OTJ Training Stages (Shop floor training)
CREATE TABLE IF NOT EXISTS training_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    type ENUM('sdc', 'otj', 'recertification') DEFAULT 'sdc',
    stage_name VARCHAR(100),
    man_hours DECIMAL(5,2),
    certified_date DATE,
    remarks TEXT,
    trainer_id INT NOT NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Exams
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    duration_minutes INT DEFAULT 30,
    passing_score INT DEFAULT 70,
    created_by INT,
    FOREIGN KEY (module_id) REFERENCES training_modules(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Questions (MCQ)
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option ENUM('A', 'B', 'C', 'D') NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Exam Results
CREATE TABLE IF NOT EXISTS exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainee_id INT NOT NULL,
    exam_id INT NOT NULL,
    score INT NOT NULL,
    max_score INT NOT NULL,
    status ENUM('pass', 'fail') NOT NULL,
    exam_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Induction Specific Score Sheet (Page 6 of 7)
CREATE TABLE IF NOT EXISTS induction_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainee_id INT NOT NULL,
    topic_1_score INT, -- About syrma - Oral
    topic_2_score INT, -- Safety - Written Test
    topic_3_score INT, -- PPE - Written Test
    topic_4_score INT, -- 5S - Written Test
    topic_5_score INT, -- ESD - Written Test
    topic_6_score INT, -- E-Module - Written Test
    trainer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trainee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Proctor Logs (Suspicious Activity)
CREATE TABLE IF NOT EXISTS proctor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    result_id INT NOT NULL,
    activity_type ENUM('tab_switch', 'multiple_faces', 'no_face', 'camera_off', 'other') NOT NULL,
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    FOREIGN KEY (result_id) REFERENCES exam_results(id) ON DELETE CASCADE
);

-- Feedback (Matches Official Layout)
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT, -- Optional link to specific course
    trainee_id INT NOT NULL,
    rating_overall ENUM('A', 'B', 'C', 'D') NOT NULL,
    rating_learning_skill ENUM('A', 'B', 'C', 'D'),
    rating_learning_knowledge ENUM('A', 'B', 'C', 'D'),
    rating_learning_attitude ENUM('A', 'B', 'C', 'D'),
    rating_explanation ENUM('A', 'B', 'C', 'D'), -- Faculties explanation
    rating_improvement ENUM('A', 'B', 'C', 'D'), -- Ability improvement
    rating_time ENUM('A', 'B', 'C', 'D'), -- Time management
    comments TEXT, -- Methodology comments
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainee_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Default Admin
INSERT IGNORE INTO users (username, password, full_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');
-- Default password: password
