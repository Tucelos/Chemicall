<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/FuncionarioController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated() || !$auth->isAdmin()) {
    header('Location: ../dashboard/index.php');
    exit();
}

$controller = new FuncionarioController($conn);
$usuarios = $controller->listar();

if (isset($_POST['delete_id'])) {
    if ($controller->deletar($_POST['delete_id'])) {
        header('Location: index.php?msg=Usuário excluído com sucesso');
        exit();
    } else {
        $error = "Erro ao excluir usuário.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users"></i> Gerenciar Usuários</h2>
            <a href="cadastro.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Novo Usuário</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Login</th>
                                <th>Tipo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['cod_funcionario']); ?></td>
                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['login_funcionario']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['tipo'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user['tipo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="editar.php?id=<?php echo $user['cod_funcionario']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['cod_funcionario'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['cod_funcionario']; ?>" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <!-- Modal de Exclusão -->
                                    <div class="modal fade" id="deleteModal<?php echo $user['cod_funcionario']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmar Exclusão</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Tem certeza que deseja excluir o usuário <strong><?php echo htmlspecialchars($user['nome']); ?></strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="delete_id" value="<?php echo $user['cod_funcionario']; ?>">
                                                        <button type="submit" class="btn btn-danger">Excluir</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
