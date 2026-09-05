<?php
require_once __DIR__ . '/../db/db_connection.php';
require_once __DIR__ . '/../controllers/FuncionarioController.php';

// O cabeçalho só é renderizado dentro de páginas autenticadas; sem sessão não
// há nada a exibir.
if (empty($_SESSION['user_id'])) {
    return;
}

$userType = $_SESSION['user_type'] ?? 'user';
$userId = (int) $_SESSION['user_id'];
$solicitacoesPendentes = [];

$funcionarioCtrl = new FuncionarioController($conn);
$usuarioLogado = $funcionarioCtrl->buscarPorId($userId);

if ($userType === 'admin') {
    $solicitacoesPendentes = $funcionarioCtrl->listarSolicitacoesPendentes();
}
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
    .navbar-nav .nav-link,
    .navbar .dropdown .dropdown-toggle {
        color: rgba(255,255,255,0.9) !important;
        font-size: 1.05rem;
        margin-right: 10px;
        transition: all 0.3s ease;
    }
    .navbar-nav .nav-link:hover,
    .navbar .dropdown .dropdown-toggle:hover {
        color: white !important;
        background-color: rgba(255,255,255,0.1);
        border-radius: 5px;
    }
    .navbar-nav .nav-link.active {
        font-weight: bold;
        background-color: rgba(255,255,255,0.2);
        border-radius: 5px;
    }
    .dropdown-toggle::after {
        vertical-align: middle;
    }
    .dropdown-item:active {
        background-color: #006233;
    }
    .nav-tabs .nav-link {
        color: #495057 !important; /* Visible dark gray for unselected tabs */
        border: none;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }
    .nav-tabs .nav-link:hover {
        color: #006233 !important;
        border-bottom: 3px solid #dee2e6;
    }
    .nav-tabs .nav-link.active {
        color: #006233 !important;
        border-bottom: 3px solid #006233 !important;
        background-color: transparent !important;
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
                <?php if ($userType === 'admin' || $userType === 'gestor'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../relatorio/relatorios.php">
                        <i class="fas fa-file-alt"></i> Relatórios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../relatorio/estatisticas.php">
                        <i class="fas fa-chart-pie"></i> Estatísticas
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($userType === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../usuarios/index.php">
                        <i class="fas fa-users"></i> Usuários
                        <?php if (count($solicitacoesPendentes) > 0): ?>
                            <span class="badge bg-danger ms-1 animate__animated animate__pulse animate__infinite" id="nav-users-badge">
                                <?php echo count($solicitacoesPendentes); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer; background: none; border: none; font-weight: 500; text-decoration: none;">
                        <i class="fas fa-user-circle me-1" style="font-size: 1.25rem;"></i>
                        <?php echo htmlspecialchars($usuarioLogado['nome'] ?? $_SESSION['user_name'] ?? 'Usuário'); ?>
                        <?php if ($userType === 'admin' && count($solicitacoesPendentes) > 0): ?>
                            <span class="badge bg-danger ms-1" id="header-user-badge">
                                <?php echo count($solicitacoesPendentes); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li>
                            <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#perfilModal">
                                <i class="fas fa-user-cog me-2 text-muted"></i> Perfil
                                <?php if ($userType === 'admin' && count($solicitacoesPendentes) > 0): ?>
                                    <span class="badge bg-danger ms-1" id="dropdown-profile-badge">
                                        <?php echo count($solicitacoesPendentes); ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger py-2" href="../../componentes/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
<div style="margin-top: 80px;"></div> <!-- Spacer for fixed header -->

<!-- Modal de Perfil -->
<div class="modal fade" id="perfilModal" tabindex="-1" aria-labelledby="perfilModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #006233;">
                <h5 class="modal-title" id="perfilModalLabel">
                    <i class="fas fa-user-circle me-2"></i> Meu Perfil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <?php if ($userType === 'admin'): ?>
                <!-- Nav Tabs for Admin -->
                <ul class="nav nav-tabs nav-fill bg-light" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3 border-0 border-bottom" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados-tab-pane" type="button" role="tab" aria-controls="dados-tab-pane" aria-selected="true" style="font-weight: 600;">
                            <i class="fas fa-id-card me-2"></i> Meus Dados e Senha
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 border-0 border-bottom d-flex align-items-center justify-content-center" id="solicitacoes-tab" data-bs-toggle="tab" data-bs-target="#solicitacoes-tab-pane" type="button" role="tab" aria-controls="solicitacoes-tab-pane" aria-selected="false" style="font-weight: 600;">
                            <i class="fas fa-users-cog me-2"></i> Solicitações de Cadastro
                            <?php if (count($solicitacoesPendentes) > 0): ?>
                                <span class="badge bg-danger ms-2" id="solicitacoes-badge-count">
                                    <?php echo count($solicitacoesPendentes); ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    </li>
                </ul>
                <?php endif; ?>

                <div class="tab-content <?php echo ($userType === 'admin') ? '' : 'p-4'; ?>" id="profileTabsContent">
                    <!-- Tab Pane 1 (Dados + Senha) -->
                    <div class="<?php echo ($userType === 'admin') ? 'tab-pane fade show active p-4' : ''; ?>" id="dados-tab-pane" role="tabpanel" aria-labelledby="dados-tab" tabindex="0">
                        <form id="formAtualizarPerfil" method="POST">
                            <?php echo csrf_field(); ?>
                            <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-info-circle text-muted me-1"></i> Dados Cadastrais</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Nome Completo</label>
                                    <div class="form-control bg-light border-0 py-2"><?php echo htmlspecialchars($usuarioLogado['nome'] ?? ''); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Matrícula</label>
                                    <div class="form-control bg-light border-0 py-2"><?php echo htmlspecialchars($usuarioLogado['matricula'] ?? 'Não informada'); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Cargo / Função</label>
                                    <div class="form-control bg-light border-0 py-2"><?php echo htmlspecialchars($usuarioLogado['cargo'] ?? 'Não informado'); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Email Principal</label>
                                    <div class="form-control bg-light border-0 py-2"><?php echo htmlspecialchars($usuarioLogado['email'] ?? ''); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Perfil de Acesso</label>
                                    <div>
                                        <?php 
                                        $tipo = $usuarioLogado['tipo'] ?? 'user';
                                        if ($tipo === 'admin') echo '<span class="badge bg-danger px-3 py-2 text-white">Administrador</span>';
                                        elseif ($tipo === 'gestor') echo '<span class="badge bg-success px-3 py-2 text-white">Gestor</span>';
                                        else echo '<span class="badge bg-primary px-3 py-2 text-white">Usuário Comum</span>';
                                        ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Acesso a Itens Controlados</label>
                                    <div>
                                        <?php 
                                        $controlados = $usuarioLogado['acesso_controlados'] ?? 0;
                                        if ($tipo === 'admin' || $tipo === 'gestor' || $controlados == 1) {
                                            echo '<span class="badge bg-success px-3 py-2 text-white"><i class="fas fa-check-circle me-1"></i> Autorizado</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary px-3 py-2 text-white"><i class="fas fa-times-circle me-1"></i> Não Autorizado</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-edit text-muted me-1"></i> Atualizar Dados</h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="email_secundario" class="form-label fw-semibold">Email Secundário</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fas fa-envelope text-muted"></i></span>
                                        <input type="email" class="form-control" id="email_secundario" name="email_secundario" value="<?php echo htmlspecialchars($usuarioLogado['email_secundario'] ?? ''); ?>" placeholder="Adicione um email secundário">
                                    </div>
                                    <div class="form-text">Usado para receber notificações e alertas adicionais.</div>
                                </div>
                                <div class="col-md-12">
                                    <div class="card border-0 bg-light p-3 mt-3">
                                        <h6 class="fw-semibold mb-2" style="color: #006233;"><i class="fas fa-key me-1"></i> Alterar Senha (Opcional)</h6>
                                        <p class="text-muted small mb-3">Deixe os campos abaixo em branco se não quiser alterar sua senha. A nova senha deve ter no mínimo 8 caracteres.</p>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label for="senha_atual" class="form-label fw-semibold">Senha Original</label>
                                                <input type="password" class="form-control bg-white" id="senha_atual" name="senha_atual" placeholder="Digite sua senha atual">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="nova_senha" class="form-label fw-semibold">Nova Senha</label>
                                                <input type="password" class="form-control bg-white" id="nova_senha" name="nova_senha" placeholder="Mínimo 8 caracteres">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="confirmar_senha" class="form-label fw-semibold">Confirmar Nova Senha</label>
                                                <input type="password" class="form-control bg-white" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme a nova senha">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="perfilAlert" class="alert d-none mt-3"></div>
                            <div class="text-end mt-4">
                                <button type="submit" class="btn text-white px-4" style="background-color: #006233;">Salvar Alterações</button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab Pane 2: Solicitações (apenas admin) -->
                    <?php if ($userType === 'admin'): ?>
                    <div class="tab-pane fade p-4" id="solicitacoes-tab-pane" role="tabpanel" aria-labelledby="solicitacoes-tab" tabindex="0">
                        <div id="solicitacoesContainer">
                            <?php if (count($solicitacoesPendentes) === 0): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                    <h5>Nenhuma solicitação de cadastro pendente!</h5>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Matrícula</th>
                                                <th>Email</th>
                                                <th>Cargo</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($solicitacoesPendentes as $sol): ?>
                                            <tr id="linha-solicitacao-<?php echo $sol['cod_funcionario']; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($sol['nome']); ?></strong>
                                                </td>
                                                <td><code><?php echo htmlspecialchars($sol['matricula'] ?? ''); ?></code></td>
                                                <td><?php echo htmlspecialchars($sol['email']); ?></td>
                                                <td><?php echo htmlspecialchars($sol['cargo'] ?? ''); ?></td>
                                                <td class="text-center" style="white-space: nowrap;">
                                                    <button type="button" class="btn btn-sm btn-success me-1 btn-acao-solicitacao" onclick="processarSolicitacao(<?php echo $sol['cod_funcionario']; ?>, 'aprovar')">
                                                        <i class="fas fa-check me-1"></i> Aprovar
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger btn-acao-solicitacao" onclick="processarSolicitacao(<?php echo $sol['cod_funcionario']; ?>, 'rejeitar')">
                                                        <i class="fas fa-times me-1"></i> Rejeitar
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div id="solicitacoesAlert" class="alert d-none mt-3"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Submit formAtualizarPerfil
    const formPerfil = document.getElementById('formAtualizarPerfil');
    if (formPerfil) {
        formPerfil.addEventListener('submit', function(e) {
            e.preventDefault();
            const alertDiv = document.getElementById('perfilAlert');
            alertDiv.className = 'alert d-none';
            
            const formData = new FormData(this);
            
            fetch('../../telas/usuarios/atualizar_perfil.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertDiv.className = 'alert alert-success';
                    alertDiv.textContent = data.message;
                    
                    // Clear password inputs
                    const inputs = ['senha_atual', 'nova_senha', 'confirmar_senha'];
                    inputs.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.value = '';
                    });

                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.textContent = data.message;
                }
            })
            .catch(error => {
                alertDiv.className = 'alert alert-danger';
                alertDiv.textContent = 'Erro ao processar a requisição.';
                console.error(error);
            });
        });
    }
});

