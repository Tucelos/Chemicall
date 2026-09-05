<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Caminhos absolutos: um include relativo depende do diretório de trabalho do
// processo, que varia entre Apache (mod_php) e PHP-FPM.
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../../db/db_connection.php';
require_once __DIR__ . '/../../config/mailer.php';

// Validade do link de redefinição, em minutos.
const RESET_TOKEN_MINUTOS = 30;

// Resposta única, independentemente do e-mail existir: evita que a tela sirva
// para descobrir quais endereços estão cadastrados.
const MSG_RESET_GENERICA =
    'Se este e-mail estiver cadastrado, enviamos um link de redefinição. '
    . 'O link vale por ' . RESET_TOKEN_MINUTOS . ' minutos.';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();

    $email = trim((string) ($_POST['email'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um e-mail válido.';
    } else {
        $stmt = $conn->prepare(
            "SELECT cod_funcionario FROM funcionario WHERE email = :email AND status = 'ativo'"
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // A mensagem exibida é sempre a mesma; só o envio de fato é condicional.
        $mensagem = MSG_RESET_GENERICA;

        if ($user) {
            // Tokens anteriores do mesmo e-mail deixam de valer.
            $conn->prepare('DELETE FROM esqueceu_senha WHERE email = :email')
                 ->execute([':email' => $email]);

            $token = bin2hex(random_bytes(32)); // Gera um token de 64 caracteres
            $hashedToken = hash('sha256', $token);

            $insert_stmt = $conn->prepare(
                'INSERT INTO esqueceu_senha (email, token, expira_em)
                 VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL :minutos MINUTE))'
            );
            $insert_stmt->bindValue(':email', $email);
            $insert_stmt->bindValue(':token', $hashedToken);
            $insert_stmt->bindValue(':minutos', RESET_TOKEN_MINUTOS, PDO::PARAM_INT);
            $res = $insert_stmt->execute();

            if ($res) {
                // A base do link vem da configuração, nunca do cabeçalho Host:
                // caso contrário um atacante poderia forjar o Host e receber o
                // token da vítima em seu próprio domínio.
                $appUrl = rtrim((string) env('APP_URL', ''), '/');

                if ($appUrl === '') {
                    $protocol = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
                    $uri = $_SERVER['REQUEST_URI'] ?? '/';
                    $pos = strpos($uri, 'src/telas/login/');
                    $baseDir = ($pos !== false) ? substr($uri, 0, $pos) : '/';
                    $appUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim($baseDir, '/');
                    error_log('[Chemicall] APP_URL não configurada: link de redefinição montado a partir do Host da requisição.');
                }

                $resetLink = $appUrl . '/src/telas/login/reset.php?token=' . urlencode($token);

                // Criando instância do PHPMailer
                $mail = new PHPMailer(true);

                try {
                    if (!smtp_configurado()) {
                        throw new Exception('SMTP não configurado no .env (SMTP_HOST/SMTP_USER ausentes).');
                    }

                    // Servidor, porta, criptografia, certificado e remetente vêm
                    // todos do .env (src/config/mailer.php).
                    configurar_smtp($mail);

                    $mail->addAddress($email);  // E-mail do destinatário
                    $mail->Subject = 'Redefinir Senha';

                    // Conteúdo do e-mail
                    $mail->isHTML(true);

                    // Corpo do e-mail
                    $mail->Body = "
<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f9;
        color: #333;
        margin: 0;
        padding: 0;
    }
    .container {
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .content {
        padding: 20px;
    }
    .content p {
        font-size: 16px;
        line-height: 1.6;
    }
    .button {
        display: inline-block;
        padding: 15px 30px;
        background-color: black;
        color: white !important;
        text-decoration: none; 
        border-radius: 5px;
        font-size: 16px;
        text-align: center;
        transition: background-color 0.3s ease, color 0.3s ease; 
    }
    .button:hover {
        background-color: #1aa153;
        color: white;
    }
    .footer {
        background-color: #f4f4f9;
        text-align: center;
        padding: 10px;
        font-size: 14px;
        }
</style>
</head>
<body>
    <div class='container'>
        <!-- Conteúdo do E-mail -->
        <div class='content'>
            <p>Olá,</p>
            <p>Recebemos uma solicitação para redefinir sua senha. Para continuar, clique no botão abaixo:</p>
            <center>
            <p><a href='$resetLink' class='button' style='text-decoration: none;'>Redefinir Senha</a></p>
            </center>
            <p>Se você não fez essa solicitação, pode ignorar este e-mail.</p>
        </div>
        
        <!-- Rodapé -->
        <div class='footer'>
            <p>Se você tiver algum problema, entre em contato conosco.</p>
        </div>
    </div>
</body>
</html>
";

                    // Envia o e-mail
                    $mail->send();
                } catch (Exception $e) {
                    // O detalhe do SMTP fica no log; o usuário vê a mensagem
                    // genérica, que também não revela se o e-mail existe.
                    // ErrorInfo fica vazio quando a falha é anterior ao envio
                    // (SMTP ausente no .env), por isso as duas informações.
                    error_log('[Chemicall] Falha no envio do e-mail de redefinição: '
                        . $e->getMessage() . ($mail->ErrorInfo ? ' | ' . $mail->ErrorInfo : ''));
                }
            } else {
                error_log('[Chemicall] Falha ao gravar token de redefinição para ' . $email);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="../../styles/reset.css">
    <script>
        // json_encode escapa a mensagem para o contexto JavaScript.
        window.onload = function () {
            var mensagem = <?php echo json_encode($mensagem, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            if (mensagem) {
                alert(mensagem);
            }
        }

        // Função para enviar o formulário quando o botão for clicado
        function enviarFormulario() {
            document.getElementById('esqueceuSenha').submit(); // Envia o formulário
        }
    </script>
</head>

<body>
    <div class="login-box">
        <center>
            <div class="logo">
                <a href="index.php" style="text-decoration: none; color: black;">
                    <h1>Chemicall</h1>
                </a>
            </div>
        </center>
        <form id="esqueceuSenha" method="POST">
            <?php echo csrf_field(); ?>
            <h4 style="padding-bottom: 10px; text-align: center; ">Redefinir Senha</h4>
            <p style="padding-bottom: 20px; justify-content: space-between;"> Insira o e-mail associado à sua conta para
                redefinir sua senha. Você
                receberá um link para criar uma nova senha.</p>

            <div class="user-box">
                <input type="email" id="email" name="email" required>
                <label for="email">Email</label>
            </div>

            <center>
                <input type="button" value="Solicitar" id="esquecer-senha" onclick="enviarFormulario()">
            </center>
        </form>
    </div>
</body>

</html>