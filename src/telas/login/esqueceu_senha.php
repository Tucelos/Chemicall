<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../../db/db_connection.php';

// Variáveis de controle para exibir a mensagem de sucesso ou erro
$mensagem = '';

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    // Verifica se o e-mail não está vazio
    if (empty($email)) {
        $mensagem = "O campo de e-mail está vazio.";
    } else {
        // Consulta para verificar se o e-mail existe no banco de dados (usando PDO e Prepared Statements)
        $stmt = $conn->prepare("SELECT * FROM funcionario WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Gera um token seguro para a redefinição de senha
            $token = bin2hex(random_bytes(32)); // Gera um token de 64 caracteres
            $hashedToken = hash('sha256', $token);

            // Insere o token no banco de dados (usando PDO e Prepared Statements)
            $insert_stmt = $conn->prepare("INSERT INTO esqueceu_senha (email, token) VALUES (:email, :token)");
            $res = $insert_stmt->execute([':email' => $email, ':token' => $hashedToken]);

            if ($res) {
                // Build reset link dynamically
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $uri = $_SERVER['REQUEST_URI'];
                $pos = strpos($uri, 'src/telas/login/');
                $baseDir = ($pos !== false) ? substr($uri, 0, $pos) : '/';
                $resetLink = $protocol . "://" . $host . $baseDir . "src/telas/login/reset.php?token=" . $token;

                // Criando instância do PHPMailer
                $mail = new PHPMailer(true);

                try {
                    // Configuração do servidor SMTP
                    $mail->isSMTP();  // Define que estamos usando SMTP
                    $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
                    $mail->SMTPAuth = true;  // Ativa autenticação SMTP
                    $mail->Username = $_ENV['SMTP_USER'] ?? 'anacarol.farias11@gmail.com';
                    $mail->Password = $_ENV['SMTP_PASS'] ?? 'gynaefjniclkgnly';
                    
                    $smtpSecure = strtolower($_ENV['SMTP_SECURE'] ?? 'ssl');
                    if ($smtpSecure === 'tls') {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    } else {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    }
                    $mail->Port = (int)($_ENV['SMTP_PORT'] ?? 465);

                    // Remetente
                    $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? 'no-reply@chemicall.com', $_ENV['SMTP_FROM_NAME'] ?? 'Redefinição de Senha');
                    $mail->addAddress($email);  // E-mail do destinatário
                    $mail->addReplyTo($_ENV['SMTP_FROM_EMAIL'] ?? 'no-reply@chemicall.com', $_ENV['SMTP_FROM_NAME'] ?? 'Redefinição de Senha');
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

                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => true,
                            'verify_peer_name' => true,
                            'allow_self_signed' => false,
                            'cafile' => 'C:/xampp/apache/bin/cacert.pem',
                        ]
                    ];

                    // Envia o e-mail
                    $mail->send();
                    $mensagem = 'Link de redefinição de senha enviado para seu e-mail.';
                } catch (Exception $e) {
                    $mensagem = "Erro ao enviar o e-mail. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $mensagem = "Erro ao gerar o token. Tente novamente.";
            }
        } else {
            $mensagem = "Usuário não encontrado.";
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
        // Função para mostrar o alerta com a mensagem do PHP
        window.onload = function () {
            var mensagem = '<?php echo $mensagem; ?>';
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