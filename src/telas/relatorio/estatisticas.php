<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
$auth->exigirGestao('../dashboard/index.php');

// Get period from GET request
$periodo = $_GET['periodo'] ?? '30';
$dateCondition = "";

if ($periodo !== 'all') {
    $days = (int)$periodo;
    $dateCondition = " AND m.data_hora >= DATE_SUB(NOW(), INTERVAL $days DAY)";
}

// 1. Consultar os reagentes mais consumidos (baseado em saídas na tabela movimentacoes)
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
        $dataMore[] = [$row['nome'], (float)$row['total_consumido']];
    }
} catch (PDOException $e) {
    // Sem registrar, uma falha do banco fica indistinguível de
    // "nenhum consumo no período" na tela.
    error_log('[Chemicall] Falha ao consultar reagentes mais consumidos: ' . $e->getMessage());
    $dataMore = [];
}

// 2. Consultar os reagentes menos consumidos (com saídas registradas)
try {
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
        $dataLess[] = [$row['nome'], (float)$row['total_consumido']];
    }
} catch (PDOException $e) {
    error_log('[Chemicall] Falha ao consultar reagentes menos consumidos: ' . $e->getMessage());
    $dataLess = [];
}

// 3. Consultar proporção de controlados vs. não controlados no estoque ativo
try {
    $sqlControlados = "SELECT controlado, COUNT(*) as total FROM reagentes WHERE ativo = 1 GROUP BY controlado";
    $stmtControlados = $conn->query($sqlControlados);
    $dataControlados = [];
    while ($row = $stmtControlados->fetch(PDO::FETCH_ASSOC)) {
        $label = $row['controlado'] ? 'Produtos Controlados' : 'Produtos Livres';
        $dataControlados[] = [$label, (int)$row['total']];
    }
} catch (PDOException $e) {
    error_log('[Chemicall] Falha ao consultar proporção de controlados: ' . $e->getMessage());
    $dataControlados = [];
}

