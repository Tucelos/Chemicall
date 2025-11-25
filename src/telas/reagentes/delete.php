<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/ReagenteController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated() || !$auth->isAdmin()) {
    header('Location: index.php');
    exit();
}

$controller = new ReagenteController($conn);
$id = $_GET['id'] ?? null;

if ($id) {
    $controller->deletar($id);
}

header('Location: index.php');
exit();
?>
