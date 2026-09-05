<?php
require_once __DIR__ . '/../../db/db_connection.php';
require_once __DIR__ . '/../../controllers/FuncionarioController.php';

$token      = (string) ($_GET['token'] ?? '');
$mensagem   = '';
$sucesso    = false;
$tokenValido = false;
$email      = null;

/**
 * Recupera o registro do token se ele existir, ainda não tiver sido usado e
 * estiver dentro do prazo de validade.
 */
function buscarTokenValido(PDO $conn, string $token): ?array
{
    if ($token === '') {
        return null;
    }
    $stmt = $conn->prepare(
        'SELECT id, email FROM esqueceu_senha
         WHERE token = :token
           AND usado_em IS NULL
           AND expira_em IS NOT NULL
           AND expira_em > NOW()
         LIMIT 1'
    );
    $stmt->execute([':token' => hash('sha256', $token)]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    return $registro ?: null;
}

try {
    $registro = buscarTokenValido($conn, $token);

    if (!$registro) {
        $mensagem = 'Link de redefinição inválido ou expirado. Solicite um novo.';
    } else {
        $tokenValido = true;
        $email = $registro['email'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_exigir();

            $password        = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

            if ($password !== $confirmPassword) {
                $mensagem = 'As senhas não coincidem. Tente novamente.';
            } elseif ($erro = FuncionarioController::validarSenha($password)) {
                $mensagem = $erro;
            } else {
                $conn->beginTransaction();

                // A cláusula usado_em IS NULL garante o consumo único do token
                // mesmo se dois envios chegarem ao mesmo tempo.
                $marcar = $conn->prepare(
                    'UPDATE esqueceu_senha SET usado_em = NOW()
                     WHERE id = :id AND usado_em IS NULL'
                );
                $marcar->execute([':id' => $registro['id']]);

                if ($marcar->rowCount() !== 1) {
                    $conn->rollBack();
                    $mensagem = 'Este link já foi utilizado. Solicite um novo.';
                    $tokenValido = false;
                } else {
                    $atualizar = $conn->prepare(
                        "UPDATE funcionario SET senha = :senha
                         WHERE email = :email AND status = 'ativo'"
                    );
                    $atualizar->execute([
                        ':senha' => password_hash($password, PASSWORD_DEFAULT),
                        ':email' => $email,
                    ]);

                    if ($atualizar->rowCount() === 0) {
                        $conn->rollBack();
                        $mensagem = 'Não foi possível redefinir a senha desta conta.';
                        $tokenValido = false;
                    } else {
                        // Invalida qualquer outro token pendente do mesmo e-mail.
                        $conn->prepare('DELETE FROM esqueceu_senha WHERE email = :email')
                             ->execute([':email' => $email]);

                        $conn->commit();
                        $sucesso = true;
                        $tokenValido = false;
                        $mensagem = 'Senha atualizada com sucesso! Você já pode entrar com a nova senha.';
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('[Chemicall] Falha na redefinição de senha: ' . $e->getMessage());
    $mensagem = 'Não foi possível processar a redefinição. Tente novamente.';
    $tokenValido = false;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../styles/reset.css">
    <title>Redefinir Senha</title>
</head>
<body>

<div class="login-box">
    <center>
        <h1>Chemicall</h1>
    </center>

    <?php if ($mensagem): ?>
        <p style="text-align:center; color: <?php echo $sucesso ? '#1aa153' : '#c0392b'; ?>;">
            <?php echo e($mensagem); ?>
        </p>
    <?php endif; ?>

    <?php if ($tokenValido): ?>
        <form method="POST" id="reset-password-form" action="reset.php?token=<?php echo e($token); ?>">
            <?php echo csrf_field(); ?>
            <h4 style="text-align: center;">Redefinir Senha</h4>
            <p>Escolha a nova senha da sua conta. Ela deve ter no mínimo
               <?php echo FuncionarioController::SENHA_MINIMA; ?> caracteres, com ao menos uma letra e um número.</p>

            <div class="user-box">
                <input type="password" name="password" id="password" minlength="<?php echo FuncionarioController::SENHA_MINIMA; ?>" required>
                <label for="password">Nova Senha</label>
            </div>

            <div class="user-box">
                <input type="password" name="confirmPassword" id="confirmPassword" minlength="<?php echo FuncionarioController::SENHA_MINIMA; ?>" required>
                <label for="confirmPassword">Confirmar Senha</label>
            </div>

            <center>
                <input type="submit" value="Atualizar Senha" id="update-password-button">
            </center>
        </form>
    <?php else: ?>
        <center>
            <p><a href="index.php">Voltar para o login</a></p>
            <?php if (!$sucesso): ?>
                <p><a href="esqueceu_senha.php">Solicitar novo link</a></p>
            <?php endif; ?>
        </center>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reset-password-form');
    if (!form) {
        return;
    }
    form.addEventListener('submit', function (e) {
        const senha = document.getElementById('password').value;
        const confirmacao = document.getElementById('confirmPassword').value;
        if (senha !== confirmacao) {
            e.preventDefault();
            alert('As senhas não coincidem. Tente novamente.');
        }
    });
});
</script>

</body>
</html>
