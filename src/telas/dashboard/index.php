<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../db/db_connection.php';

require_once __DIR__ . '/../../controllers/ReagenteController.php';

$auth = new AuthController($conn);
$auth->exigirLogin('../login/index.php');

$userType = $_SESSION['user_type'] ?? 'user';

// O painel resume a situação do estoque em vez de ser apenas um menu: são os
// números que determinam o que o usuário precisa fazer ao entrar no sistema.
$reagenteController = new ReagenteController($conn);
$resumo       = $reagenteController->resumoEstoque();
$emAlerta     = $reagenteController->reagentesEmAlerta(5);
$movimentacoes = $reagenteController->ultimasMovimentacoes(5);

$rotulosMovimentacao = [
    'entrada'  => ['Entrada',  'success',   'fa-arrow-down'],
    'saida'    => ['Retirada', 'warning',   'fa-arrow-up'],
    'criacao'  => ['Cadastro', 'primary',   'fa-plus'],
    'edicao'   => ['Edição',   'secondary', 'fa-pen'],
    'exclusao' => ['Exclusão', 'danger',    'fa-trash'],
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card-action {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 15px;
            height: 100%;
            cursor: pointer;
        }
        .card-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #006233;
        }
        .card-title {
            font-weight: bold;
            color: #333;
        }
        .welcome-section {
            background: linear-gradient(135deg, #006233 0%, #004d28 100%);
            color: white;
            padding: 28px 0;
            margin-bottom: 32px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .indicador {
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .indicador:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
        }
        .indicador .numero {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1.1;
        }
        .indicador .rotulo {
            color: #495057;
            font-size: 0.9rem;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="welcome-section text-center">
        <div class="container">
            <h1 class="display-5">Bem-vindo ao Chemicall</h1>
            <p class="lead mb-0">Sistema de Gerenciamento de Reagentes Químicos</p>
        </div>
    </div>

    <div class="container">
        <!-- Indicadores do estoque -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <a href="../reagentes/index.php" class="text-decoration-none">
                    <div class="card indicador h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="numero text-dark"><?php echo $resumo['total']; ?></div>
                            <div class="rotulo"><i class="fas fa-boxes me-1"></i> Reagentes no sistema</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-lg-3">
                <a href="../reagentes/index.php" class="text-decoration-none">
                    <div class="card indicador h-100 border-0 shadow-sm <?php echo $resumo['vencidos'] > 0 ? 'border-start border-4 border-danger' : ''; ?>">
                        <div class="card-body">
                            <div class="numero <?php echo $resumo['vencidos'] > 0 ? 'text-danger' : 'text-dark'; ?>">
                                <?php echo $resumo['vencidos']; ?>
                            </div>
                            <div class="rotulo">
                                <i class="fas fa-triangle-exclamation me-1"></i> Vencidos em estoque
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-lg-3">
                <a href="../reagentes/index.php" class="text-decoration-none">
                    <div class="card indicador h-100 border-0 shadow-sm <?php echo $resumo['vencendo'] > 0 ? 'border-start border-4 border-warning' : ''; ?>">
                        <div class="card-body">
                            <div class="numero <?php echo $resumo['vencendo'] > 0 ? 'text-warning-emphasis' : 'text-dark'; ?>">
                                <?php echo $resumo['vencendo']; ?>
                            </div>
                            <div class="rotulo">
                                <i class="fas fa-clock me-1"></i> Vencem em <?php echo ReagenteController::DIAS_ALERTA_VALIDADE; ?> dias
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-lg-3">
                <a href="../reagentes/utilizados.php" class="text-decoration-none">
                    <div class="card indicador h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="numero text-dark"><?php echo $resumo['esgotados']; ?></div>
                            <div class="rotulo"><i class="fas fa-box-open me-1"></i> Esgotados</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Reagentes que exigem atenção -->
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3">
                        <h2 class="h5 mb-0"><i class="fas fa-triangle-exclamation text-danger me-1"></i> Precisam de atenção</h2>
                    </div>
                    <div class="card-body pt-2">
                        <?php if (empty($emAlerta)): ?>
                            <p class="text-body-secondary mb-0 py-3 text-center">
                                <i class="fas fa-circle-check text-success me-1"></i>
                                Nenhum reagente vencido ou próximo do vencimento.
                            </p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($emAlerta as $item): ?>
                                    <?php $sit = ReagenteController::situacaoValidade($item['validade']); ?>
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                        <span>
                                            <strong><?php echo e($item['nome']); ?></strong>
                                            <?php if ($item['controlado']): ?>
                                                <span class="badge bg-danger ms-1">Controlado</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-body-secondary">
                                                <?php echo (float) $item['quantidade'] . ' ' . e($item['unidade_medida']); ?>
                                                em estoque · validade <?php echo date('d/m/Y', strtotime($item['validade'])); ?>
                                            </small>
                                        </span>
                                        <span class="badge bg-<?php echo $sit['classe']; ?>-subtle text-<?php echo $sit['classe']; ?>-emphasis border border-<?php echo $sit['classe']; ?>-subtle text-nowrap">
                                            <i class="fas <?php echo $sit['icone']; ?>"></i> <?php echo e($sit['rotulo']); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Movimentações recentes -->
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3">
                        <h2 class="h5 mb-0"><i class="fas fa-clock-rotate-left text-secondary me-1"></i> Movimentações recentes</h2>
                    </div>
                    <div class="card-body pt-2">
                        <?php if (empty($movimentacoes)): ?>
                            <p class="text-body-secondary mb-0 py-3 text-center">Nenhuma movimentação registrada ainda.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($movimentacoes as $mov): ?>
                                    <?php [$rotulo, $cor, $icone] = $rotulosMovimentacao[$mov['tipo_movimentacao']]
                                        ?? [ucfirst($mov['tipo_movimentacao']), 'secondary', 'fa-circle']; ?>
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                        <span>
                                            <span class="badge bg-<?php echo $cor; ?>-subtle text-<?php echo $cor; ?>-emphasis border border-<?php echo $cor; ?>-subtle">
                                                <i class="fas <?php echo $icone; ?>"></i> <?php echo e($rotulo); ?>
                                            </span>
                                            <strong class="ms-1"><?php echo e($mov['reagente']); ?></strong>
                                            <br>
                                            <small class="text-body-secondary">
                                                <?php echo (float) $mov['quantidade']; ?> un ·
                                                <?php echo e($mov['funcionario']); ?>
                                            </small>
                                        </span>
                                        <small class="text-body-secondary text-nowrap">
                                            <?php echo date('d/m/Y H:i', strtotime($mov['data_hora'])); ?>
                                        </small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="h5 mb-3 text-body-secondary">Ações</h2>
        <div class="row g-4 justify-content-center">
            <!-- Card: Novo Reagente -->
            <?php if ($userType === 'admin' || $userType === 'gestor'): ?>
            <div class="col-md-6 col-lg-3">
                <a href="../reagentes/form.php" class="text-decoration-none">
                    <div class="card card-action p-4 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-flask"></i>
                            </div>
                            <h5 class="card-title">Novo Reagente</h5>
                            <p class="card-text text-muted">Cadastrar novo item no estoque</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Card: Ver Estoque -->
            <div class="col-md-6 col-lg-3">
                <a href="../reagentes/index.php" class="text-decoration-none">
                    <div class="card card-action p-4 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <h5 class="card-title">Ver Estoque</h5>
                            <p class="card-text text-muted">Consultar e gerenciar reagentes</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card: Relatórios -->
            <?php if ($userType === 'admin' || $userType === 'gestor'): ?>
            <div class="col-md-6 col-lg-3">
                <a href="../relatorio/relatorios.php" class="text-decoration-none">
                    <div class="card card-action p-4 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h5 class="card-title">Relatórios</h5>
                            <p class="card-text text-muted">Gerar PDF de inventário e logs</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card: Estatísticas -->
            <div class="col-md-6 col-lg-3">
                <a href="../relatorio/estatisticas.php" class="text-decoration-none">
                    <div class="card card-action p-4 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h5 class="card-title">Estatísticas</h5>
                            <p class="card-text text-muted">Visualizar consumo e gráficos</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Card: Usuários -->
            <?php if ($userType === 'admin'): ?>
            <div class="col-md-6 col-lg-3">
                <a href="../usuarios/index.php" class="text-decoration-none">
                    <div class="card card-action p-4 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h5 class="card-title">Usuários</h5>
                            <p class="card-text text-muted">Gerenciar usuários do sistema</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>
