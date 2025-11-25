<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userType = $_SESSION['user_type'] ?? 'docente';
?>
<style>
    .navbar {
        background-color: #006233; /* Chemicall Green */
        padding: 10px 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .navbar-brand {
        color: white !important;
        font-weight: bold;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .nav-link {
        color: rgba(255,255,255,0.9) !important;
        font-size: 1.05rem;
        margin-right: 10px;
        transition: all 0.3s ease;
    }
    .nav-link:hover {
        color: white !important;
        background-color: rgba(255,255,255,0.1);
        border-radius: 5px;
    }
    .nav-link.active {
        font-weight: bold;
        background-color: rgba(255,255,255,0.2);
        border-radius: 5px;
    }
    .user-info {
        color: white;
        margin-right: 15px;
        font-weight: 500;
    }
    .logout-btn {
        color: #ffcccc;
        text-decoration: none;
        font-size: 1.1rem;
        transition: color 0.3s;
    }
    .logout-btn:hover {
        color: #ff9999;
    }
</style>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="../dashboard/index.php">
            <i class="fas fa-flask"></i> Chemicall
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../dashboard/index.php">
                        <i class="fas fa-home"></i> Início
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../reagentes/index.php">
                        <i class="fas fa-boxes"></i> Estoque
                    </a>
                </li>
                <?php if ($userType === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../relatorio/relatorios.php">
                        <i class="fas fa-file-alt"></i> Relatórios
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($userType === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../usuarios/index.php">
                        <i class="fas fa-users"></i> Usuários
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <span class="user-info">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>
                </span>
                <a href="../../componentes/logout.php" class="logout-btn" title="Sair">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </div>
</nav>
<div style="margin-top: 80px;"></div> <!-- Spacer for fixed header -->