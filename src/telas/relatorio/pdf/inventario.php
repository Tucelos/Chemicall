<?php
require_once __DIR__ . '/../../../controllers/AuthController.php';
require_once __DIR__ . '/../../../db/db_connection.php';
require_once 'dompdf/autoload.inc.php';

$auth = new AuthController($conn);
$auth->exigirGestao('../../dashboard/index.php');

use Dompdf\Dompdf;

// Helper para formatar a quantidade
function formatarQuantidade($quantidade, $unidade, $capacidade = null, $unidadeCapacidade = 'ml') {
    if (empty($unidade)) {
        return $quantidade;
    }
    $unidadePlural = $unidade;
    switch (strtolower($unidade)) {
        case 'galão': $unidadePlural = $quantidade > 1 ? 'Galões' : 'Galão'; break;
        case 'frasco': $unidadePlural = $quantidade > 1 ? 'Frascos' : 'Frasco'; break;
        case 'litro': $unidadePlural = $quantidade > 1 ? 'Litros' : 'Litro'; break;
        case 'ml': $unidadePlural = 'mL'; break;
        case 'kg': $unidadePlural = 'Kg'; break;
        case 'g': $unidadePlural = 'g'; break;
        case 'mg': $unidadePlural = 'mg'; break;
    }
    if (!empty($capacidade) && $capacidade > 0) {
        $cap = (float)$capacidade;
        return "{$quantidade} {$unidadePlural} ({$cap} {$unidadeCapacidade})";
    }
    return "{$quantidade} {$unidadePlural}";
}

// 1. Fetch Current Stock based on Filter Checkboxes and Bounds
$incluirAtivos = isset($_GET['incluir_ativos']) ? (int)$_GET['incluir_ativos'] : 0;
$incluirEsgotados = isset($_GET['incluir_esgotados']) ? (int)$_GET['incluir_esgotados'] : 0;
$apenasVencidos = isset($_GET['apenas_vencidos']) ? (int)$_GET['apenas_vencidos'] : 0;
$apenasControlados = isset($_GET['apenas_controlados']) ? (int)$_GET['apenas_controlados'] : 0;
$estoqueMin = isset($_GET['estoque_min']) && $_GET['estoque_min'] !== '' ? (float)$_GET['estoque_min'] : null;
$estoqueMax = isset($_GET['estoque_max']) && $_GET['estoque_max'] !== '' ? (float)$_GET['estoque_max'] : null;
$notaFiscal = isset($_GET['nota_fiscal']) && $_GET['nota_fiscal'] !== '' && $_GET['nota_fiscal'] !== 'todas' ? $_GET['nota_fiscal'] : null;

$sqlStock = "SELECT * FROM reagentes WHERE ativo = 1";
$paramsStock = [];

// Filtro de Status
if ($incluirAtivos && !$incluirEsgotados) {
    $sqlStock .= " AND quantidade > 0";
} elseif (!$incluirAtivos && $incluirEsgotados) {
    $sqlStock .= " AND quantidade = 0";
} elseif (!$incluirAtivos && !$incluirEsgotados) {
    // Se nada foi marcado, assume ambos
    $incluirAtivos = 1;
    $incluirEsgotados = 1;
}

// Filtro de Vencidos
if ($apenasVencidos) {
    $sqlStock .= " AND validade < CURDATE()";
}

// Filtro de Controlados
if ($apenasControlados) {
    $sqlStock .= " AND controlado = 1";
}

// Filtro de Estoque Mínimo e Máximo
if ($estoqueMin !== null) {
    $sqlStock .= " AND quantidade >= :estoqueMin";
    $paramsStock[':estoqueMin'] = $estoqueMin;
}
if ($estoqueMax !== null) {
    $sqlStock .= " AND quantidade <= :estoqueMax";
    $paramsStock[':estoqueMax'] = $estoqueMax;
}

// Filtro de Nota Fiscal
if ($notaFiscal !== null) {
    $sqlStock .= " AND numero_nota_fiscal = :notaFiscal";
    $paramsStock[':notaFiscal'] = $notaFiscal;
}

