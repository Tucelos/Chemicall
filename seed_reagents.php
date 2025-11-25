<?php
require_once 'src/db/db_connection.php';

// Mock session for logging if needed, or just insert manually
// Assuming admin user id is 1. Check if user 1 exists.
$adminId = 1;

$reagents = [
    [
        'nome' => 'Ácido Sulfúrico',
        'formula_quimica' => 'H2SO4',
        'massa_molar' => 98.079,
        'concentracao' => '98%',
        'densidade' => 1.84,
        'validade' => '2026-12-31',
        'fabricante' => 'Sigma-Aldrich',
        'condicao' => 'fechado',
        'numero_cas' => '7664-93-9',
        'numero_ncm' => '28070010',
        'numero_nota_fiscal' => 'NF-1001',
        'quantidade' => 5
    ],
    [
        'nome' => 'Ácido Clorídrico',
        'formula_quimica' => 'HCl',
        'massa_molar' => 36.46,
        'concentracao' => '37%',
        'densidade' => 1.18,
        'validade' => '2025-06-30',
        'fabricante' => 'Merck',
        'condicao' => 'aberto',
        'numero_cas' => '7647-01-0',
        'numero_ncm' => '28061020',
        'numero_nota_fiscal' => 'NF-1002',
        'quantidade' => 3
    ],
    [
        'nome' => 'Hidróxido de Sódio',
        'formula_quimica' => 'NaOH',
        'massa_molar' => 39.997,
        'concentracao' => '99%',
        'densidade' => 2.13,
        'validade' => '2027-01-15',
        'fabricante' => 'Neon',
        'condicao' => 'fechado',
        'numero_cas' => '1310-73-2',
        'numero_ncm' => '28151100',
        'numero_nota_fiscal' => 'NF-1003',
        'quantidade' => 10
    ],
    [
        'nome' => 'Etanol',
        'formula_quimica' => 'C2H6O',
        'massa_molar' => 46.07,
        'concentracao' => '99.5%',
        'densidade' => 0.789,
        'validade' => '2025-11-20',
        'fabricante' => 'Synth',
        'condicao' => 'fechado',
        'numero_cas' => '64-17-5',
        'numero_ncm' => '22071010',
        'numero_nota_fiscal' => 'NF-1004',
        'quantidade' => 20
    ],
    [
        'nome' => 'Acetona',
        'formula_quimica' => 'C3H6O',
        'massa_molar' => 58.08,
        'concentracao' => '99.5%',
        'densidade' => 0.784,
        'validade' => '2026-03-10',
        'fabricante' => 'Dinâmica',
        'condicao' => 'aberto',
        'numero_cas' => '67-64-1',
        'numero_ncm' => '29141100',
        'numero_nota_fiscal' => 'NF-1005',
        'quantidade' => 8
    ],
    [
        'nome' => 'Sulfato de Cobre',
        'formula_quimica' => 'CuSO4',
        'massa_molar' => 159.609,
        'concentracao' => '98%',
        'densidade' => 3.6,
        'validade' => '2028-05-22',
        'fabricante' => 'Vetec',
        'condicao' => 'fechado',
        'numero_cas' => '7758-98-7',
        'numero_ncm' => '28332510',
        'numero_nota_fiscal' => 'NF-1006',
        'quantidade' => 2
    ],
    [
        'nome' => 'Cloreto de Sódio',
        'formula_quimica' => 'NaCl',
        'massa_molar' => 58.44,
        'concentracao' => '99%',
        'densidade' => 2.16,
        'validade' => '2030-12-31',
        'fabricante' => 'Cinthia',
        'condicao' => 'aberto',
        'numero_cas' => '7647-14-5',
        'numero_ncm' => '25010020',
        'numero_nota_fiscal' => 'NF-1007',
        'quantidade' => 15
    ],
    [
        'nome' => 'Permanganato de Potássio',
        'formula_quimica' => 'KMnO4',
        'massa_molar' => 158.034,
        'concentracao' => '99%',
        'densidade' => 2.7,
        'validade' => '2025-08-15',
        'fabricante' => 'Labsynth',
        'condicao' => 'fechado',
        'numero_cas' => '7722-64-7',
        'numero_ncm' => '28416100',
        'numero_nota_fiscal' => 'NF-1008',
        'quantidade' => 4
    ],
    [
        'nome' => 'Ácido Acético',
        'formula_quimica' => 'CH3COOH',
        'massa_molar' => 60.052,
        'concentracao' => '99.7%',
        'densidade' => 1.05,
        'validade' => '2026-09-01',
        'fabricante' => 'Anidrol',
        'condicao' => 'aberto',
        'numero_cas' => '64-19-7',
        'numero_ncm' => '29152100',
        'numero_nota_fiscal' => 'NF-1009',
        'quantidade' => 6
    ],
    [
        'nome' => 'Nitrato de Prata',
        'formula_quimica' => 'AgNO3',
        'massa_molar' => 169.87,
        'concentracao' => '99.8%',
        'densidade' => 4.35,
        'validade' => '2025-12-12',
        'fabricante' => 'Sigma',
        'condicao' => 'fechado',
        'numero_cas' => '7761-88-8',
        'numero_ncm' => '28432100',
        'numero_nota_fiscal' => 'NF-1010',
        'quantidade' => 1
    ]
];

try {
    $conn->beginTransaction();

    $stmtReagente = $conn->prepare("INSERT INTO reagentes (nome, formula_quimica, massa_molar, concentracao, densidade, validade, fabricante, condicao, numero_cas, numero_ncm, numero_nota_fiscal, quantidade) 
            VALUES (:nome, :formula, :massa, :conc, :dens, :val, :fab, :cond, :cas, :ncm, :nf, :qtd)");

    $stmtLog = $conn->prepare("INSERT INTO movimentacoes (reagente_id, funcionario_id, tipo_movimentacao, quantidade) VALUES (:rid, :fid, :tipo, :qtd)");

    foreach ($reagents as $dados) {
        $stmtReagente->execute([
            ':nome' => $dados['nome'],
            ':formula' => $dados['formula_quimica'],
            ':massa' => $dados['massa_molar'],
            ':conc' => $dados['concentracao'],
            ':dens' => $dados['densidade'],
            ':val' => $dados['validade'],
            ':fab' => $dados['fabricante'],
            ':cond' => $dados['condicao'],
            ':cas' => $dados['numero_cas'],
            ':ncm' => $dados['numero_ncm'],
            ':nf' => $dados['numero_nota_fiscal'],
            ':qtd' => $dados['quantidade']
        ]);
        
        $id = $conn->lastInsertId();
        
        // Log creation
        $stmtLog->execute([
            ':rid' => $id,
            ':fid' => $adminId,
            ':tipo' => 'criacao',
            ':qtd' => $dados['quantidade']
        ]);
        
        echo "Inserted: " . $dados['nome'] . "\n";
    }

    $conn->commit();
    echo "All 10 reagents inserted successfully with logs.\n";

} catch (PDOException $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
