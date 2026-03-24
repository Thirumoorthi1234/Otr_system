<?php
require 'includes/config.php';
ob_start();
$stmt = $pdo->query("SHOW COLUMNS FROM feedback");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
$stmt = $pdo->query("SHOW COLUMNS FROM exam_results");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
$stmt = $pdo->query("SHOW COLUMNS FROM assignments");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
file_put_contents('test_cols_out.txt', ob_get_clean());
