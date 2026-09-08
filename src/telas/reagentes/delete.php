<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/ReagenteController.php';

$auth = new AuthController($conn);
$auth->exigirGestao('index.php');

// Exclusão só por POST com token: um GET podia ser disparado por um simples
// <img src="delete.php?id=..."> em outro site (CSRF).
exigir_post();
csrf_exigir();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php?error=' . urlencode('Item inválido.'));
    exit();
}

$controller = new ReagenteController($conn);

if ($controller->deletar($id)) {
    header('Location: index.php?msg=' . urlencode('Reagente excluído com sucesso!'));
} else {
    header('Location: index.php?error=' . urlencode('Não foi possível excluir o reagente.'));
}
exit();
