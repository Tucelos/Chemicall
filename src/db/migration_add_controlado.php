<?php
require_once __DIR__ . '/db_connection.php';

try {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM reagentes LIKE 'controlado'");
    if ($check->rowCount() == 0) {
        // Add column if it doesn't exist
        $sql = "ALTER TABLE reagentes ADD COLUMN controlado TINYINT(1) DEFAULT 0 AFTER quantidade";
        $conn->exec($sql);
        echo "Coluna 'controlado' adicionada com sucesso.\n";
    } else {
        echo "Coluna 'controlado' já existe.\n";
    }
} catch (PDOException $e) {
    echo "Erro ao adicionar coluna: " . $e->getMessage() . "\n";
}
?>
