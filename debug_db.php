<?php
require_once 'includes/db_connect.php';

function describeTable($pdo, $tableName) {
    echo "Table: $tableName\n";
    $stmt = $pdo->query("DESCRIBE $tableName");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']} - Null: {$col['Null']} - Default: {$col['Default']}\n";
    }
    echo "\n";
}

try {
    describeTable($pdo, 'crops');
    describeTable($pdo, 'livestock');
    describeTable($pdo, 'inventory');
    describeTable($pdo, 'tasks');
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
