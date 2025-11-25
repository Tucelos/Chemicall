<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/ReagenteController.php';
require_once __DIR__ . '/../../db/db_connection.php';

$auth = new AuthController($conn);
if (!$auth->isAuthenticated()) {
    header('Location: ../login/index.php');
    exit();
}

$controller = new ReagenteController($conn);
$id = $_GET['id'] ?? null;
$reagente = [];

if ($id) {
    $reagente = $controller->buscarPorId($id);
    if (!$reagente) {
        header('Location: index.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome' => $_POST['nome'],
        'formula_quimica' => $_POST['formula_quimica'],
        'massa_molar' => $_POST['massa_molar'],
        'concentracao' => $_POST['concentracao'],
        'densidade' => $_POST['densidade'],
        'validade' => $_POST['validade'],
        'fabricante' => $_POST['fabricante'],
        'numero_cas' => $_POST['numero_cas'],
        'numero_ncm' => $_POST['numero_ncm'],
        'numero_nota_fiscal' => $_POST['numero_nota_fiscal'],
        'quantidade' => $_POST['quantidade']
    ];

    if ($id) {
        $controller->atualizar($id, $dados);
    } else {
        $controller->criar($dados);
    }
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Editar' : 'Novo'; ?> Reagente - Chemicall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include '../../componentes/header.php'; ?>

    <div class="container mt-4 mb-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="mb-0"><?php echo $id ? 'Editar' : 'Novo'; ?> Reagente</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" name="nome" class="form-control" required value="<?php echo $reagente['nome'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fórmula Química</label>
                            <input type="text" name="formula_quimica" class="form-control" value="<?php echo $reagente['formula_quimica'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Número CAS</label>
                            <input type="text" name="numero_cas" class="form-control" value="<?php echo $reagente['numero_cas'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Massa Molar (g/mol)</label>
                            <input type="number" step="0.01" name="massa_molar" class="form-control" value="<?php echo $reagente['massa_molar'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Concentração</label>
                            <input type="text" name="concentracao" class="form-control" value="<?php echo $reagente['concentracao'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Densidade (g/cm³)</label>
                            <input type="number" step="0.001" name="densidade" class="form-control" value="<?php echo $reagente['densidade'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Validade *</label>
                            <input type="date" name="validade" class="form-control" required value="<?php echo $reagente['validade'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fabricante</label>
                            <input type="text" name="fabricante" class="form-control" value="<?php echo $reagente['fabricante'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">NCM</label>
                            <input type="text" name="numero_ncm" class="form-control" value="<?php echo $reagente['numero_ncm'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nota Fiscal</label>
                            <input type="text" name="numero_nota_fiscal" class="form-control" value="<?php echo $reagente['numero_nota_fiscal'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantidade (Unidades) *</label>
                            <input type="number" name="quantidade" class="form-control" required value="<?php echo $reagente['quantidade'] ?? '0'; ?>">
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
