<?php
require_once 'includes/config.php';
$number = '9965022420';
$withCode = '91' . $number;

$stmt = $pdo->prepare("SELECT id, employee_id, full_name, mobile_number, status, role FROM users WHERE mobile_number LIKE ?");
$stmt->execute(['%' . $number . '%']);
$users = $stmt->fetchAll();

echo "Searching for number: $number\n";
echo "Results found: " . count($users) . "\n\n";

foreach ($users as $u) {
    print_r($u);
}
?>
