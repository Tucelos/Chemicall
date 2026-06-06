<?php
require_once __DIR__ . '/../../controllers/FuncionarioController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $matricula = $_POST['matricula'] ?? '';
    $email = $_POST['email'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (empty($nome) || empty($matricula) || empty($email) || empty($cargo) || empty($senha)) {
        $error = 'Todos os campos são obrigatórios!';
    } elseif (strlen($senha) < 8) {
        $error = 'A senha deve ter no mínimo 8 caracteres!';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem!';
    } else {
        $controller = new FuncionarioController($conn);
        $dados = [
            'nome' => $nome,
            'matricula' => $matricula,
            'email' => $email,
            'cargo' => $cargo,
            'senha' => $senha,
            'tipo' => 'user',             // Usuário comum por padrão
            'status' => 'pendente',        // Pendente de aprovação do administrador
            'acesso_controlados' => 0      // Sem acesso a controlados por padrão
        ];

        $res = $controller->criar($dados);
        if ($res['success']) {
            $success = 'Sua solicitação de cadastro foi enviada com sucesso! Um administrador irá analisar o seu pedido.';
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Cadastro - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px 0;
        }
        .register-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        .logo-text {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: bold;
            font-size: 2.2rem;
            color: #006233;
            text-align: center;
            margin-bottom: 25px;
        }
        .btn-custom {
            background-color: #006233;
            color: white;
            width: 100%;
            padding: 10px;
            font-size: 1.1rem;
        }
        .btn-custom:hover {
            background-color: #004d28;
            color: white;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <div class="logo-text">
            <i class="fas fa-flask"></i> Chemicall
        </div>
        
        <h4 class="text-center mb-4" style="color: #333;">Solicitar Acesso ao Sistema</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <div class="mt-3 text-center">
                    <a href="index.php" class="btn btn-outline-success btn-sm">Voltar para o Login</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="nome" class="form-label fw-semibold">Nome Completo</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="matricula" class="form-label fw-semibold">Matrícula</label>
                    <input type="text" class="form-control" id="matricula" name="matricula" value="<?php echo htmlspecialchars($matricula ?? ''); ?>" placeholder="Ex: 202612345" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email institucional</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="cargo" class="form-label fw-semibold">Cargo / Função</label>
                    <input type="text" class="form-control" id="cargo" name="cargo" value="<?php echo htmlspecialchars($cargo ?? ''); ?>" placeholder="Ex: Professor, Técnico de Laboratório" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="senha" class="form-label fw-semibold">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirmar_senha" class="form-label fw-semibold">Confirmar Senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-custom mt-2">Enviar Solicitação</button>
                
                <div class="mt-3 text-center">
                    <a href="index.php" class="text-decoration-none small text-muted">Já possui uma conta? Entrar</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
