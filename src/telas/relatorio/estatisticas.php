<?php
require_once __DIR__ . '/../../db/db_connection.php';

// Get period from GET request
$periodo = $_GET['periodo'] ?? '30';
$dateCondition = "";

if ($periodo !== 'all') {
    $days = (int)$periodo;
    $dateCondition = " AND m.data_hora >= DATE_SUB(NOW(), INTERVAL $days DAY)";
}

// Consultar os reagentes mais consumidos (baseado em saídas na tabela movimentacoes)
try {
    $sqlMore = "SELECT r.nome, SUM(m.quantidade) as total_consumido 
                FROM movimentacoes m 
                JOIN reagentes r ON m.reagente_id = r.id 
                WHERE m.tipo_movimentacao = 'saida' $dateCondition
                GROUP BY r.id 
                ORDER BY total_consumido DESC 
                LIMIT 10";
    $stmtMore = $conn->query($sqlMore);
    $dataMore = [];
    while ($row = $stmtMore->fetch(PDO::FETCH_ASSOC)) {
        $dataMore[] = [$row['nome'], (int)$row['total_consumido']];
    }

    // Para menos consumidos, podemos pegar os que têm saídas mas em menor quantidade
    // Ou listar os que nunca saíram? Vamos listar os com menor saída registrada.
    $sqlLess = "SELECT r.nome, SUM(m.quantidade) as total_consumido 
                FROM movimentacoes m 
                JOIN reagentes r ON m.reagente_id = r.id 
                WHERE m.tipo_movimentacao = 'saida' $dateCondition
                GROUP BY r.id 
                ORDER BY total_consumido ASC 
                LIMIT 10";
    $stmtLess = $conn->query($sqlLess);
    $dataLess = [];
    while ($row = $stmtLess->fetch(PDO::FETCH_ASSOC)) {
        $dataLess[] = [$row['nome'], (int)$row['total_consumido']];
    }
} catch (PDOException $e) {
    $dataMore = [];
    $dataLess = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .chart-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #006233; margin-top: 30px; margin-bottom: 30px; }
    </style>
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="container">
        <h1 class="text-center"><i class="fas fa-chart-bar"></i> Estatísticas de Consumo</h1>
        <p class="text-center text-muted">
            Período: <?php 
                $labels = [
                    '7' => 'Última Semana',
                    '30' => 'Último Mês',
                    '90' => 'Últimos 3 Meses',
                    '180' => 'Últimos 6 Meses',
                    '365' => 'Último Ano',
                    'all' => 'Todo o Período'
                ];
                echo $labels[$periodo] ?? 'Personalizado';
            ?>
        </p>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="text-center text-success mb-4">Mais Consumidos</h3>
                    <div id="chart_more" style="height: 400px;"></div>
                    <?php if (empty($dataMore)): ?>
                        <p class="text-center text-muted mt-5">Sem dados de consumo registrados.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="text-center text-warning mb-4">Menos Consumidos</h3>
                    <div id="chart_less" style="height: 400px;"></div>
                    <?php if (empty($dataLess)): ?>
                        <p class="text-center text-muted mt-5">Sem dados de consumo registrados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', { packages: ['corechart'] });
        
        const dataMore = <?php echo json_encode($dataMore); ?>;
        const dataLess = <?php echo json_encode($dataLess); ?>;

        if (dataMore.length > 0 || dataLess.length > 0) {
            google.charts.setOnLoadCallback(drawCharts);
        }

        function drawCharts() {
            if (dataMore.length > 0) drawChart(dataMore, 'chart_more', 'Reagentes Mais Consumidos', '#006233');
            if (dataLess.length > 0) drawChart(dataLess, 'chart_less', 'Reagentes Menos Consumidos', '#ffc107');
        }

        function drawChart(dataArray, elementId, title, color) {
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Reagente');
            data.addColumn('number', 'Qtd Consumida');
            data.addRows(dataArray);

            const options = {
                title: title,
                legend: { position: 'none' },
                colors: [color],
                hAxis: { title: 'Reagente' },
                vAxis: { title: 'Quantidade' },
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            const chart = new google.visualization.ColumnChart(document.getElementById(elementId));
            chart.draw(data, options);
        }
    </script>
</body>
</html>
