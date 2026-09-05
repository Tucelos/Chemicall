<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/ReagenteController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
$auth->exigirLogin('../login/index.php');

$reagenteController = new ReagenteController($conn);
$busca = trim((string) ($_GET['busca'] ?? ''));
$apenasControlados = isset($_GET['controlado']) && $_GET['controlado'] == '1';
$reagentes = $reagenteController->listar($busca, $apenasControlados, false); // Apenas ativos (quantidade > 0)
$isAdmin = $auth->isAdmin();
$isGestor = $auth->isGestor();

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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-boxes"></i> Estoque de Reagentes</h2>
            <div class="d-flex gap-2">
                <a href="utilizados.php" class="btn btn-outline-secondary"><i class="fas fa-history"></i> Itens Utilizados</a>
                <?php if ($isAdmin || $isGestor): ?>
                <a href="form.php" class="btn btn-success"><i class="fas fa-plus"></i> Novo Reagente</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                    <input type="text" name="busca" class="form-control w-auto" placeholder="Buscar por nome, fórmula ou CAS..." value="<?php echo htmlspecialchars($busca); ?>">
                    
                    <div class="form-check ms-2">
                        <input class="form-check-input" type="checkbox" name="controlado" value="1" id="checkControlado" <?php echo $apenasControlados ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <label class="form-check-label" for="checkControlado">
                            Apenas Controlados
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                    <?php if ($busca || $apenasControlados): ?>
                        <a href="index.php" class="btn btn-secondary">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="card bg-dark text-white p-3 mb-4 d-none">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span id="selectedCount" class="fw-bold fs-5 text-warning">0</span> itens selecionados
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#bulkWithdrawModal">
                        <i class="fas fa-minus"></i> Retirar em Lote
                    </button>
                    <?php if ($isAdmin || $isGestor): ?>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                        <i class="fas fa-trash"></i> Excluir Selecionados
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form id="bulkForm" action="acoes_lote.php" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" id="bulkAcao" value="">
            
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <!--
                            Ordem das colunas: o que se usa para agir (quantidade,
                            validade, ações) vem antes das propriedades físicas.
                            As colunas marcadas com d-none só aparecem a partir de
                            telas grandes — no celular a tabela mostra apenas o
                            essencial, em vez de exigir rolagem lateral até as ações.
                        -->
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>Nome</th>
                            <th>Qtd.</th>
                            <th>Ações</th>
                            <th>Validade</th>
                            <th class="d-none d-md-table-cell">Controlado</th>
                            <th class="d-none d-lg-table-cell">Densidade</th>
                            <th class="d-none d-lg-table-cell">Concentração</th>
                            <th class="d-none d-xl-table-cell">Unidade</th>
                            <th class="d-none d-xl-table-cell">Capacidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reagentes)): ?>
                            <tr>
                                <td colspan="10" class="text-center">Nenhum reagente encontrado no estoque.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reagentes as $r): ?>
                                <?php $val = ReagenteController::situacaoValidade($r['validade']); ?>
                                <?php $alertaValidade = in_array($val['estado'], ['vencido', 'vence_em_breve'], true); ?>
                                <tr class="clickable-row" data-reagente-id="<?php echo $r['id']; ?>" style="cursor: pointer;">
                                    <td>
                                        <input type="checkbox" class="form-check-input row-checkbox" name="ids[]" value="<?php echo $r['id']; ?>" data-nome="<?php echo htmlspecialchars($r['nome']); ?>" data-qtd="<?php echo $r['quantidade']; ?>" data-unidade="<?php echo htmlspecialchars($r['unidade_medida']); ?>" data-capacidade="<?php echo $r['capacidade_medida']; ?>" data-unicap="<?php echo htmlspecialchars($r['unidade_capacidade']); ?>">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($r['nome']); ?></strong>
                                        <?php if ($r['controlado']): ?>
                                            <span class="badge bg-danger ms-1 d-md-none">Controlado</span>
                                        <?php endif; ?>
                                        <?php if ($alertaValidade): ?>
                                            <!-- Espelha o alerta sob o nome nas telas pequenas, onde a
                                                 coluna Validade fica fora do campo de visão. -->
                                            <div class="d-lg-none mt-1">
                                                <span class="badge bg-<?php echo $val['classe']; ?>-subtle text-<?php echo $val['classe']; ?>-emphasis border border-<?php echo $val['classe']; ?>-subtle">
                                                    <i class="fas <?php echo $val['icone']; ?>"></i>
                                                    <?php echo e($val['rotulo']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark fs-6"><?php echo (float)$r['quantidade']; ?></span>
                                    </td>
                                    
                                    <td>
                                        <div class="btn-group" role="group">
                                             <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalRemove<?php echo $r['id']; ?>" title="Retirar do Estoque">
                                                 <i class="fas fa-minus"></i>
                                             </button>
                                             <?php if ($isAdmin || $isGestor): ?>
                                             <a href="form.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                             <button type="button" class="btn btn-sm btn-danger btn-excluir" data-id="<?php echo $r['id']; ?>" data-nome="<?php echo htmlspecialchars($r['nome']); ?>" title="Excluir"><i class="fas fa-trash"></i></button>
                                             <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo date('d/m/Y', strtotime($r['validade'])); ?></div>
                                        <?php if ($alertaValidade): ?>
                                            <span class="badge bg-<?php echo $val['classe']; ?>-subtle text-<?php echo $val['classe']; ?>-emphasis border border-<?php echo $val['classe']; ?>-subtle mt-1 d-none d-lg-inline-block">
                                                <i class="fas <?php echo $val['icone']; ?>"></i>
                                                <?php echo e($val['rotulo']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php if ($r['controlado']): ?>
                                            <span class="badge bg-danger">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($r['densidade'] !== null ? $r['densidade'] . ' g/cm³' : '-'); ?></td>
                                    <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($r['concentracao'] ?? '-'); ?></td>
                                    <td class="d-none d-xl-table-cell"><?php echo htmlspecialchars($r['unidade_medida']); ?></td>
                                    <td class="d-none d-xl-table-cell"><?php echo htmlspecialchars($r['capacidade_medida'] !== null && $r['capacidade_medida'] > 0 ? ((float)$r['capacidade_medida']) . ' ' . $r['unidade_capacidade'] : '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bulk Withdraw Modal -->
            <div class="modal fade" id="bulkWithdrawModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content text-dark">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title"><i class="fas fa-minus"></i> Retirar Itens em Lote</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-start">
                            <div class="mb-4 pb-3 border-bottom bg-light p-3 rounded">
                                <label class="form-label fw-bold text-secondary mb-1">Aplicar a mesma quantidade para todos</label>
                                <div class="input-group">
                                    <input type="number" id="bulk_apply_all_qty" class="form-control" placeholder="Ex: 5" min="0" step="any">
                                    <button class="btn btn-warning text-dark fw-bold" type="button" id="btn_apply_all_qty">Aplicar</button>
                                </div>
                                <div class="form-text text-muted" style="font-size:0.75rem;">Preenche a quantidade de todos os itens da lista abaixo (respeitando o estoque máximo individual).</div>
                            </div>

                            <div id="bulkWithdrawItemsList">
                                <!-- Dynamic content populated by JS -->
                            </div>
                            
                            <div class="mb-3 mt-4">
                                <label class="form-label fw-bold">Motivo da retirada (para todos)</label>
                                <select name="motivo_tipo" id="motivo_tipo_bulk" class="form-select" required>
                                    <option value="uso">Uso em aula/pesquisa</option>
                                    <option value="vencimento">Vencimento do produto</option>
                                    <option value="outro">Outro (especificar)</option>
                                </select>
                            </div>
                            <div class="mb-3 d-none" id="divOutroBulk">
                                <label class="form-label fw-bold">Especifique o motivo</label>
                                <input type="text" name="motivo_outro" class="form-control" id="inputOutroBulk">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning" onclick="document.getElementById('bulkAcao').value = 'retirar';">Confirmar Retirada</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Delete Modal -->
            <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content text-dark">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title"><i class="fas fa-trash"></i> Confirmar Exclusão em Lote</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-start">
                            <p>Tem certeza que deseja excluir permanentemente os seguintes reagentes?</p>
                            <ul id="bulkDeleteItemsList" class="text-danger">
                                <!-- Dynamic list populated by JS -->
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger" onclick="document.getElementById('bulkAcao').value = 'excluir';">Confirmar Exclusão</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Exclusão individual: POST com token CSRF, fora do bulkForm -->
        <form id="deleteForm" action="delete.php" method="POST" class="d-none">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="deleteId" value="">
        </form>

        <!-- Modais de Ações Individuais (Fora do bulkForm) -->
        <?php if (!empty($reagentes)): ?>
            <?php foreach ($reagentes as $r): ?>
                <!-- Modal Remover -->
                <div class="modal fade" id="modalRemove<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content text-dark">
                            <div class="modal-header">
                                <h5 class="modal-title">Retirar do Estoque: <?php echo htmlspecialchars($r['nome']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="atualizar_estoque.php" method="POST">
                                <?php echo csrf_field(); ?>
                                <div class="modal-body text-start">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <input type="hidden" name="operacao" value="remover">
                                    <div class="mb-3">
                                        <label class="form-label">Quantidade a retirar</label>
                                        <input type="number" name="quantidade" class="form-control" min="1" max="<?php echo $r['quantidade']; ?>" required>
                                        <div class="form-text">Estoque atual: <?php echo formatarQuantidade($r['quantidade'], $r['unidade_medida'], $r['capacidade_medida'], $r['unidade_capacidade']); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Motivo da retirada</label>
                                        <select name="motivo_tipo" class="form-select select-motivo" data-reagente-id="<?php echo $r['id']; ?>" required>
                                            <option value="uso">Uso em aula/pesquisa</option>
                                            <option value="vencimento">Vencimento do produto</option>
                                            <option value="outro">Outro (especificar)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 d-none" id="divOutro<?php echo $r['id']; ?>">
                                        <label class="form-label">Especifique o motivo</label>
                                        <input type="text" name="motivo_outro" class="form-control" id="inputOutro<?php echo $r['id']; ?>">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-warning">Retirar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Detalhes -->
                <div class="modal fade" id="modalDetail<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content text-dark">
                            <div class="modal-header bg-dark text-white">
                                <h5 class="modal-title"><i class="fas fa-flask"></i> Detalhes do Reagente: <?php echo htmlspecialchars($r['nome']); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-start">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <strong>Nome:</strong> <span class="text-secondary"><?php echo htmlspecialchars($r['nome']); ?></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Fórmula Química:</strong> <span class="text-secondary"><?php echo htmlspecialchars($r['formula_quimica'] ?? '-'); ?></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Número CAS:</strong> <span class="text-secondary"><?php echo htmlspecialchars($r['numero_cas'] ?? '-'); ?></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Massa Molar:</strong> <span class="text-secondary"><?php echo htmlspecialchars($r['massa_molar'] ?? '-'); ?> g/mol</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Densidade:</strong> <span class="text-secondary"><?php echo htmlspecialchars($r['densidade'] ?? '-'); ?> g/cm³</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Concentração:</strong> <span class="text-secondary"><?php echo htmlspecialchars($r['concentracao'] ?? '-'); ?></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Data de Validade:</strong> <span class="text-secondary"><?php echo date('d/m/Y', strtotime($r['validade'])); ?></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Fabricante:</strong> <span class="text-secondary"><?php echo htmlspecialchars($r['fabricante'] ?? '-'); ?></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Condição:</strong> <span class="text-secondary"><?php echo ucfirst(htmlspecialchars($r['condicao'])); ?></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Número NCM:</strong> <span class="text-secondary"><?php echo htmlspecialchars($r['numero_ncm'] ?? '-'); ?></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Nota Fiscal:</strong> <span class="text-secondary"><?php echo htmlspecialchars($r['numero_nota_fiscal'] ?? '-'); ?></span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Produto Controlado:</strong> 
                                        <span class="badge bg-<?php echo $r['controlado'] ? 'danger' : 'secondary'; ?>">
                                            <?php echo $r['controlado'] ? 'Sim' : 'Não'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h5 class="text-dark"><i class="fas fa-chart-pie"></i> Métricas de Estoque</h5>
                                <div class="row mt-3 text-center d-flex align-items-stretch">
                                    <div class="col-md-4 mb-3">
                                        <div class="card bg-light p-3 h-100 d-flex flex-column justify-content-between">
                                            <div>
                                                <h6>Qtd Original</h6>
                                                <span class="fs-6 text-primary"><?php echo formatarQuantidade($r['quantidade_original'], $r['unidade_medida'], $r['capacidade_medida'], $r['unidade_capacidade']); ?></span>
                                            </div>
                                            <small class="d-block mt-1" style="font-size: 0.75rem; visibility: hidden;">&nbsp;</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card bg-light p-3 border-danger-subtle h-100 d-flex flex-column justify-content-between" style="cursor: pointer; transition: all 0.2s;" onmouseover="this.classList.add('shadow-sm'); this.style.transform='scale(1.03)';" onmouseout="this.classList.remove('shadow-sm'); this.style.transform='scale(1)';" data-bs-toggle="modal" data-bs-target="#modalUsageHistory<?php echo $r['id']; ?>">
                                            <div>
                                                <h6 class="text-danger"><i class="fas fa-history me-1"></i> Qtd Utilizada</h6>
                                                <span class="fs-6 text-danger fw-bold">
                                                    <?php 
                                                    $stmtSaidas = $conn->prepare("SELECT SUM(quantidade) as total FROM movimentacoes WHERE reagente_id = :rid AND tipo_movimentacao = 'saida'");
                                                    $stmtSaidas->execute([':rid' => $r['id']]);
                                                    $totalSaidas = $stmtSaidas->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                                                    echo formatarQuantidade($totalSaidas, $r['unidade_medida'], $r['capacidade_medida'], $r['unidade_capacidade']);
                                                    ?>
                                                </span>
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;"><i class="fas fa-eye"></i> Clique para ver histórico</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card bg-light p-3 h-100 d-flex flex-column justify-content-between">
                                            <div>
                                                <h6>Qtd em Estoque</h6>
                                                <span class="fs-6 text-success"><?php echo formatarQuantidade($r['quantidade'], $r['unidade_medida'], $r['capacidade_medida'], $r['unidade_capacidade']); ?></span>
                                            </div>
                                            <small class="d-block mt-1" style="font-size: 0.75rem; visibility: hidden;">&nbsp;</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <strong>Cadastrado por:</strong> 
                                        <span class="text-secondary">
                                            <?php 
                                            $stmtCriador = $conn->prepare("
                                                SELECT f.nome 
                                                FROM movimentacoes m 
                                                JOIN funcionario f ON m.funcionario_id = f.cod_funcionario 
                                                WHERE m.reagente_id = :rid AND m.tipo_movimentacao = 'criacao' 
                                                LIMIT 1
                                            ");
                                            $stmtCriador->execute([':rid' => $r['id']]);
                                            $criador = $stmtCriador->fetch(PDO::FETCH_ASSOC);
                                            echo htmlspecialchars($criador['nome'] ?? 'Administrador/Semente');
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Histórico de Movimentações (Retiradas) -->
                <div class="modal fade" id="modalUsageHistory<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content text-dark">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title"><i class="fas fa-history"></i> Relatório de Movimentações: <?php echo htmlspecialchars($r['nome']); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-start">
                                <?php
                                $stmtHist = $conn->prepare("
                                    SELECT m.*, f.nome as funcionario_nome 
                                    FROM movimentacoes m 
                                    JOIN funcionario f ON m.funcionario_id = f.cod_funcionario 
                                    WHERE m.reagente_id = :rid AND m.tipo_movimentacao = 'saida' 
                                    ORDER BY m.data_hora DESC
                                ");
                                $stmtHist->execute([':rid' => $r['id']]);
                                $historico = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                
                                <?php if (empty($historico)): ?>
                                    <div class="alert alert-info text-center my-3">
                                        Nenhuma movimentação de retirada registrada para este reagente.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-striped table-hover align-middle">
                                            <thead class="table-dark">
                                                <tr>
                                                    <?php if ($isAdmin): ?>
                                                        <th>Data/Hora</th>
                                                        <th>Quem Retirou</th>
                                                    <?php endif; ?>
                                                    <th>Quantidade</th>
                                                    <th>Motivo Assinalado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($historico as $h): ?>
                                                    <tr>
                                                        <?php if ($isAdmin): ?>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($h['data_hora'])); ?></td>
                                                            <td>
                                                                <i class="fas fa-user text-secondary me-1"></i>
                                                                <?php echo htmlspecialchars($h['funcionario_nome']); ?>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td>
                                                            <span class="badge bg-danger-subtle text-danger fs-6 border border-danger-subtle">
                                                                - <?php echo formatarQuantidade($h['quantidade'], $r['unidade_medida'], $r['capacidade_medida'], $r['unidade_capacidade']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="text-secondary"><?php echo htmlspecialchars($h['motivo_retirada'] ?? 'Não especificado'); ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalDetail<?php echo $r['id']; ?>">
                                    <i class="fas fa-arrow-left"></i> Voltar
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Exibição condicional do motivo personalizado no modal individual
        const selectMotivos = document.querySelectorAll('.select-motivo');
        selectMotivos.forEach(select => {
            select.addEventListener('change', function() {
                const id = this.getAttribute('data-reagente-id');
                const divOutro = document.getElementById('divOutro' + id);
                const inputOutro = document.getElementById('inputOutro' + id);
                if (this.value === 'outro') {
                    divOutro.classList.remove('d-none');
                    inputOutro.setAttribute('required', 'required');
                    inputOutro.focus();
                } else {
                    divOutro.classList.add('d-none');
                    inputOutro.removeAttribute('required');
                    inputOutro.value = '';
                }
            });
        });

        // Exibição condicional do motivo personalizado no modal em lote
        const selectMotivoBulk = document.getElementById('motivo_tipo_bulk');
        const divOutroBulk = document.getElementById('divOutroBulk');
        const inputOutroBulk = document.getElementById('inputOutroBulk');
        if (selectMotivoBulk) {
            selectMotivoBulk.addEventListener('change', function() {
                if (this.value === 'outro') {
                    divOutroBulk.classList.remove('d-none');
                    inputOutroBulk.setAttribute('required', 'required');
                    inputOutroBulk.focus();
                } else {
                    divOutroBulk.classList.add('d-none');
                    inputOutroBulk.removeAttribute('required');
                    inputOutroBulk.value = '';
                }
            });
        }

        // Clique na linha para abrir os Detalhes (ignora se clicar em checkbox ou botões)
        const clickableRows = document.querySelectorAll('.clickable-row');
        clickableRows.forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.closest('button') || e.target.closest('a') || e.target.closest('.modal') || e.target.closest('input[type="checkbox"]')) {
                    return;
                }
                const id = this.getAttribute('data-reagente-id');
                const detailModalEl = document.getElementById('modalDetail' + id);
                if (detailModalEl) {
                    const myModal = new bootstrap.Modal(detailModalEl);
                    myModal.show();
                }
            });
        });

        // Exclusão individual via POST (o botão dispara o formulário com token CSRF)
        document.querySelectorAll('.btn-excluir').forEach(btn => {
            btn.addEventListener('click', function() {
                const nome = this.getAttribute('data-nome');
                if (!confirm('Tem certeza que deseja excluir "' + nome + '"?')) {
                    return;
                }
                document.getElementById('deleteId').value = this.getAttribute('data-id');
                document.getElementById('deleteForm').submit();
            });
        });

        // Lógica de seleção em lote
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');

        function updateBulkActions() {
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            if (checkedCount > 0) {
                bulkActionsBar.classList.remove('d-none');
                selectedCount.textContent = checkedCount;
            } else {
                bulkActionsBar.classList.add('d-none');
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                updateBulkActions();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkActions);
        });

        // Popular modal de retirada em lote
        const bulkWithdrawModal = document.getElementById('bulkWithdrawModal');
        const inputApplyAll = document.getElementById('bulk_apply_all_qty');
        const btnApplyAll = document.getElementById('btn_apply_all_qty');

        if (bulkWithdrawModal) {
            bulkWithdrawModal.addEventListener('show.bs.modal', function () {
                if (inputApplyAll) {
                    inputApplyAll.value = '';
                }
                const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
                const container = document.getElementById('bulkWithdrawItemsList');
                container.innerHTML = '';

                selectedCheckboxes.forEach(cb => {
                    const id = cb.value;
                    const nome = cb.getAttribute('data-nome');
                    const maxQtd = cb.getAttribute('data-qtd');
                    const unidade = cb.getAttribute('data-unidade');
                    const capacidade = cb.getAttribute('data-capacidade');
                    const uniCap = cb.getAttribute('data-unicap');

                    let fmtInfo = maxQtd + ' ' + unidade;
                    if (capacidade && capacidade > 0) {
                        fmtInfo += ' (' + parseFloat(capacidade) + ' ' + uniCap + ')';
                    }

                    // Os nós são criados pela API do DOM, com o nome do reagente
                    // atribuído via textContent. Montar este bloco por template
                    // literal + insertAdjacentHTML executaria o conteúdo de
                    // data-nome como HTML: o escape feito pelo PHP no atributo é
                    // desfeito por getAttribute().
                    const bloco = document.createElement('div');
                    bloco.className = 'mb-3 border-bottom pb-3';

                    const rotulo = document.createElement('label');
                    rotulo.className = 'form-label fw-bold mb-1';
                    rotulo.htmlFor = 'bulk_qty_' + id;
                    rotulo.textContent = nome + ' ';

                    const estoque = document.createElement('span');
                    estoque.className = 'text-muted';
                    estoque.style.fontSize = '0.85rem';
                    estoque.textContent = '(Estoque: ' + fmtInfo + ')';
                    rotulo.appendChild(estoque);

                    const campo = document.createElement('input');
                    campo.type = 'number';
                    campo.name = 'quantidades[' + id + ']';
                    campo.id = 'bulk_qty_' + id;
                    campo.className = 'form-control bulk-qty-input';
                    campo.min = '0';
                    campo.max = maxQtd;
                    campo.step = 'any';
                    campo.value = '0';
                    campo.required = true;

                    const ajuda = document.createElement('div');
                    ajuda.className = 'form-text';
                    ajuda.textContent = 'Quantidade a retirar (máx: ' + maxQtd + ')';

                    bloco.append(rotulo, campo, ajuda);
                    container.appendChild(bloco);
                });
            });
        }

        if (btnApplyAll && inputApplyAll) {
            btnApplyAll.addEventListener('click', function () {
                const val = parseFloat(inputApplyAll.value);
                if (isNaN(val) || val < 0) return;

                const qtyInputs = document.querySelectorAll('.bulk-qty-input');
                qtyInputs.forEach(input => {
                    const maxVal = parseFloat(input.getAttribute('max'));
                    if (val > maxVal) {
                        input.value = maxVal;
                    } else {
                        input.value = val;
                    }
                });
            });
        }

        // Popular modal de exclusão em lote
        const bulkDeleteModal = document.getElementById('bulkDeleteModal');
        if (bulkDeleteModal) {
            bulkDeleteModal.addEventListener('show.bs.modal', function () {
                const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
                const listContainer = document.getElementById('bulkDeleteItemsList');
                listContainer.innerHTML = '';

                selectedCheckboxes.forEach(cb => {
                    const nome = cb.getAttribute('data-nome');
                    const li = document.createElement('li');
                    li.className = 'mb-1';
                    li.textContent = nome;
                    listContainer.appendChild(li);
                });
            });
        }
    });
    </script>
</body>
</html>
