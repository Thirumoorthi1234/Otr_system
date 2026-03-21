<?php
// restore_data.php
require_once 'includes/config.php';

try {
    $sql = file_get_contents('update_induction.sql');
    $pdo->exec($sql);
    echo "SUCCESS: Master data restored successfully.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
