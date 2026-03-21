<?php
// /tmp/seed_induction.php
require_once 'C:/xampp2/htdocs/otr/includes/config.php';

$checklist = [
    // Day 1
    ['day' => 1, 'section' => 'JOINING FORMALITIES', 'topic' => 'Joining Formalities', 'mins' => 90],
    ['day' => 1, 'section' => 'JOINING FORMALITIES', 'topic' => 'Self introduction', 'mins' => 90],
    ['day' => 1, 'section' => 'JOINING FORMALITIES', 'topic' => 'About SYRMA', 'mins' => 30],
    ['day' => 1, 'section' => 'JOINING FORMALITIES', 'topic' => 'Product and Process Details', 'mins' => 90],
    
    // Day 2
    ['day' => 2, 'section' => 'SAFETY', 'topic' => 'Basics of safety', 'hours' => 2],
    ['day' => 2, 'section' => 'SAFETY', 'topic' => 'Industrial safety', 'hours' => 2],
    ['day' => 2, 'section' => 'SAFETY', 'topic' => 'PPE', 'hours' => 2],
    ['day' => 2, 'section' => 'SAFETY', 'topic' => 'EHS', 'hours' => 2],
    ['day' => 2, 'section' => 'SAFETY', 'topic' => 'Safety E-module', 'hours' => 2],
    ['day' => 2, 'section' => 'SAFETY', 'topic' => 'Sexual Harassment', 'hours' => 2],
    
    ['day' => 2, 'section' => 'BASIC CONCEPTS', 'topic' => 'Kaizen', 'hours' => 3],
    ['day' => 2, 'section' => 'BASIC CONCEPTS', 'topic' => 'Lean Manufacturing', 'hours' => 3],
    ['day' => 2, 'section' => 'BASIC CONCEPTS', 'topic' => '6S', 'hours' => 3],
    ['day' => 2, 'section' => 'BASIC CONCEPTS', 'topic' => 'IPC - Basics Awareness', 'hours' => 3],
    ['day' => 2, 'section' => 'BASIC CONCEPTS', 'topic' => 'Industrial E-Module', 'hours' => 3],
    ['day' => 2, 'section' => 'BASIC CONCEPTS', 'topic' => 'ISO Standards', 'hours' => 3],
    ['day' => 2, 'section' => 'BASIC CONCEPTS', 'topic' => 'PCB Handling', 'hours' => 3],
    ['day' => 2, 'section' => 'BASIC CONCEPTS', 'topic' => 'ROHS', 'hours' => 3],
    
    ['day' => 2, 'section' => 'ESD TRAINING', 'topic' => 'Why ESD?', 'hours' => 2],
    ['day' => 2, 'section' => 'ESD TRAINING', 'topic' => 'Causes and Effect', 'hours' => 2],
    ['day' => 2, 'section' => 'ESD TRAINING', 'topic' => 'How to use ESD?', 'hours' => 2],
    ['day' => 2, 'section' => 'ESD TRAINING', 'topic' => 'How to check ESD?', 'hours' => 2],
    
    ['day' => 2, 'section' => 'FIRST AID TRAINING', 'topic' => 'Health checkup', 'hours' => 1],
    ['day' => 2, 'section' => 'FIRST AID TRAINING', 'topic' => 'Health Awareness', 'hours' => 1],
    
    // Day 3
    ['day' => 3, 'section' => 'DEPARTMENT DETAILS', 'topic' => 'Process Emodule', 'hours' => NULL],
    ['day' => 3, 'section' => 'DEPARTMENT DETAILS', 'topic' => 'Work Instruction Detail', 'hours' => NULL],
    ['day' => 3, 'section' => 'DEPARTMENT DETAILS', 'topic' => 'Product Handling Method', 'hours' => NULL],
    
    ['day' => 3, 'section' => 'ACTIVITIES', 'topic' => '5S Game', 'hours' => 2],
    ['day' => 3, 'section' => 'ACTIVITIES', 'topic' => 'Group Activity', 'hours' => 2],
    ['day' => 3, 'section' => 'ACTIVITIES', 'topic' => 'Multi Tasking', 'hours' => 2],
    ['day' => 3, 'section' => 'ACTIVITIES', 'topic' => 'Skill Competition', 'hours' => 2],
    
    ['day' => 3, 'section' => 'TEST - WRITTEN & ORAL', 'topic' => 'About syrma - Oral', 'hours' => 2],
    ['day' => 3, 'section' => 'TEST - WRITTEN & ORAL', 'topic' => 'Safety - Written Test', 'hours' => 2],
    ['day' => 3, 'section' => 'TEST - WRITTEN & ORAL', 'topic' => 'PPE - Written Test', 'hours' => 2],
    ['day' => 3, 'section' => 'TEST - WRITTEN & ORAL', 'topic' => '5S - Written Test', 'hours' => 2],
    ['day' => 3, 'section' => 'TEST - WRITTEN & ORAL', 'topic' => 'ESD - Written Test', 'hours' => 2],
    ['day' => 3, 'section' => 'TEST - WRITTEN & ORAL', 'topic' => 'E-Module - Written Test', 'hours' => 2],
];

try {
    // Check if progress exists before clearing topics
    $progressCount = $pdo->query("SELECT COUNT(*) FROM trainee_checklist_progress")->fetchColumn();
    
    if ($progressCount > 0) {
        echo "Skipping topic re-seed: Existing progress records found in trainee_checklist_progress. Clear them first if you want to re-seed the topics.";
    } else {
        $pdo->exec("DELETE FROM induction_checklist");
        $pdo->exec("ALTER TABLE induction_checklist AUTO_INCREMENT = 1");
        
        $stmt = $pdo->prepare("INSERT INTO induction_checklist (day_number, section_name, topic_name, estimated_hours, estimated_mins) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($checklist as $item) {
            $stmt->execute([
                $item['day'],
                $item['section'],
                $item['topic'],
                $item['hours'] ?? NULL,
                $item['mins'] ?? NULL
            ]);
        }
        echo "Checklist seeded successfully!";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
