<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/ReagenteController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated()) {
    header('Location: ../login/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $quantidade = $_POST['quantidade'] ?? 0;
    $operacao = $_POST['operacao'] ?? '';

    // Usuários comuns (não admin) não podem adicionar estoque
    if ($operacao === 'adicionar' && !$auth->isAdmin()) {
        header('Location: index.php?error=Acesso negado para adicionar estoque');
        exit();
    }

    if ($id && $quantidade > 0 && in_array($operacao, ['adicionar', 'remover'])) {
        $reagenteController = new ReagenteController($conn);
        
        $motivo = null;
        if ($operacao === 'remover') {
            $motivo_tipo = $_POST['motivo_tipo'] ?? '';
            if ($motivo_tipo === 'outro') {
                $motivo = $_POST['motivo_outro'] ?? 'Outro motivo';
            } else {
                $motivo = $motivo_tipo === 'vencimento' ? 'Vencimento do produto' : 'Uso em aula/pesquisa';
            }
        }

        if ($reagenteController->atualizarQuantidade($id, $quantidade, $operacao, $motivo)) {
            $msg = $operacao === 'adicionar' ? 'Estoque adicionado com sucesso!' : 'Item retirado do estoque com sucesso!';
            header('Location: index.php?msg=' . urlencode($msg));
            exit();
        }
    }
}

header('Location: index.php?error=Erro ao atualizar estoque');
exit();
?>
