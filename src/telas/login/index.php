<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);

if ($auth->isAuthenticated()) {
    header('Location: ../dashboard/index.php');
    exit();
}

$error = '';
$aviso = '';

if (!empty($_SESSION['sessao_expirada'])) {
    $aviso = 'Sua sessão expirou por inatividade. Faça login novamente.';
    unset($_SESSION['sessao_expirada']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_exigir();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $resultado = $auth->login($email, $password);
    if ($resultado['success']) {
        header('Location: ../dashboard/index.php');
        exit();
    }
    $error = $resultado['message'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .logo-text {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: bold;
            font-size: 2.5rem;
            color: #006233;
            text-align: center;
            margin-bottom: 30px;
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
    <div class="login-box">
        <div class="logo-text">
            <i class="fas fa-flask"></i> Chemicall
        </div>
        
        <?php if ($aviso): ?>
            <div class="alert alert-warning"><?php echo e($aviso); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-custom">Entrar</button>
        </form>
        <div class="mt-3 text-center">
            <a href="solicitar_cadastro.php" class="text-decoration-none" style="color: #006233; font-weight: 500;">Solicitar Cadastro</a>
            <?php if (file_exists('esqueceu_senha.php')): ?>
                <span class="text-muted mx-2">|</span>
                <a href="esqueceu_senha.php" class="text-decoration-none text-muted">Esqueceu a senha?</a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>