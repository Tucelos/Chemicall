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
$id = $_GET['id'] ?? null;
$usuario = null;

if ($id) {
    $usuario = $controller->buscarPorId($id);
    if (!$usuario) {
        header('Location: index.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome' => $_POST['nome'],
        'email' => $_POST['email'],
        'login' => $_POST['login'],
        'tipo' => $_POST['tipo'],
        'senha' => $_POST['senha'] // Optional
    ];

    if ($controller->atualizar($id, $dados)) {
        $msg = 'Usuário atualizado com sucesso!';
        // Refresh data
        $usuario = $controller->buscarPorId($id);
    } else {
        $error = 'Erro ao atualizar usuário.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-user-edit"></i> Editar Usuário</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($msg): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($msg); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo htmlspecialchars($usuario['nome']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($usuario['email']); ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="login" class="form-label">Login</label>
                                    <input type="text" class="form-control" id="login" name="login" required value="<?php echo htmlspecialchars($usuario['login_funcionario']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tipo" class="form-label">Tipo de Usuário</label>
                                    <select class="form-select" id="tipo" name="tipo" required>
                                        <option value="docente" <?php echo $usuario['tipo'] === 'docente' ? 'selected' : ''; ?>>Professor (Docente)</option>
                                        <option value="admin" <?php echo $usuario['tipo'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="senha" class="form-label">Nova Senha (deixe em branco para manter)</label>
                                <input type="password" class="form-control" id="senha" name="senha">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-warning btn-lg">Salvar Alterações</button>
                                <a href="index.php" class="btn btn-outline-secondary">Voltar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
