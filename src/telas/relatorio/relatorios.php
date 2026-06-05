<?php
include "../../db/db_connection.php";

// Verify admin or gestor access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userType = $_SESSION['user_type'] ?? '';
if (!$userType || ($userType !== 'admin' && $userType !== 'gestor')) {
    header('Location: ../dashboard/index.php');
    exit();
}

// Buscar notas fiscais únicas e válidas cadastradas no estoque para o seletor
try {
    $sqlNF = "SELECT DISTINCT numero_nota_fiscal FROM reagentes WHERE numero_nota_fiscal IS NOT NULL AND numero_nota_fiscal != '' ORDER BY numero_nota_fiscal ASC";
    $stmtNF = $conn->query($sqlNF);
    $notasFiscais = $stmtNF->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $notasFiscais = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Chemicall</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .main-container {
            padding-top: 100px;
            padding-bottom: 50px;
            max-width: 900px;
        }
        .report-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: none;
            padding: 30px;
        }
        .section-header {
            color: #006233;
            border-bottom: 2px solid #006233;
            padding-bottom: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .btn-generate {
            background-color: #006233;
            color: white;
            font-weight: bold;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.3s;
            border: none;
        }
        .btn-generate:hover {
            background-color: #004d28;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .form-check-input:checked {
            background-color: #006233;
            border-color: #006233;
        }
    </style>
</head>
<body>
    <?php include_once('../../componentes/header.php'); ?>

    <div class="container main-container">
        <div class="report-card">
            <div class="text-center mb-4">
                <i class="fas fa-file-pdf fa-4x text-success mb-3"></i>
                <h2 class="fw-bold" style="color: #006233;">Painel de Emissão de Relatórios</h2>
                <p class="text-muted">Personalize os filtros abaixo para gerar o relatório PDF do estoque e de movimentações.</p>
            </div>
            
            <form action="pdf/inventario.php" method="GET" target="_blank">
                
                <!-- 1. Status e Filtros Especiais -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h5 class="section-header"><i class="fas fa-filter"></i> 1. Status do Reagente</h5>
                        <div class="ps-2">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="incluir_ativos" value="1" id="chkAtivos" checked>
                                <label class="form-check-label fw-semibold" for="chkAtivos">
                                    Incluir Itens Ativos <span class="text-muted font-monospace">(Qtd > 0)</span>
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="incluir_esgotados" value="1" id="chkEsgotados">
                                <label class="form-check-label fw-semibold" for="chkEsgotados">
                                    Incluir Itens Esgotados <span class="text-muted font-monospace">(Qtd = 0)</span>
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="apenas_vencidos" value="1" id="chkVencidos">
                                <label class="form-check-label fw-semibold" for="chkVencidos">
                                    Apenas Itens Vencidos
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="apenas_controlados" value="1" id="chkControlados">
                                <label class="form-check-label fw-semibold" for="chkControlados">
                                    Apenas Itens Controlados
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="section-header"><i class="fas fa-sliders-h"></i> 2. Limites e Nota Fiscal</h5>
                        <div class="mb-3">
                            <label for="estoque_min" class="form-label fw-semibold">Quantidade mínima em estoque:</label>
                            <input type="number" name="estoque_min" id="estoque_min" class="form-control" placeholder="Ex: 5" min="0" step="any">
                        </div>
                        <div class="mb-3">
                            <label for="estoque_max" class="form-label fw-semibold">Quantidade máxima em estoque:</label>
                            <input type="number" name="estoque_max" id="estoque_max" class="form-control" placeholder="Ex: 100" min="0" step="any">
                        </div>
                        <div class="mb-3">
                            <label for="nota_fiscal" class="form-label fw-semibold">Nota Fiscal específica:</label>
                            <select name="nota_fiscal" id="nota_fiscal" class="form-select">
                                <option value="todas" selected>Todas as Notas Fiscais</option>
                                <?php foreach ($notasFiscais as $nf): ?>
                                    <option value="<?php echo htmlspecialchars($nf); ?>"><?php echo htmlspecialchars($nf); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- 2. Período das Movimentações -->
                <div class="mb-4">
                    <h5 class="section-header"><i class="fas fa-calendar-alt"></i> 3. Período das Movimentações</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="periodo" class="form-label fw-semibold">Período de movimentação:</label>
                            <select name="periodo" id="periodo" class="form-select" required>
                                <option value="7">Última Semana</option>
                                <option value="30" selected>Último Mês</option>
                                <option value="90">Últimos 3 Meses</option>
                                <option value="180">Últimos 6 Meses</option>
                                <option value="365">Último Ano</option>
                                <option value="all">Todo o Período</option>
                                <option value="custom">Personalizado (selecionar datas)</option>
                            </select>
                        </div>
                        
                        <!-- Inputs de Período Personalizado (Exibidos condicionalmente) -->
                        <div class="col-md-6 d-none" id="customDatesContainer">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label for="data_inicio_custom" class="form-label fw-semibold">Data Inicial:</label>
                                    <input type="date" name="data_inicio_custom" id="data_inicio_custom" class="form-control">
                                </div>
                                <div class="col-6 mb-3">
                                    <label for="data_fim_custom" class="form-label fw-semibold">Data Final:</label>
                                    <input type="date" name="data_fim_custom" id="data_fim_custom" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-generate w-100 mt-2">
                    <i class="fas fa-file-pdf me-2"></i> GERAR RELATÓRIO PDF
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script de controle de datas personalizadas -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const periodoSelect = document.getElementById('periodo');
            const customDatesContainer = document.getElementById('customDatesContainer');
            const dataInicio = document.getElementById('data_inicio_custom');
            const dataFim = document.getElementById('data_fim_custom');

            periodoSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customDatesContainer.classList.remove('d-none');
                    dataInicio.setAttribute('required', 'required');
                    dataFim.setAttribute('required', 'required');
                } else {
                    customDatesContainer.classList.add('d-none');
                    dataInicio.removeAttribute('required');
                    dataFim.removeAttribute('required');
                }
            });
        });
    </script>
</body>
</html>