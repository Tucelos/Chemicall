<?php
require_once __DIR__ . '/../../controllers/FuncionarioController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$error = '';
$success = '';
$nome = $matricula = $email = $cargo = '';

// Mensagem única para qualquer desfecho: um cadastro público não deve revelar
// quais e-mails já existem no sistema.
const MSG_SOLICITACAO_ENVIADA =
    'Se os dados informados forem válidos, sua solicitação será analisada por um administrador. '
    . 'Você receberá o retorno pelo e-mail informado.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();

    $nome            = trim((string) ($_POST['nome'] ?? ''));
    $matricula       = trim((string) ($_POST['matricula'] ?? ''));
    $email           = trim((string) ($_POST['email'] ?? ''));
    $cargo           = trim((string) ($_POST['cargo'] ?? ''));
    $senha           = (string) ($_POST['senha'] ?? '');
    $confirmar_senha = (string) ($_POST['confirmar_senha'] ?? '');

    // Teto para a fila de aprovação: impede que um script encha a tabela de
    // funcionários com cadastros pendentes.
    $limiteAtingido = false;
    try {
        $stmtLimite = $conn->prepare(
            "SELECT COUNT(*) FROM funcionario WHERE status = 'pendente'"
        );
        $stmtLimite->execute();
        $limiteAtingido = (int) $stmtLimite->fetchColumn() >= 50;
    } catch (PDOException $e) {
        error_log('[Chemicall] Falha ao verificar fila de solicitações: ' . $e->getMessage());
    }

    if ($limiteAtingido) {
        $error = 'Há muitas solicitações aguardando análise. Tente novamente mais tarde.';
    } elseif ($nome === '' || $matricula === '' || $email === '' || $cargo === '' || $senha === '') {
        $error = 'Todos os campos são obrigatórios!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail válido!';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem!';
    } elseif ($erroSenha = FuncionarioController::validarSenha($senha)) {
        $error = $erroSenha;
    } else {
        $controller = new FuncionarioController($conn);
        $res = $controller->criar([
            'nome'               => $nome,
            'matricula'          => $matricula,
            'email'              => $email,
            'cargo'              => $cargo,
            'senha'              => $senha,
            'tipo'               => 'user',      // Usuário comum por padrão
            'status'             => 'pendente',  // Pendente de aprovação do administrador
            'acesso_controlados' => 0,           // Sem acesso a controlados por padrão
        ]);

        // Sucesso e e-mail duplicado devolvem a mesma resposta; o detalhe fica no log.
        if (!$res['success']) {
            error_log('[Chemicall] Solicitação de cadastro recusada: ' . $res['message']);
        }
        $success = MSG_SOLICITACAO_ENVIADA;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Cadastro - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">
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
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo e($success); ?>
                <div class="mt-3 text-center">
                    <a href="index.php" class="btn btn-outline-success btn-sm">Voltar para o Login</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label for="nome" class="form-label fw-semibold">Nome Completo</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo e($nome ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="matricula" class="form-label fw-semibold">Matrícula</label>
                    <input type="text" class="form-control" id="matricula" name="matricula" value="<?php echo e($matricula ?? ''); ?>" placeholder="Ex: 202612345" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email institucional</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo e($email ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="cargo" class="form-label fw-semibold">Cargo / Função</label>
                    <input type="text" class="form-control" id="cargo" name="cargo" value="<?php echo e($cargo ?? ''); ?>" placeholder="Ex: Professor, Técnico de Laboratório" required>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>
