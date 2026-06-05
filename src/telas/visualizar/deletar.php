<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated() || !$auth->isAdmin()) {
    header('Location: ../dashboard/index.php');
    exit();
}

$cod_insumo = $_GET['cod_insumo'] ?? null;

if ($cod_insumo) {
    try {
        $stmt = $conn->prepare("DELETE FROM insumo WHERE cod_insumo = :cod_insumo");
        $result = $stmt->execute([':cod_insumo' => $cod_insumo]);
        
        if ($result) {
            header("Location: ../estoque/estoque.php?msg=Deletado com Sucesso!");
            exit();
        } else {
            echo "Falhou ao deletar insumo.";
        }
    } catch (PDOException $e) {
        echo "Erro: " . htmlspecialchars($e->getMessage());
    }
} else {
    header("Location: ../estoque/estoque.php");
    exit();
}
?>