// 4. Consultar os motivos das saídas (retiradas)
try {
    $sqlMotivos = "SELECT motivo_retirada, COUNT(*) as total 
                   FROM movimentacoes m
                   WHERE m.tipo_movimentacao = 'saida' AND m.motivo_retirada IS NOT NULL AND m.motivo_retirada != '' $dateCondition
                   GROUP BY m.motivo_retirada";
    $stmtMotivos = $conn->query($sqlMotivos);
    $dataMotivos = [];
    while ($row = $stmtMotivos->fetch(PDO::FETCH_ASSOC)) {
        $motivo = $row['motivo_retirada'];
        if ($motivo === 'uso') $motivo = 'Uso em aula/pesquisa';
        elseif ($motivo === 'vencimento') $motivo = 'Vencimento';
        $dataMotivos[] = [ucfirst($motivo), (int)$row['total']];
    }
} catch (PDOException $e) {
    error_log('[Chemicall] Falha ao consultar motivos das retiradas: ' . $e->getMessage());
    $dataMotivos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas - Chemicall</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .main-container {
            padding-top: 100px;
            padding-bottom: 50px;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.05);
            min-height: 480px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        h1 {
            color: #006233;
            font-weight: bold;
        }
        .section-title {
            color: #333;
            font-weight: 600;
            font-size: 1.25rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="container main-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-chart-pie me-2"></i> Estatísticas de Consumo e Estoque</h1>
            <p class="text-muted">Analise dados analíticos de consumo dos reagentes e distribuição do estoque.</p>
        </div>

        <!-- Seletor de Período Integrado -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-6">
                <div class="card p-3 shadow-sm border-0 rounded-4">
                    <form action="estatisticas.php" method="GET" class="d-flex align-items-center gap-2">
                        <label for="periodo" class="form-label fw-bold mb-0 text-nowrap" style="color: #006233;">Período:</label>
                        <select name="periodo" id="periodo" class="form-select rounded-3">
                            <option value="7" <?php echo $periodo === '7' ? 'selected' : ''; ?>>Última Semana</option>
                            <option value="30" <?php echo $periodo === '30' ? 'selected' : ''; ?>>Último Mês</option>
                            <option value="90" <?php echo $periodo === '90' ? 'selected' : ''; ?>>Últimos 3 Meses</option>
                            <option value="180" <?php echo $periodo === '180' ? 'selected' : ''; ?>>Últimos 6 Meses</option>
                            <option value="365" <?php echo $periodo === '365' ? 'selected' : ''; ?>>Último Ano</option>
                            <option value="all" <?php echo $periodo === 'all' ? 'selected' : ''; ?>>Todo o Período</option>
                        </select>
                        <button type="submit" class="btn btn-success fw-bold text-nowrap px-4 rounded-3" style="background-color: #006233; border-color: #006233;">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Linha 1: Consumos -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <div>
                        <h4 class="section-title mb-3 text-success"><i class="fas fa-arrow-trend-up me-2"></i>Reagentes Mais Consumidos</h4>
                        <div id="chart_more" style="height: 350px; width: 100%;"></div>
                    </div>
                    <?php if (empty($dataMore)): ?>
                        <p class="text-center text-muted my-auto">Sem dados de consumo registrados no período.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <div>
                        <h4 class="section-title mb-3 text-danger"><i class="fas fa-arrow-trend-down me-2"></i>Reagentes Menos Consumidos</h4>
                        <div id="chart_less" style="height: 350px; width: 100%;"></div>
                    </div>
                    <?php if (empty($dataLess)): ?>
                        <p class="text-center text-muted my-auto">Sem dados de consumo registrados no período.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Linha 2: Proporções e Motivos -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <div>
                        <h4 class="section-title mb-3 text-primary"><i class="fas fa-shield-halved me-2"></i>Controle de Reagentes (Estoque Ativo)</h4>
                        <div id="chart_control" style="height: 350px; width: 100%;"></div>
                    </div>
                    <?php if (empty($dataControlados)): ?>
                        <p class="text-center text-muted my-auto">Nenhum reagente ativo no estoque para análise.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <div>
                        <h4 class="section-title mb-3 text-warning"><i class="fas fa-question-circle me-2"></i>Principais Motivos de Retirada (Saídas)</h4>
                        <div id="chart_motivos" style="height: 350px; width: 100%;"></div>
                    </div>
                    <?php if (empty($dataMotivos)): ?>
                        <p class="text-center text-muted my-auto">Nenhuma retirada registrada no período para exibir motivos.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <!-- Google Charts -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', { packages: ['corechart'] });
        
        const dataMore = <?php echo json_encode($dataMore); ?>;
        const dataLess = <?php echo json_encode($dataLess); ?>;
        const dataControlados = <?php echo json_encode($dataControlados); ?>;
        const dataMotivos = <?php echo json_encode($dataMotivos); ?>;

        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            // 1. Mais Consumidos
            if (dataMore.length > 0) {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'Reagente');
                data.addColumn('number', 'Quantidade');
                data.addRows(dataMore);

                const options = {
                    legend: { position: 'none' },
                    colors: ['#198754'],
                    hAxis: { title: 'Reagente', textStyle: { fontSize: 10 } },
                    vAxis: { title: 'Quantidade Total' },
                    animation: { startup: true, duration: 800, easing: 'out' }
                };
                const chart = new google.visualization.ColumnChart(document.getElementById('chart_more'));
                chart.draw(data, options);
            }

            // 2. Menos Consumidos
            if (dataLess.length > 0) {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'Reagente');
                data.addColumn('number', 'Quantidade');
                data.addRows(dataLess);

                const options = {
                    legend: { position: 'none' },
                    colors: ['#dc3545'],
                    hAxis: { title: 'Reagente', textStyle: { fontSize: 10 } },
                    vAxis: { title: 'Quantidade Total' },
                    animation: { startup: true, duration: 800, easing: 'out' }
                };
                const chart = new google.visualization.ColumnChart(document.getElementById('chart_less'));
                chart.draw(data, options);
            }

            // 3. Proporção de Controlados
            if (dataControlados.length > 0) {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'Tipo');
                data.addColumn('number', 'Quantidade');
                data.addRows(dataControlados);

                const options = {
                    pieHole: 0.4,
                    colors: ['#dc3545', '#0d6efd'],
                    chartArea: { width: '90%', height: '80%' },
                    legend: { position: 'bottom' }
                };
                const chart = new google.visualization.PieChart(document.getElementById('chart_control'));
                chart.draw(data, options);
            }

            // 4. Motivos de Retirada
            if (dataMotivos.length > 0) {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'Motivo');
                data.addColumn('number', 'Total');
                data.addRows(dataMotivos);

                const options = {
                    colors: ['#ffc107', '#fd7e14', '#6f42c1', '#17a2b8'],
                    chartArea: { width: '90%', height: '80%' },
                    legend: { position: 'bottom' }
                };
                const chart = new google.visualization.PieChart(document.getElementById('chart_motivos'));
                chart.draw(data, options);
            }
        }
    </script>
</body>
</html>
