<?php
require_once __DIR__ . '/db_connection.php';

try {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM reagentes LIKE 'ativo'");
    if ($check->rowCount() == 0) {
        // Add column if it doesn't exist
        $sql = "ALTER TABLE reagentes ADD COLUMN ativo TINYINT(1) DEFAULT 1 AFTER controlado";
        $conn->exec($sql);
        echo "Coluna 'ativo' adicionada com sucesso.\n";
    } else {
        echo "Coluna 'ativo' já existe.\n";
    }
} catch (PDOException $e) {
    echo "Erro ao adicionar coluna 'ativo': " . $e->getMessage() . "\n";
}
?>
