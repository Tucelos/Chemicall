<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/ReagenteController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated()) {
    header('Location: ../login/index.php');
    exit();
}

$reagenteController = new ReagenteController($conn);
$busca = $_GET['busca'] ?? '';
$apenasControlados = isset($_GET['controlado']) && $_GET['controlado'] == '1';
$reagentes = $reagenteController->listar($busca, $apenasControlados);
$isAdmin = $auth->isAdmin();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-boxes"></i> Estoque de Reagentes</h2>
            <?php if ($isAdmin): ?>
            <a href="form.php" class="btn btn-success"><i class="fas fa-plus"></i> Novo Reagente</a>
            <?php endif; ?>
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

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Fórmula</th>
                        <th>CAS</th>
                        <th>Controlado</th>
                        <th>Validade</th>
                        <th>Qtd</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reagentes)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Nenhum reagente encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reagentes as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['nome']); ?></td>
                                <td><?php echo htmlspecialchars($r['formula_quimica']); ?></td>
                                <td><?php echo htmlspecialchars($r['numero_cas']); ?></td>
                                <td>
                                    <?php if ($r['controlado']): ?>
                                        <span class="badge bg-danger">Sim</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($r['validade'])); ?></td>
                                <td>
                                    <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($r['quantidade']); ?></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAdd<?php echo $r['id']; ?>" title="Adicionar Estoque">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalRemove<?php echo $r['id']; ?>" title="Retirar do Estoque">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <?php if ($isAdmin): ?>
                                        <a href="form.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                        <a href="delete.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este item?');" title="Excluir"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Modal Adicionar -->
                                    <div class="modal fade" id="modalAdd<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Adicionar ao Estoque: <?php echo htmlspecialchars($r['nome']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="atualizar_estoque.php" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                        <input type="hidden" name="operacao" value="adicionar">
                                                        <div class="mb-3">
                                                            <label class="form-label">Quantidade a adicionar</label>
                                                            <input type="number" name="quantidade" class="form-control" min="1" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-success">Adicionar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Remover -->
                                    <div class="modal fade" id="modalRemove<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Retirar do Estoque: <?php echo htmlspecialchars($r['nome']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="atualizar_estoque.php" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                        <input type="hidden" name="operacao" value="remover">
                                                        <div class="mb-3">
                                                            <label class="form-label">Quantidade a retirar</label>
                                                            <input type="number" name="quantidade" class="form-control" min="1" max="<?php echo $r['quantidade']; ?>" required>
                                                            <div class="form-text">Estoque atual: <?php echo $r['quantidade']; ?></div>
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
