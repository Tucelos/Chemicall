<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/FuncionarioController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated()) {
    header('Location: ../login/index.php');
    exit();
}

if (!$auth->isAdmin()) {
    header('Location: ../dashboard/index.php');
    exit();
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcionarioController = new FuncionarioController($conn);
    $tipo = $_POST['tipo'] ?? 'user';
    $dados = [
        'nome' => $_POST['nome'] ?? '',
        'matricula' => $_POST['matricula'] ?? null,
        'email' => $_POST['email'] ?? '',
        'email_secundario' => $_POST['email_secundario'] ?? null,
        'cargo' => $_POST['cargo'] ?? null,
        'senha' => $_POST['senha'] ?? '',
        'tipo' => $tipo,
        'acesso_controlados' => (($tipo === 'admin' || $tipo === 'gestor') || isset($_POST['acesso_controlados'])) ? 1 : 0
    ];

    if (strlen($_POST['senha'] ?? '') < 8) {
        $error = 'A senha deve ter no mínimo 8 caracteres.';
    } else {
        $resultado = $funcionarioController->criar($dados);
        if ($resultado['success']) {
            $msg = $resultado['message'];
        } else {
            $error = $resultado['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuários - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-user-plus"></i> Cadastro de Usuário</h4>
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
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="matricula" class="form-label">Matrícula</label>
                                    <input type="text" class="form-control" id="matricula" name="matricula" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cargo" class="form-label">Cargo / Função</label>
                                    <input type="text" class="form-control" id="cargo" name="cargo" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Principal</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="email_secundario" class="form-label">Email Secundário (Opcional)</label>
                                <input type="email" class="form-control" id="email_secundario" name="email_secundario">
                            </div>

                            <div class="mb-3">
                                <label for="tipo" class="form-label d-flex align-items-center">
                                    Perfil de Acesso
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle ms-2" data-bs-toggle="popover" data-bs-placement="right" data-bs-html="true" data-bs-title="Níveis de Permissão" data-bs-content="<strong>Administrador:</strong> Acesso total ao sistema, gerenciar usuários, gerenciar estoque e emitir relatórios.<br><br><strong>Gestor:</strong> Permissão para modificar o estoque (criar, editar, excluir produtos) e emitir relatórios.<br><br><strong>Usuário:</strong> Apenas visualizar o estoque e realizar retiradas (acesso a produtos controlados é opcional)." style="width: 20px; height: 20px; padding: 0; font-size: 0.75rem; line-height: 1; display: inline-flex; align-items: center; justify-content: center; font-weight: bold;">?</button>
                                </label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="user">Usuário</option>
                                    <option value="gestor">Gestor</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>

                            <div class="mb-3 form-check" id="div_controlados">
                                <input type="checkbox" class="form-check-input" id="acesso_controlados" name="acesso_controlados" value="1">
                                <label class="form-check-label" for="acesso_controlados">Permitir visualização e retirada de produtos controlados</label>
                            </div>

                            <div class="mb-4">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">Cadastrar</button>
                                <a href="../dashboard/index.php" class="btn btn-outline-secondary">Voltar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSelect = document.getElementById('tipo');
        const divControlados = document.getElementById('div_controlados');

        function toggleControlados() {
            if (tipoSelect.value === 'user') {
                divControlados.style.display = 'block';
            } else {
                divControlados.style.display = 'none';
            }
        }

        tipoSelect.addEventListener('change', toggleControlados);
        toggleControlados(); // Executa ao carregar a página

        // Inicializar os Popovers do Bootstrap
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
    });
    </script>
</body>
</html>