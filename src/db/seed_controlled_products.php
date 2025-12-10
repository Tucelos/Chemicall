<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/../controllers/ReagenteController.php';

$controller = new ReagenteController($conn);

$controlledProducts = [
    [
        'nome' => 'Ácido Sulfúrico P.A.',
        'formula_quimica' => 'H2SO4',
        'massa_molar' => '98.08',
        'concentracao' => '98%',
        'densidade' => '1.84',
        'validade' => date('Y-m-d', strtotime('+1 year')),
        'fabricante' => 'Merck',
        'numero_cas' => '7664-93-9',
        'numero_ncm' => '2807.00.10',
        'numero_nota_fiscal' => 'NF-12345',
        'quantidade' => 10,
        'controlado' => 1
    ],
    [
        'nome' => 'Acetona P.A.',
        'formula_quimica' => 'C3H6O',
        'massa_molar' => '58.08',
        'concentracao' => '99.5%',
        'densidade' => '0.79',
        'validade' => date('Y-m-d', strtotime('+2 years')),
        'fabricante' => 'Sigma-Aldrich',
        'numero_cas' => '67-64-1',
        'numero_ncm' => '2914.11.00',
        'numero_nota_fiscal' => 'NF-67890',
        'quantidade' => 25,
        'controlado' => 1
    ],
    [
        'nome' => 'Tolueno',
        'formula_quimica' => 'C7H8',
        'massa_molar' => '92.14',
        'concentracao' => '99%',
        'densidade' => '0.87',
        'validade' => date('Y-m-d', strtotime('+18 months')),
        'fabricante' => 'Synth',
        'numero_cas' => '108-88-3',
        'numero_ncm' => '2902.30.00',
        'numero_nota_fiscal' => 'NF-11223',
        'quantidade' => 5,
        'controlado' => 1
    ],
    [
        'nome' => 'Clorofórmio',
        'formula_quimica' => 'CHCl3',
        'massa_molar' => '119.38',
        'concentracao' => '99.8%',
        'densidade' => '1.49',
        'validade' => date('Y-m-d', strtotime('+1 year')),
        'fabricante' => 'Neon',
        'numero_cas' => '67-66-3',
        'numero_ncm' => '2903.13.00',
        'numero_nota_fiscal' => 'NF-44556',
        'quantidade' => 8,
        'controlado' => 1
    ],
    [
        'nome' => 'Permanganato de Potássio',
        'formula_quimica' => 'KMnO4',
        'massa_molar' => '158.03',
        'concentracao' => '99%',
        'densidade' => '2.70',
        'validade' => date('Y-m-d', strtotime('+3 years')),
        'fabricante' => 'Dinâmica',
        'numero_cas' => '7722-64-7',
        'numero_ncm' => '2841.61.00',
        'numero_nota_fiscal' => 'NF-99887',
        'quantidade' => 2,
        'controlado' => 1
    ]
];

echo "Inserindo produtos controlados...\n";

foreach ($controlledProducts as $prod) {
    if ($controller->criar($prod)) {
        echo "Sucesso: " . $prod['nome'] . "\n";
    } else {
        echo "Erro ao inserir: " . $prod['nome'] . "\n";
    }
}

echo "Concluído.\n";
?>
