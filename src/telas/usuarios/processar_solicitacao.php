<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/FuncionarioController.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthController($conn);
if (!$auth->isAuthenticated() || !$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Apenas administradores podem gerenciar solicitações.',
    ]);
    exit();
}

exigir_post();
csrf_exigir('json');

$id   = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$acao = $_POST['acao'] ?? '';

if (!$id || !in_array($acao, ['aprovar', 'rejeitar'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit();
}

$controller = new FuncionarioController($conn);

if ($acao === 'aprovar') {
    $ok = $controller->aprovarSolicitacao($id);
    echo json_encode($ok
        ? ['success' => true, 'message' => 'Solicitação aprovada com sucesso! O usuário agora está ativo.']
        : ['success' => false, 'message' => 'Solicitação não encontrada ou já processada.']);
    exit();
}

$ok = $controller->rejeitarSolicitacao($id);
echo json_encode($ok
    ? ['success' => true, 'message' => 'Solicitação rejeitada com sucesso!']
    : ['success' => false, 'message' => 'Solicitação não encontrada ou já processada.']);