$sqlStock .= " ORDER BY nome ASC, validade ASC";

try {
    $stmtStock = $conn->prepare($sqlStock);
    $stmtStock->execute($paramsStock);
    $reagentes = $stmtStock->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // A mensagem do PDO traz nomes de tabela e coluna: só vai para o log.
    chemicall_fail('Falha ao buscar estoque para o relatório: ' . $e->getMessage());
}

// 2. Fetch Movements Log based on Period, Filters and Bounds
$periodo = $_GET['periodo'] ?? '30'; // Default to 30 days
$dataInicio = '';
$dataFim = '';
$periodoTexto = '';
$paramsLog = [];

if ($periodo === 'custom') {
    $dataInicioCustom = $_GET['data_inicio_custom'] ?? '';
    $dataFimCustom = $_GET['data_fim_custom'] ?? '';
    
    if (!empty($dataInicioCustom)) {
        $dataInicio = date('Y-m-d 00:00:00', strtotime($dataInicioCustom));
    } else {
        $dataInicio = '1970-01-01 00:00:00';
    }
    
    if (!empty($dataFimCustom)) {
        $dataFim = date('Y-m-d 23:59:59', strtotime($dataFimCustom));
    } else {
        $dataFim = date('Y-m-d H:i:s');
    }
    
    $periodoTexto = "Período Personalizado (" . date('d/m/Y', strtotime($dataInicio)) . " até " . date('d/m/Y', strtotime($dataFim)) . ")";
} else {
    $dataFim = date('Y-m-d H:i:s');
    if ($periodo === 'all') {
        $dataInicio = '1970-01-01 00:00:00';
        $periodoTexto = 'Todo o Período';
    } else {
        $dias = (int)$periodo;
        $dataInicio = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
        $periodoTexto = "Últimos {$dias} dias";
    }
}

try {
    $sqlLog = "SELECT m.*, r.nome as reagente_nome, r.controlado, r.numero_nota_fiscal, r.unidade_medida, r.capacidade_medida, r.unidade_capacidade, f.nome as usuario_nome 
               FROM movimentacoes m 
               LEFT JOIN reagentes r ON m.reagente_id = r.id 
               LEFT JOIN funcionario f ON m.funcionario_id = f.cod_funcionario 
               WHERE m.data_hora >= :dataInicio AND m.data_hora <= :dataFim";
               
    $paramsLog[':dataInicio'] = $dataInicio;
    $paramsLog[':dataFim'] = $dataFim;

    if ($incluirAtivos && !$incluirEsgotados) {
        $sqlLog .= " AND r.quantidade > 0";
    } elseif (!$incluirAtivos && $incluirEsgotados) {
        $sqlLog .= " AND (r.quantidade = 0 OR m.tipo_movimentacao = 'exclusao')";
    }

    if ($apenasVencidos) {
        $sqlLog .= " AND r.validade < CURDATE()";
    }

    if ($apenasControlados) {
        $sqlLog .= " AND r.controlado = 1";
    }

    if ($estoqueMin !== null) {
        $sqlLog .= " AND r.quantidade >= :estoqueMin";
        $paramsLog[':estoqueMin'] = $estoqueMin;
    }
    if ($estoqueMax !== null) {
        $sqlLog .= " AND r.quantidade <= :estoqueMax";
        $paramsLog[':estoqueMax'] = $estoqueMax;
    }

    if ($notaFiscal !== null) {
        $sqlLog .= " AND r.numero_nota_fiscal = :notaFiscal";
        $paramsLog[':notaFiscal'] = $notaFiscal;
    }

    $sqlLog .= " ORDER BY m.data_hora DESC";
    
    $stmtLog = $conn->prepare($sqlLog);
    $stmtLog->execute($paramsLog);
    $movimentacoes = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    chemicall_fail('Falha ao buscar movimentações para o relatório: ' . $e->getMessage());
}

