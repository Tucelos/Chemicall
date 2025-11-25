<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../styles/relatorio.css">
    <title>Relatórios</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .card-custom {
            width: 300px;
            margin: 10px;
        }
    </style>
</head>

<body>
    <?php
    include "../../db/db_connection.php";

// Verify admin access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../dashboard/index.php');
    exit();
}
    include_once('../../componentes/header.php');

    ?>


    <!-- Cards de relatórios disponíveis-->
    <div class="container" style="padding-top:80px;">
        <h1 class="title" style="padding-bottom: 20px;">Relatórios</h1>
        <div class="row justify-content-center cards-row">
            <!-- Card: Inventário Completo -->
            <div class="col-md-6">
                <div class="card card-custom">
                    <div class="card-body text-center">
                        <div class="card-icon mb-3">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h5 class="card-title">Relatório de Estoque e Movimentações</h5>
                        <p class="card-text">Gere um relatório completo do estoque atual e histórico de movimentações.</p>
                        
                        <form action="pdf/inventario.php" method="GET" target="_blank" class="mt-3">
                            <div class="mb-3">
                                <label for="periodo" class="form-label">Período das Movimentações:</label>
                                <select name="periodo" id="periodo" class="form-select">
                                    <option value="7">Última Semana</option>
                                    <option value="30" selected>Último Mês</option>
                                    <option value="90">Últimos 3 Meses</option>
                                    <option value="180">Últimos 6 Meses</option>
                                    <option value="365">Último Ano</option>
                                    <option value="all">Todo o Período</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-file-pdf"></i> Gerar PDF
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card card-custom" style="padding-left: 3px;padding-right: 3px;">
                <div class="card-body text-center">
                    <div class="text-center mb-3">
                        <i class="fas fa-chart-pie fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title">Estatísticas</h5>
                    <p class="card-text">Este relatório mostra quais são os itens mais e menos consumidos.</p>
                    
                    <form action="estatisticas.php" method="GET" class="mt-3">
                        <div class="mb-3">
                            <label for="periodo_stats" class="form-label">Período:</label>
                            <select name="periodo" id="periodo_stats" class="form-select">
                                <option value="7">Última Semana</option>
                                <option value="30" selected>Último Mês</option>
                                <option value="90">Últimos 3 Meses</option>
                                <option value="180">Últimos 6 Meses</option>
                                <option value="365">Último Ano</option>
                                <option value="all">Todo o Período</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-chart-bar"></i> Abrir Estatísticas
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>