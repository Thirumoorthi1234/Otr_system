<?php
require_once 'c:/xampp2/htdocs/otr/includes/config.php';
try {
    $stmt = $pdo->query("DESCRIBE feedback");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = "";
    foreach ($all as $col) {
        $out .= "Column: {$col['Field']}\n";
        foreach ($col as $k => $v) {
            if ($k !== 'Field') $out .= "  $k: $v\n";
        }
    }
    file_put_contents('c:/xampp2/htdocs/otr/schema_output.txt', $out);
    echo "Done";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
