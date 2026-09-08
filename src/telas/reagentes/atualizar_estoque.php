<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/ReagenteController.php';

$auth = new AuthController($conn);
$auth->exigirLogin('../login/index.php');

exigir_post();
csrf_exigir();

$id        = $_POST['id'] ?? null;
$quantidade = $_POST['quantidade'] ?? 0;
$operacao  = $_POST['operacao'] ?? '';

// Entrada de estoque é ato de gestão: restrita a administrador e gestor.
if ($operacao === 'adicionar' && !$auth->podeGerenciarEstoque()) {
    header('Location: index.php?error=' . urlencode('Você não tem permissão para adicionar estoque.'));
    exit();
}

$motivo = null;
if ($operacao === 'remover') {
    $motivoTipo = $_POST['motivo_tipo'] ?? '';
    if ($motivoTipo === 'outro') {
        $motivo = trim((string) ($_POST['motivo_outro'] ?? ''));
        $motivo = $motivo === '' ? 'Outro motivo' : mb_substr($motivo, 0, 255);
    } else {
        $motivo = $motivoTipo === 'vencimento' ? 'Vencimento do produto' : 'Uso em aula/pesquisa';
    }
}

$reagenteController = new ReagenteController($conn);
$resultado = $reagenteController->atualizarQuantidade($id, $quantidade, $operacao, $motivo);

if ($resultado['success']) {
    $msg = $operacao === 'adicionar'
        ? 'Estoque adicionado com sucesso!'
        : 'Item retirado do estoque com sucesso!';
    header('Location: index.php?msg=' . urlencode($msg));
} else {
    header('Location: index.php?error=' . urlencode($resultado['message']));
}
exit();
