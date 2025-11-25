<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated()) {
    header('Location: ../login/index.php');
    exit();
}

$userType = $_SESSION['user_type'] ?? 'docente';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card-action {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 15px;
            height: 100%;
            cursor: pointer;
        }
        .card-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #006233;
        }
        .card-title {
            font-weight: bold;
            color: #333;
        }
        .welcome-section {
            background: linear-gradient(135deg, #006233 0%, #004d28 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="welcome-section text-center">
        <div class="container">
            <h1 class="display-4">Bem-vindo ao Chemicall</h1>
            <p class="lead">Sistema de Gerenciamento de Reagentes Químicos</p>
        </div>
    </div>

    <div class="container">
        <div class="row g-4 justify-content-center">
            <!-- Card: Novo Reagente -->
            <?php if ($userType === 'admin'): ?>
            <div class="col-md-6 col-lg-3">
                <a href="../reagentes/form.php" class="text-decoration-none">
                    <div class="card card-action p-4 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-flask"></i>
                            </div>
                            <h5 class="card-title">Novo Reagente</h5>
                            <p class="card-text text-muted">Cadastrar novo item no estoque</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Card: Ver Estoque -->
            <div class="col-md-6 col-lg-3">
                <a href="../reagentes/index.php" class="text-decoration-none">
                    <div class="card card-action p-4 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <h5 class="card-title">Ver Estoque</h5>
                            <p class="card-text text-muted">Consultar e gerenciar reagentes</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card: Relatórios -->
            <?php if ($userType === 'admin'): ?>
            <div class="col-md-6 col-lg-3">
                <a href="../relatorio/relatorios.php" class="text-decoration-none">
                    <div class="card card-action p-4 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h5 class="card-title">Relatórios</h5>
                            <p class="card-text text-muted">Visualizar relatórios e estatísticas</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Card: Usuários -->
            <?php if ($userType === 'admin'): ?>
            <div class="col-md-6 col-lg-3">
                <a href="../usuarios/index.php" class="text-decoration-none">
                    <div class="card card-action p-4 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h5 class="card-title">Usuários</h5>
                            <p class="card-text text-muted">Gerenciar usuários do sistema</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
