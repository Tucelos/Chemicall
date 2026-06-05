<?php
require_once 'src/db/db_connection.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS movimentacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reagente_id INT NOT NULL,
        funcionario_id INT NOT NULL,
        tipo_movimentacao VARCHAR(20) NOT NULL,
        quantidade INT NOT NULL,
        data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reagente_id) REFERENCES reagentes(id),
        FOREIGN KEY (funcionario_id) REFERENCES funcionario(cod_funcionario)
    )";
    
    $conn->exec($sql);
    echo "Table 'movimentacoes' created successfully.\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
