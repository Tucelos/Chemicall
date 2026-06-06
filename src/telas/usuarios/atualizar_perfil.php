<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/FuncionarioController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada ou usuário não autenticado.']);
    exit();
}

$userId = $_SESSION['user_id'];
$controller = new FuncionarioController($conn);
$usuario = $controller->buscarPorId($userId);

if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    exit();
}

$emailSecundario = $_POST['email_secundario'] ?? null;
$senhaAtual = $_POST['senha_atual'] ?? '';
$novaSenha = $_POST['nova_senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

// Sanitize secondary email
if ($emailSecundario !== null) {
    $emailSecundario = trim($emailSecundario);
    if (!empty($emailSecundario) && !filter_var($emailSecundario, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'E-mail secundário inválido.']);
        exit();
    }
}

$updateFields = [
    'nome' => $usuario['nome'],
    'matricula' => $usuario['matricula'],
    'email' => $usuario['email'],
    'email_secundario' => empty($emailSecundario) ? null : $emailSecundario,
    'tipo' => $usuario['tipo'],
    'status' => $usuario['status'],
    'cargo' => $usuario['cargo'],
    'acesso_controlados' => $usuario['acesso_controlados']
];

// If password change is requested
if (!empty($senhaAtual) || !empty($novaSenha) || !empty($confirmarSenha)) {
    if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
        echo json_encode(['success' => false, 'message' => 'Para alterar a senha, preencha a senha original, a nova senha e a confirmação.']);
        exit();
    }
    if (strlen($novaSenha) < 8) {
        echo json_encode(['success' => false, 'message' => 'A nova senha deve ter no mínimo 8 caracteres.']);
        exit();
    }
    if ($novaSenha !== $confirmarSenha) {
        echo json_encode(['success' => false, 'message' => 'A nova senha e a confirmação não coincidem.']);
        exit();
    }
    // Verify original password
    if (!password_verify($senhaAtual, $usuario['senha'])) {
        echo json_encode(['success' => false, 'message' => 'Senha original incorreta.']);
        exit();
    }
    $updateFields['senha'] = $novaSenha;
}

if ($controller->atualizar($userId, $updateFields)) {
    echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar perfil no banco de dados.']);
}
?>