// 3. Montar Texto Dinâmico de Filtros Ativos
$filtrosAtivos = [];
if ($incluirAtivos) $filtrosAtivos[] = "Ativos";
if ($incluirEsgotados) $filtrosAtivos[] = "Esgotados";
if ($apenasVencidos) $filtrosAtivos[] = "Vencidos";
if ($apenasControlados) $filtrosAtivos[] = "Controlados";
if ($estoqueMin !== null) $filtrosAtivos[] = "Mín(" . $estoqueMin . ")";
if ($estoqueMax !== null) $filtrosAtivos[] = "Máx(" . $estoqueMax . ")";
if ($notaFiscal !== null) $filtrosAtivos[] = "NF(" . $notaFiscal . ")";

if (empty($filtrosAtivos)) {
    $filtroTexto = "Geral (Todos)";
} else {
    $filtroTexto = implode(" + ", $filtrosAtivos);
}

$dompdf = new Dompdf(['enable_remote' => true]);
$dompdf->setPaper('A4', 'landscape');

$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; color: #006233; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        th { background-color: #006233; color: white; padding: 8px; text-align: left; }
        td { border-bottom: 1px solid #ddd; padding: 6px; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .page-break { page-break-before: always; }
        .section-title { color: #006233; border-bottom: 2px solid #006233; padding-bottom: 5px; margin-top: 30px; }
        .badge { padding: 3px 6px; border-radius: 4px; color: white; font-size: 10px; }
        .bg-entrada { background-color: #198754; }
        .bg-saida { background-color: #dc3545; }
        .bg-criacao { background-color: #0d6efd; }
        .bg-edicao { background-color: #ffc107; color: black; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Chemicall</h1>
        <h2>Relatório de Estoque e Movimentações</h2>
        <h3>Foco: ' . $filtroTexto . '</h3>
        <p>Gerado em: ' . date('d/m/Y H:i') . '</p>
    </div>

    <h3 class="section-title">1. Estado do Estoque (' . $filtroTexto . ')</h3>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Fórmula</th>
                <th>Fabricante</th>
                <th>Nota Fiscal</th>
                <th>Controlado</th>
                <th>Validade</th>
                <th>Qtd</th>
            </tr>
        </thead>
        <tbody>';

if (empty($reagentes)) {
    $html .= '<tr><td colspan="7" style="text-align:center;">Nenhum reagente encontrado com o filtro selecionado.</td></tr>';
} else {
    foreach ($reagentes as $r) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($r['nome']) . '</td>
                <td>' . htmlspecialchars($r['formula_quimica']) . '</td>
                <td>' . htmlspecialchars($r['fabricante']) . '</td>
                <td>' . htmlspecialchars($r['numero_nota_fiscal'] ?? '-') . '</td>
                <td>' . ($r['controlado'] ? 'Sim' : 'Não') . '</td>
                <td>' . date('d/m/Y', strtotime($r['validade'])) . '</td>
                <td>' . formatarQuantidade($r['quantidade'], $r['unidade_medida'], $r['capacidade_medida'], $r['unidade_capacidade']) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>

    <div class="page-break"></div>

    <h3 class="section-title">2. Histórico de Movimentações - ' . $filtroTexto . ' (' . $periodoTexto . ')</h3>
    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Reagente</th>
                <th>Nota Fiscal</th>
                <th>Controlado</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Qtd</th>
            </tr>
        </thead>
        <tbody>';

if (empty($movimentacoes)) {
    $html .= '<tr><td colspan="7" style="text-align:center;">Nenhuma movimentação encontrada neste período com o filtro selecionado.</td></tr>';
} else {
    foreach ($movimentacoes as $m) {
        $tipoClass = '';
        $tipoLabel = '';
        switch($m['tipo_movimentacao']) {
            case 'entrada': $tipoClass = 'bg-entrada'; $tipoLabel = 'Entrada'; break;
            case 'saida': $tipoClass = 'bg-saida'; $tipoLabel = 'Saída'; break;
            case 'criacao': $tipoClass = 'bg-criacao'; $tipoLabel = 'Criação'; break;
            case 'edicao': $tipoClass = 'bg-edicao'; $tipoLabel = 'Edição'; break;
            case 'exclusao': $tipoClass = 'bg-saida'; $tipoLabel = 'Exclusão'; break;
            default: $tipoClass = 'bg-secondary'; $tipoLabel = ucfirst($m['tipo_movimentacao']);
        }

        $motivoTexto = '';
        if ($m['tipo_movimentacao'] === 'saida' && !empty($m['motivo_retirada'])) {
            $motivoTexto = '<br><small style="color: #666; font-size: 0.8em;">Motivo: ' . htmlspecialchars($m['motivo_retirada']) . '</small>';
        }

        $html .= '
            <tr>
                <td>' . date('d/m/Y H:i', strtotime($m['data_hora'])) . '</td>
                <td>' . htmlspecialchars($m['reagente_nome'] ?? 'Reagente Excluído') . '</td>
                <td>' . htmlspecialchars($m['numero_nota_fiscal'] ?? '-') . '</td>
                <td>' . ($m['controlado'] ? 'Sim' : 'Não') . '</td>
                <td>' . htmlspecialchars($m['usuario_nome'] ?? 'Usuário Desconhecido') . '</td>
                <td><span class="badge ' . $tipoClass . '">' . $tipoLabel . '</span>' . $motivoTexto . '</td>
                <td>' . formatarQuantidade($m['quantidade'], $m['unidade_medida'], $m['capacidade_medida'], $m['unidade_capacidade']) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>';

// Só exibe a seção 3 se o relatório já não for exclusivamente de controlados (evita redundância)
if (!$apenasControlados) {
    $html .= '
    <div class="page-break"></div>

    <h3 class="section-title">3. Histórico de Movimentações (Apenas Produtos Controlados no período)</h3>
    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Reagente</th>
                <th>Nota Fiscal</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Qtd</th>
            </tr>
        </thead>
        <tbody>';

    $movimentacoesControladas = array_filter($movimentacoes, function($m) {
        return $m['controlado'] == 1;
    });

    if (empty($movimentacoesControladas)) {
        $html .= '<tr><td colspan="6" style="text-align:center;">Nenhuma movimentação de produto controlado encontrada neste período com o filtro selecionado.</td></tr>';
    } else {
        foreach ($movimentacoesControladas as $m) {
            $tipoClass = '';
            $tipoLabel = '';
            switch($m['tipo_movimentacao']) {
                case 'entrada': $tipoClass = 'bg-entrada'; $tipoLabel = 'Entrada'; break;
                case 'saida': $tipoClass = 'bg-saida'; $tipoLabel = 'Saída'; break;
                case 'criacao': $tipoClass = 'bg-criacao'; $tipoLabel = 'Criação'; break;
                case 'edicao': $tipoClass = 'bg-edicao'; $tipoLabel = 'Edição'; break;
                case 'exclusao': $tipoClass = 'bg-saida'; $tipoLabel = 'Exclusão'; break;
                default: $tipoClass = 'bg-secondary'; $tipoLabel = ucfirst($m['tipo_movimentacao']);
            }

            $motivoTexto = '';
            if ($m['tipo_movimentacao'] === 'saida' && !empty($m['motivo_retirada'])) {
                $motivoTexto = '<br><small style="color: #666; font-size: 0.8em;">Motivo: ' . htmlspecialchars($m['motivo_retirada']) . '</small>';
            }

            $html .= '
                <tr>
                    <td>' . date('d/m/Y H:i', strtotime($m['data_hora'])) . '</td>
                    <td>' . htmlspecialchars($m['reagente_nome'] ?? 'Reagente Excluído') . '</td>
                    <td>' . htmlspecialchars($m['numero_nota_fiscal'] ?? '-') . '</td>
                    <td>' . htmlspecialchars($m['usuario_nome'] ?? 'Usuário Desconhecido') . '</td>
                    <td><span class="badge ' . $tipoClass . '">' . $tipoLabel . '</span>' . $motivoTexto . '</td>
                    <td>' . formatarQuantidade($m['quantidade'], $m['unidade_medida'], $m['capacidade_medida'], $m['unidade_capacidade']) . '</td>
                </tr>';
        }
    }

    $html .= '
            </tbody>
        </table>';
}

$html .= '
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("relatorio_chemicall.pdf", ["Attachment" => false]);
?>