<?php
require_once 'includes/config.php';

try {
    // 1. Ensure Modules exist
    $modules = [
        [1, 'Induction Training', 'Foundational training covering Safety, PPE, 5S, and ESD.', 'Induction'],
        [2, 'Practical Training-SDC', 'Practical skill development center training.', 'SDC'],
        [3, 'On the job training- shop floor', 'Hands-on training on the production floor.', 'OTJ']
    ];
    
    foreach ($modules as $m) {
        $stmt = $pdo->prepare("INSERT INTO training_modules (id, title, description, category) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), category=VALUES(category)");
        $stmt->execute($m);
    }

    // 2. Clear existing exams and questions for these specific ones to reset IDs
    $pdo->exec("DELETE FROM questions WHERE exam_id IN (1, 2, 3, 4)");
    $pdo->exec("DELETE FROM exams WHERE id IN (1, 2, 3, 4)");

    // 3. Insert Exams with fixed IDs
    $exams = [
        [1, 1, '5S Test', 15, 80],
        [2, 1, 'Safety Test', 15, 80],
        [3, 1, 'PPE Test', 15, 80],
        [4, 1, 'ESD Test', 15, 80]
    ];
    
    foreach ($exams as $e) {
        $stmt = $pdo->prepare("INSERT INTO exams (id, module_id, title, duration_minutes, passing_score) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($e);
    }

    // 4. Insert Questions for 5S Test
    $q_5s = [
        ['5S stands for', 'Sort, Set, Shine, Standardize and Sustain', 'Sort, Shine, Set, Sustain, Standardize', 'Shine, Spotless, Sanitize & Safety on Saturdays', 'NONE', 'A'],
        ['Who is responsible for 5S', 'Cleaning team and maintenance', 'Operators and cleaners', 'Cleaners, operators, maintenance and management', 'NONE', 'C'],
        ['Where to do 5S', '5S is best only done on the plant floor', '5S is best done in areas that are messy', '5S is best done on the plant floor, maintenance, shipping and offices', 'NONE', 'C'],
        ['When to do 5S', '5S is best done every day as part of good practices', '5S is best to be done when the plant is not busy', '5S is best done at the end of the day or before a shutdown', 'NONE', 'A'],
        ['Which one of these best describes 5S', 'Nothing out of place and nothing missing', 'A place for everything and everything in its place identified and ready for use', 'A home for everything', 'NONE', 'B']
    ];
    foreach ($q_5s as $q) {
        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (1, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($q);
    }

    // 5. Insert Questions for Safety Test
    $q_safety = [
        ['When you hear the evacuation alarm you must...', 'Stop to get your belongings', 'Call your supervisor', 'Await further instruction', 'Evacuate immediately to the emergency assembly location', 'D'],
        ['What factor is not relevant in the event of an emergency?', 'Who your fire wardens are', 'Completing an experiment', 'The Emergency Procedures', 'Where to assemble', 'B'],
        ['Who is the first person to inform if you have an incident or identify a hazard even if no-one is injured?', 'Your supervisor', 'The director', 'First aider', 'Reception', 'A'],
        ['If you are injured at work, no matter how insignificant it is, you must...', 'Go home', 'Report it to your supervisor', 'Go to the hospital', 'Tell your family', 'B'],
        ['Which of the following activities are considered "hot work"?', 'Welding', 'Cutting', 'Grinding Ferrous Metals', 'All of the Above', 'D']
    ];
    foreach ($q_safety as $q) {
        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (2, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($q);
    }

    // 6. Insert Questions for PPE Test
    $q_ppe = [
        ['PPE Stands for', 'Personal Product Equipment', 'Personal Protective Equipment', 'Personal Production Equipment', 'Personal Perfect Equipment', 'B'],
        ['Which of the following are considered PPE?', 'Safety glasses', 'Ear plugs', 'Gloves', 'All of the above', 'D'],
        ['Which of the following can cause a severe eye injury if the proper protection is not worn?', 'Flying metal chips', 'Nails', 'Chemicals', 'All of the above', 'D'],
        ['When working with chemicals, safety glasses offer adequate eye protection.', 'True', 'False', '', '', 'A'],
        ['PPE offers little or no protection if worn improperly.', 'True', 'False', '', '', 'A'],
        ['All gloves offer the same basic level of protection.', 'True', 'False', '', '', 'B'],
        ['How often should you inspect your PPE to ensure its in good condition and functioning properly?', 'Before each use', 'once a week', 'Once a month', 'When need', 'A']
    ];
    foreach ($q_ppe as $q) {
        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (3, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($q);
    }

    // 7. Insert Questions for ESD Test
    $q_esd = [
        ['Give Expansion of ESD', 'Electronic static Display', 'Electrical Static Display', 'Electro static Discharge', 'Electro Magnetic Discharge', 'C'],
        ['ESD Safe Materials for Human Body', 'Shoe, Gloves Helmet, Band.', 'ESD Shoe, ESD gloves, ESD Coat & Wrist', 'Safety Glass, Helmet, Wrist Watch', 'Face Mask, Safety Glass, Hand Gloves', 'B'],
        ['Function of wrist band', 'ground the charges in our body', 'to avoid shock', 'to reduce temperature in our body', 'All the above', 'A'],
        ['What is the purpose of ESD safe materials?', 'Maintain quality', 'Increase lifetime of the product', 'Avoid Latent failures', 'All the above', 'D'],
        ['List out the ESD Safe materails.', 'Wrist strap, ESD shoes, ESD apron', 'Normal shoes, cotton shirt', 'Plastic bags, rubber bands', 'NONE', 'A']
    ];
    foreach ($q_esd as $q) {
        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (4, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($q);
    }

    echo "Seeding completed successfully with fixed IDs!";
} catch (Exception $e) {
    echo "Error during seeding: " . $e->getMessage();
}
?>
