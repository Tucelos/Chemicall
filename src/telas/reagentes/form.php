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
        'quantidade' => $_POST['quantidade'],
        'controlado' => isset($_POST['controlado']) ? 1 : 0
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
                            <label class="form-label d-flex align-items-center justify-content-between">
                                <span>Nome *</span>
                                <button type="button" class="btn btn-link p-0 text-decoration-none text-muted small" data-bs-toggle="modal" data-bs-target="#modalHelpPubChem" title="Como funciona o preenchimento automático?">
                                    <i class="far fa-question-circle"></i> Como funciona?
                                </button>
                            </label>
                            <div class="input-group">
                                <input type="text" name="nome" id="nome_reagente" class="form-control" required value="<?php echo $reagente['nome'] ?? ''; ?>">
                                <button class="btn btn-outline-success" type="button" id="btn_buscar_pubchem" title="Buscar dados no PubChem">
                                    <i class="fas fa-magic"></i> Auto-completar
                                </button>
                            </div>
                            <div id="pubchem_status" class="form-text mt-1" style="display: none;"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fórmula Química</label>
                            <input type="text" name="formula_quimica" id="formula_quimica" class="form-control" value="<?php echo $reagente['formula_quimica'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Número CAS</label>
                            <input type="text" name="numero_cas" id="numero_cas" class="form-control" value="<?php echo $reagente['numero_cas'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Massa Molar (g/mol)</label>
                            <input type="number" step="0.01" name="massa_molar" id="massa_molar" class="form-control" value="<?php echo $reagente['massa_molar'] ?? ''; ?>">
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
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="controlado" id="controlado" <?php echo (isset($reagente['controlado']) && $reagente['controlado']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="controlado">
                                    Produto Controlado (Polícia Federal / Exército)
                                </label>
                            </div>
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

    <!-- Modal de Ajuda do PubChem -->
    <div class="modal fade" id="modalHelpPubChem" tabindex="-1" aria-labelledby="modalHelpPubChemLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalHelpPubChemLabel"><i class="fas fa-info-circle"></i> Preenchimento Automático via PubChem</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>O recurso de <strong>Auto-completar</strong> busca propriedades químicas diretamente no banco de dados público e internacional do <strong>PubChem (NIH)</strong>.</p>
                    
                    <h6>Como usar:</h6>
                    <ol>
                        <li>Digite o <strong>nome em inglês</strong> do composto (ex: <em>Acetone</em>, <em>Sulfuric Acid</em>, <em>Sodium Hydroxide</em>) ou o <strong>número CAS</strong> (ex: <em>67-64-1</em>) no campo <strong>Nome</strong>.</li>
                        <li>Clique no botão <strong>Auto-completar</strong>.</li>
                        <li>O sistema preencherá automaticamente os campos de <strong>Fórmula Química</strong>, <strong>Número CAS</strong> e <strong>Massa Molar</strong> se localizados.</li>
                    </ol>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Dica de busca:</strong> A base do PubChem é internacional. Para obter os melhores resultados pelo nome, digite-o em <strong>inglês</strong>. Buscar diretamente pelo <strong>Número CAS</strong> é 100% preciso e funciona instantaneamente!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('btn_buscar_pubchem').addEventListener('click', function() {
        const termoBusca = document.getElementById('nome_reagente').value.trim();
        const statusText = document.getElementById('pubchem_status');
        const btn = document.getElementById('btn_buscar_pubchem');
        
        if (!termoBusca) {
            alert('Por favor, digite o nome do reagente (em inglês) ou o CAS no campo de Nome para buscar.');
            return;
        }

        statusText.style.display = 'block';
        statusText.className = "form-text mt-1 text-muted";
        statusText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando no PubChem...';
        btn.disabled = true;
        
        const urlProperties = `https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/name/${encodeURIComponent(termoBusca)}/property/MolecularFormula,MolecularWeight/JSON`;
        
        fetch(urlProperties)
            .then(response => {
                if (!response.ok) throw new Error('Produto não encontrado');
                return response.json();
            })
            .then(data => {
                const props = data.PropertyTable.Properties[0];
                const cid = props.CID;
                
                // Preenche os campos da tela
                document.getElementById('formula_quimica').value = props.MolecularFormula || '';
                document.getElementById('massa_molar').value = props.MolecularWeight || '';
                
                statusText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dados básicos encontrados! Buscando CAS...';
                
                // Busca Sinônimos para extrair o Número CAS
                return fetch(`https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/${cid}/synonyms/JSON`);
            })
            .then(response => {
                if (!response.ok) {
                    return { InformationList: { Information: [{ Synonym: [] }] } };
                }
                return response.json();
            })
            .then(data => {
                const synonyms = data.InformationList.Information[0].Synonym || [];
                // Regex para validar formato CAS
                const casRegex = /^\d{2,7}-\d{2}-\d$/;
                const casEncontrado = synonyms.find(syn => casRegex.test(syn));
                
                if (casEncontrado) {
                    document.getElementById('numero_cas').value = casEncontrado;
                    statusText.className = "form-text mt-1 text-success";
                    statusText.innerHTML = '<i class="fas fa-check-circle"></i> Sucesso! Fórmulas, Massa Molar e CAS importados.';
                } else {
                    statusText.className = "form-text mt-1 text-warning";
                    statusText.innerHTML = '<i class="fas fa-exclamation-circle"></i> Sucesso! Fórmula e Massa Molar importados (CAS não localizado).';
                }
            })
            .catch(error => {
                console.error(error);
                statusText.className = "form-text mt-1 text-danger";
                statusText.innerHTML = '<i class="fas fa-times-circle"></i> Composto não localizado ou erro na API do PubChem.';
            })
            .finally(() => {
                btn.disabled = false;
            });
    });
    </script>
</body>
</html>
