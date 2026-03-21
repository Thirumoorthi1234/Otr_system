<?php
require_once 'includes/config.php';
try {
    $pdo->exec('ALTER TABLE users ADD COLUMN dol DATE DEFAULT NULL AFTER doj');
    echo 'Success';
} catch (Exception $e) {
    echo $e->getMessage();
}
