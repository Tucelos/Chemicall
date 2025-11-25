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

    if ($id && $quantidade > 0 && in_array($operacao, ['adicionar', 'remover'])) {
        $reagenteController = new ReagenteController($conn);
        
        if ($reagenteController->atualizarQuantidade($id, $quantidade, $operacao)) {
            $msg = $operacao === 'adicionar' ? 'Estoque adicionado com sucesso!' : 'Item retirado do estoque com sucesso!';
            header('Location: index.php?msg=' . urlencode($msg));
            exit();
        }
    }
}

header('Location: index.php?error=Erro ao atualizar estoque');
exit();
?>