// Process pending registrations
function processarSolicitacao(id, acao) {
    const alertDiv = document.getElementById('solicitacoesAlert');
    if (alertDiv) {
        alertDiv.className = 'alert d-none';
    }
    
    const row = document.getElementById('linha-solicitacao-' + id);
    if (row) {
        const buttons = row.querySelectorAll('.btn-acao-solicitacao');
        buttons.forEach(btn => btn.disabled = true);
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('acao', acao);
    formData.append('_csrf', <?php echo json_encode(csrf_token()); ?>);

    fetch('../../telas/usuarios/processar_solicitacao.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (alertDiv) {
                alertDiv.className = 'alert alert-success';
                alertDiv.textContent = data.message;
            }
            
            if (row) {
                row.remove();
            }

            const container = document.getElementById('solicitacoesContainer');
            const remainingRows = container ? container.querySelectorAll('tbody tr') : [];
            
            const headerBadge = document.getElementById('header-user-badge');
            const navBadge = document.getElementById('nav-users-badge');
            const dropdownBadge = document.getElementById('dropdown-profile-badge');
            const tabBadge = document.getElementById('solicitacoes-badge-count');
            
            const newCount = remainingRows.length;
            if (newCount > 0) {
                if (headerBadge) headerBadge.textContent = newCount;
                if (navBadge) navBadge.textContent = newCount;
                if (dropdownBadge) dropdownBadge.textContent = newCount;
                if (tabBadge) tabBadge.textContent = newCount;
            } else {
                if (headerBadge) headerBadge.remove();
                if (navBadge) navBadge.remove();
                if (dropdownBadge) dropdownBadge.remove();
                if (tabBadge) tabBadge.remove();
                
                if (container) {
                    container.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <h5>Nenhuma solicitação de cadastro pendente!</h5>
                        </div>
                    `;
                }
            }
        } else {
            if (alertDiv) {
                alertDiv.className = 'alert alert-danger';
                alertDiv.textContent = data.message;
            }
            if (row) {
                const buttons = row.querySelectorAll('.btn-acao-solicitacao');
                buttons.forEach(btn => btn.disabled = false);
            }
        }
    })
    .catch(error => {
        if (alertDiv) {
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = 'Erro ao processar a requisição.';
        }
        console.error(error);
        if (row) {
            const buttons = row.querySelectorAll('.btn-acao-solicitacao');
            buttons.forEach(btn => btn.disabled = false);
        }
    });
}
</script>