<?php
require_once 'src/db/db_connection.php';

try {
    $sql = "SELECT m.*, f.nome as funcionario, r.nome as reagente 
            FROM movimentacoes m 
            JOIN funcionario f ON m.funcionario_id = f.cod_funcionario 
            JOIN reagentes r ON m.reagente_id = r.id 
            ORDER BY m.data_hora DESC LIMIT 10";
    
    $stmt = $conn->query($sql);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($logs) > 0) {
        echo "<h1>Últimas 10 Movimentações</h1>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Data/Hora</th><th>Funcionário</th><th>Reagente</th><th>Tipo</th><th>Qtd</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['data_hora']}</td>";
            echo "<td>{$log['funcionario']}</td>";
            echo "<td>{$log['reagente']}</td>";
            echo "<td>{$log['tipo_movimentacao']}</td>";
            echo "<td>{$log['quantidade']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Nenhuma movimentação registrada ainda.";
    }

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
