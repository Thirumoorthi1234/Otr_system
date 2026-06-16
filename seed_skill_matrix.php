<?php
/**
 * Seed: Default RFID Skill Matrix skills from Syrma SGS format
 * Creates a default report template with all skills pre-configured
 */
require_once __DIR__ . '/includes/config.php';

echo "=== Seeding Default Skill Matrix Skills ===\n\n";

try {
    // Check if default skills already exist (report_id = NULL means global template)
    $existing = $pdo->query("SELECT COUNT(*) FROM skill_matrix_skills WHERE report_id IS NULL")->fetchColumn();
    if ($existing > 0) {
        echo "[SKIP] Default skills already seeded ($existing skills found).\n";
        echo "To re-seed, run: DELETE FROM skill_matrix_skills WHERE report_id IS NULL\n";
        exit;
    }

    $skills = [
        // Process Knowledge → Basic Process Knowledge
        ['5S',                                   'Process Knowledge', 'Basic Process Knowledge',            4, 1],
        ['Safety/Emergency Preparation/stoppage', 'Process Knowledge', 'Basic Process Knowledge',            4, 2],
        ['SOPWI',                                'Process Knowledge', 'Basic Process Knowledge',            4, 3],
        ['Defect Identification',                'Process Knowledge', 'Basic Process Knowledge',            4, 4],
        ['Start Up Checks*',                     'Process Knowledge', 'Basic Process Knowledge',            4, 5],
        
        // Process Knowledge → Basic Operating Skill-Theoretical
        ['Production Data Entry*',               'Process Knowledge', 'Basic Operating Skill-Theoretical',  4, 6],
        ['Parts Handling',                       'Process Knowledge', 'Basic Operating Skill-Theoretical',  4, 7],
        
        // Process Knowledge → Knowledge/Awareness
        ['Drawing Reading',                      'Process Knowledge', 'Knowledge/Awareness',                4, 8],
        ['Visual Inspection*',                   'Process Knowledge', 'Knowledge/Awareness',                4, 9],
        ['RFID product Knowledge',               'Process Knowledge', 'Knowledge/Awareness',                4, 10],
        
        // Operating Knowledge (no sub-category)
        ['Visual Inspection*',                   'Operating Knowledge', '',                                  4, 11],
        ['Header Inspection*',                   'Operating Knowledge', '',                                  4, 12],
        ['Process audit*',                       'Operating Knowledge', '',                                  4, 13],
        ['Dimensional checking*',                'Operating Knowledge', '',                                  4, 14],
        ['Packing*',                             'Operating Knowledge', '',                                  4, 15],
        ['Out box audit*',                       'Operating Knowledge', '',                                  4, 16],
        ['Shipping audit*',                      'Operating Knowledge', '',                                  4, 17],
        ['Shop floor activity*',                 'Operating Knowledge', '',                                  4, 18],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO skill_matrix_skills (skill_name, category_group, sub_category, required_level, sort_order, report_id) 
        VALUES (?, ?, ?, ?, ?, NULL)
    ");

    foreach ($skills as $s) {
        $stmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4]]);
        echo "[OK] Added: {$s[1]} > {$s[2]} > {$s[0]}\n";
    }

    echo "\n=== Seeding Complete: " . count($skills) . " skills added ===\n";

} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
?>
