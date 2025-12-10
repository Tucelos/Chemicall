<?php
require_once '../../../../src/db/db_connection.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// 1. Fetch Current Stock
try {
    $sqlStock = "SELECT * FROM reagentes ORDER BY nome ASC";
    $stmtStock = $conn->query($sqlStock);
    $reagentes = $stmtStock->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar estoque: " . $e->getMessage());
}

// 2. Fetch Movements Log based on Period
$periodo = $_GET['periodo'] ?? '30'; // Default to 30 days
$dataInicio = '';
$periodoTexto = '';

if ($periodo === 'all') {
    $dataInicio = '1970-01-01 00:00:00';
    $periodoTexto = 'Todo o Período';
} else {
    $dias = (int)$periodo;
    $dataInicio = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
    $periodoTexto = "Últimos {$dias} dias";
}

try {
    $sqlLog = "SELECT m.*, r.nome as reagente_nome, r.controlado, r.numero_nota_fiscal, f.nome as usuario_nome 
               FROM movimentacoes m 
               LEFT JOIN reagentes r ON m.reagente_id = r.id 
               LEFT JOIN funcionario f ON m.funcionario_id = f.cod_funcionario 
               WHERE m.data_hora >= :dataInicio 
               ORDER BY m.data_hora DESC";
    
    $stmtLog = $conn->prepare($sqlLog);
    $stmtLog->execute([':dataInicio' => $dataInicio]);
    $movimentacoes = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar movimentações: " . $e->getMessage());
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
        <p>Gerado em: ' . date('d/m/Y H:i') . '</p>
    </div>

    <h3 class="section-title">1. Estoque Atual</h3>
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
    $html .= '<tr><td colspan="7" style="text-align:center;">Nenhum reagente em estoque.</td></tr>';
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
                <td>' . htmlspecialchars($r['quantidade']) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>

    <div class="page-break"></div>

    <h3 class="section-title">2. Histórico de Movimentações (' . $periodoTexto . ')</h3>
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
    $html .= '<tr><td colspan="7" style="text-align:center;">Nenhuma movimentação encontrada neste período.</td></tr>';
} else {
    foreach ($movimentacoes as $m) {
        $tipoClass = '';
        $tipoLabel = '';
        switch($m['tipo_movimentacao']) {
            case 'entrada': $tipoClass = 'bg-entrada'; $tipoLabel = 'Entrada'; break;
            case 'saida': $tipoClass = 'bg-saida'; $tipoLabel = 'Saída'; break;
            case 'criacao': $tipoClass = 'bg-criacao'; $tipoLabel = 'Criação'; break;
            case 'edicao': $tipoClass = 'bg-edicao'; $tipoLabel = 'Edição'; break;
            default: $tipoClass = 'bg-secondary'; $tipoLabel = $m['tipo_movimentacao'];
        }

        $html .= '
            <tr>
                <td>' . date('d/m/Y H:i', strtotime($m['data_hora'])) . '</td>
                <td>' . htmlspecialchars($m['reagente_nome'] ?? 'Reagente Excluído') . '</td>
                <td>' . htmlspecialchars($m['numero_nota_fiscal'] ?? '-') . '</td>
                <td>' . ($m['controlado'] ? 'Sim' : 'Não') . '</td>
                <td>' . htmlspecialchars($m['usuario_nome'] ?? 'Usuário Desconhecido') . '</td>
                <td><span class="badge ' . $tipoClass . '">' . $tipoLabel . '</span></td>
                <td>' . htmlspecialchars($m['quantidade']) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>
    </table>

    <div class="page-break"></div>

    <h3 class="section-title">3. Histórico de Movimentações (Apenas Produtos Controlados)</h3>
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
    $html .= '<tr><td colspan="6" style="text-align:center;">Nenhuma movimentação de produto controlado encontrada neste período.</td></tr>';
} else {
    foreach ($movimentacoesControladas as $m) {
        $tipoClass = '';
        $tipoLabel = '';
        switch($m['tipo_movimentacao']) {
            case 'entrada': $tipoClass = 'bg-entrada'; $tipoLabel = 'Entrada'; break;
            case 'saida': $tipoClass = 'bg-saida'; $tipoLabel = 'Saída'; break;
            case 'criacao': $tipoClass = 'bg-criacao'; $tipoLabel = 'Criação'; break;
            case 'edicao': $tipoClass = 'bg-edicao'; $tipoLabel = 'Edição'; break;
            default: $tipoClass = 'bg-secondary'; $tipoLabel = $m['tipo_movimentacao'];
        }

        $html .= '
            <tr>
                <td>' . date('d/m/Y H:i', strtotime($m['data_hora'])) . '</td>
                <td>' . htmlspecialchars($m['reagente_nome'] ?? 'Reagente Excluído') . '</td>
                <td>' . htmlspecialchars($m['numero_nota_fiscal'] ?? '-') . '</td>
                <td>' . htmlspecialchars($m['usuario_nome'] ?? 'Usuário Desconhecido') . '</td>
                <td><span class="badge ' . $tipoClass . '">' . $tipoLabel . '</span></td>
                <td>' . htmlspecialchars($m['quantidade']) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("relatorio_chemicall.pdf", ["Attachment" => false]);
?>