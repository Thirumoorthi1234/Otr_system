<?php
require 'includes/config.php';
$stmt = $pdo->query('DESCRIBE training_stages');
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($r);
$stmt = $pdo->query('SELECT * FROM training_stages ORDER BY id DESC LIMIT 5');
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($r);
