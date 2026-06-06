<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/FuncionarioController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated() || !$auth->isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem gerenciar solicitações.']);
    exit();
}

$id = $_POST['id'] ?? null;
$acao = $_POST['acao'] ?? '';

if (!$id || !in_array($acao, ['aprovar', 'rejeitar'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit();
}

$controller = new FuncionarioController($conn);

if ($acao === 'aprovar') {
    if ($controller->aprovarSolicitacao($id)) {
        echo json_encode(['success' => true, 'message' => 'Solicitação aprovada com sucesso! O usuário agora está ativo.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao aprovar solicitação.']);
    }
} elseif ($acao === 'rejeitar') {
    if ($controller->rejeitarSolicitacao($id)) {
        echo json_encode(['success' => true, 'message' => 'Solicitação rejeitada com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao rejeitar solicitação.']);
    }
}
?>
