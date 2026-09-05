<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/ReagenteController.php';

$auth = new AuthController($conn);
$auth->exigirLogin('../login/index.php');

exigir_post();
csrf_exigir();

$acao = $_POST['acao'] ?? '';
$ids  = $_POST['ids'] ?? [];

if (!is_array($ids) || empty($ids)) {
    header('Location: index.php?error=' . urlencode('Nenhum item selecionado'));
    exit();
}

// Normaliza a seleção para inteiros válidos e sem duplicatas.
$ids = array_values(array_unique(array_filter(
    array_map(static fn($v) => filter_var($v, FILTER_VALIDATE_INT), $ids),
    static fn($v) => $v !== false && $v > 0
)));

if (empty($ids)) {
    header('Location: index.php?error=' . urlencode('Seleção inválida'));
    exit();
}

$reagenteController = new ReagenteController($conn);

if ($acao === 'excluir') {
    if (!$auth->podeGerenciarEstoque()) {
        header('Location: index.php?error=' . urlencode('Acesso negado'));
        exit();
    }

    $excluidos = 0;
    foreach ($ids as $id) {
        if ($reagenteController->deletar($id)) {
            $excluidos++;
        }
    }

    $msg = $excluidos === count($ids)
        ? "{$excluidos} item(ns) excluído(s) com sucesso"
        : "{$excluidos} de " . count($ids) . ' item(ns) excluído(s); os demais falharam';
    header('Location: index.php?msg=' . urlencode($msg));
    exit();
}

if ($acao === 'retirar') {
    $quantidades = $_POST['quantidades'] ?? [];
    $motivoTipo  = $_POST['motivo_tipo'] ?? '';

    if ($motivoTipo === 'outro') {
        $motivo = trim((string) ($_POST['motivo_outro'] ?? ''));
        $motivo = $motivo === '' ? 'Outro motivo' : mb_substr($motivo, 0, 255);
    } else {
        $motivo = $motivoTipo === 'vencimento' ? 'Vencimento do produto' : 'Uso em aula/pesquisa';
    }

    $retirados = 0;
    $falhas    = [];

    foreach ($ids as $id) {
        $qtd = $quantidades[$id] ?? null;
        if ($qtd === null || $qtd === '' || (int) $qtd <= 0) {
            continue;
        }

        $resultado = $reagenteController->atualizarQuantidade($id, $qtd, 'remover', $motivo);
        if ($resultado['success']) {
            $retirados++;
        } else {
            $falhas[] = "#{$id}: {$resultado['message']}";
        }
    }

    if (!empty($falhas)) {
        $resumo = $retirados > 0 ? "{$retirados} item(ns) retirado(s). Falhas — " : 'Nenhum item retirado. ';
        header('Location: index.php?error=' . urlencode($resumo . implode(' | ', array_slice($falhas, 0, 5))));
        exit();
    }

    $msg = $retirados > 0
        ? "{$retirados} item(ns) retirado(s) com sucesso"
        : 'Nenhuma quantidade foi informada';
    header('Location: index.php?msg=' . urlencode($msg));
    exit();
}

header('Location: index.php');
exit();
