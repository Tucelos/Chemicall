<?php
require_once __DIR__ . '/../src/db/db_connection.php';

try {
    // Adiciona as colunas matricula, email_secundario, status e cargo na tabela funcionario
    $sql = "ALTER TABLE funcionario 
            ADD COLUMN matricula VARCHAR(50) NULL AFTER nome,
            ADD COLUMN email_secundario VARCHAR(255) NULL AFTER email,
            ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'ativo' AFTER tipo,
            ADD COLUMN cargo VARCHAR(100) NULL AFTER status";
            
    $conn->exec($sql);
    echo "Tabela 'funcionario' alterada com sucesso! Novas colunas adicionadas.\n";
} catch (PDOException $e) {
    // Se as colunas já existirem, ou der erro, avisa
    echo "Erro ou colunas já existentes: " . $e->getMessage() . "\n";
}
?>
