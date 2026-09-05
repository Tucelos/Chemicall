<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/FuncionarioController.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthController($conn);
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit();
}

exigir_post();
csrf_exigir('json');

$userId = (int) $_SESSION['user_id'];
$controller = new FuncionarioController($conn);
$usuario = $controller->buscarPorId($userId);

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    exit();
}

$emailSecundario = trim((string) ($_POST['email_secundario'] ?? ''));
$senhaAtual      = (string) ($_POST['senha_atual'] ?? '');
$novaSenha       = (string) ($_POST['nova_senha'] ?? '');
$confirmarSenha  = (string) ($_POST['confirmar_senha'] ?? '');

if ($emailSecundario !== '' && !filter_var($emailSecundario, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail secundário inválido.']);
    exit();
}

$senhaParaSalvar = null;

if ($senhaAtual !== '' || $novaSenha !== '' || $confirmarSenha !== '') {
    if ($senhaAtual === '' || $novaSenha === '' || $confirmarSenha === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Para alterar a senha, preencha a senha original, a nova senha e a confirmação.',
        ]);
        exit();
    }
    if ($novaSenha !== $confirmarSenha) {
        echo json_encode(['success' => false, 'message' => 'A nova senha e a confirmação não coincidem.']);
        exit();
    }
    if (!password_verify($senhaAtual, $usuario['senha'])) {
        echo json_encode(['success' => false, 'message' => 'Senha original incorreta.']);
        exit();
    }
    if ($erro = FuncionarioController::validarSenha($novaSenha)) {
        echo json_encode(['success' => false, 'message' => $erro]);
        exit();
    }
    $senhaParaSalvar = $novaSenha;
}

// atualizarPerfil altera apenas e-mail secundário e senha: perfil, status e
// permissão de controlados não são editáveis pelo próprio usuário.
$resultado = $controller->atualizarPerfil(
    $userId,
    $emailSecundario === '' ? null : $emailSecundario,
    $senhaParaSalvar
);

if ($resultado['success'] && $senhaParaSalvar !== null) {
    // Troca de senha renova o identificador de sessão.
    session_regenerate_id(true);
}

echo json_encode($resultado);
