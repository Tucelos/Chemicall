<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/ReagenteController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated()) {
    header('Location: ../login/index.php');
    exit();
}

$isAdmin = $auth->isAdmin();
$isGestor = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'gestor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $ids = $_POST['ids'] ?? [];

    if (empty($ids)) {
        header('Location: index.php?error=Nenhum item selecionado');
        exit();
    }

    $reagenteController = new ReagenteController($conn);

    if ($acao === 'excluir') {
        // Apenas admin e gestor podem excluir
        if (!$isAdmin && !$isGestor) {
            header('Location: index.php?error=Acesso negado');
            exit();
        }
        foreach ($ids as $id) {
            $reagenteController->deletar($id);
        }
        header('Location: index.php?msg=Itens excluídos com sucesso');
        exit();
    } elseif ($acao === 'retirar') {
        $quantidades = $_POST['quantidades'] ?? [];
        $motivo_tipo = $_POST['motivo_tipo'] ?? '';
        
        $motivo = null;
        if ($motivo_tipo === 'outro') {
            $motivo = $_POST['motivo_outro'] ?? 'Outro motivo';
        } else {
            $motivo = $motivo_tipo === 'vencimento' ? 'Vencimento do produto' : 'Uso em aula/pesquisa';
        }

        $algumRetirado = false;
        foreach ($quantidades as $id => $qtd) {
            $qtd = (int)$qtd;
            if ($qtd > 0 && in_array($id, $ids)) {
                $reagenteController->atualizarQuantidade($id, $qtd, 'remover', $motivo);
                $algumRetirado = true;
            }
        }
        
        $msg = $algumRetirado ? 'Itens retirados com sucesso' : 'Nenhuma quantidade foi alterada';
        header('Location: index.php?msg=' . urlencode($msg));
        exit();
    }
}

header('Location: index.php');
exit();
?>
