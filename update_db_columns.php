<?php
require 'includes/config.php';

$columns = [
    'old_emp_id' => 'VARCHAR(50)',
    'reporting_to_code' => 'VARCHAR(50)',
    'reporting_to_name' => 'VARCHAR(100)',
    'date_of_re_joining' => 'DATE',
    'uid_emp_no' => 'VARCHAR(50)',
    'business' => 'VARCHAR(50)',
    'region' => 'VARCHAR(50)',
    'location' => 'VARCHAR(100)',
    'work_location' => 'VARCHAR(100)',
    'org_unit_1' => 'VARCHAR(100)',
    'org_unit_2' => 'VARCHAR(100)',
    'roll' => 'VARCHAR(50)',
    'sub_category' => 'VARCHAR(50)',
    'payroll_group' => 'VARCHAR(50)',
    'gender' => 'ENUM("Male", "Female", "Other")',
    'home_town' => 'VARCHAR(100)'
];

foreach ($columns as $column => $type) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN $column $type DEFAULT NULL");
        echo "Added column: $column\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') { // Column already exists
            echo "Column exists: $column\n";
        } else {
            echo "Error adding column $column: " . $e->getMessage() . "\n";
        }
    }
}
echo "Database update complete.\n";
