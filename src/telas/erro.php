<?php
/**
 * Página de erro genérica do Chemicall.
 *
 * Substitui as páginas padrão do Apache, que exibem a versão do servidor
 * ("Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12") e não carregam os
 * cabeçalhos de segurança da aplicação.
 */
require_once __DIR__ . '/../config/bootstrap.php';

// REDIRECT_STATUS é definido pelo Apache ao acionar um ErrorDocument.
$status = (int) ($_SERVER['REDIRECT_STATUS'] ?? 404);

$mensagens = [
    400 => ['Requisição inválida', 'O servidor não conseguiu interpretar o pedido.'],
    401 => ['Acesso não autorizado', 'É necessário entrar no sistema para acessar esta página.'],
    403 => ['Acesso negado', 'Você não tem permissão para acessar este recurso.'],
    404 => ['Página não encontrada', 'O endereço acessado não existe neste sistema.'],
    500 => ['Erro interno', 'Ocorreu um erro inesperado. Se o problema persistir, contate o administrador.'],
];

if (!isset($mensagens[$status])) {
    $status = 404;
}
[$titulo, $descricao] = $mensagens[$status];

http_response_code($status);

// O caminho de volta muda conforme o usuário esteja ou não autenticado.
$destino = !empty($_SESSION['user_id'])
    ? '/Chemicall/src/telas/dashboard/index.php'
    : '/Chemicall/src/telas/login/index.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $status; ?> — Chemicall</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        .caixa {
            background: #fff;
            padding: 48px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 460px;
            text-align: center;
        }
        .marca { color: #006233; font-size: 1.8rem; font-weight: bold; margin-bottom: 24px; }
        .codigo { font-size: 3.4rem; font-weight: bold; color: #006233; line-height: 1; margin-bottom: 8px; }
        h1 { font-size: 1.3rem; margin: 0 0 12px; }
        p { color: #666; line-height: 1.6; margin: 0 0 28px; }
        a.voltar {
            display: inline-block;
            background-color: #006233;
            color: #fff;
            text-decoration: none;
            padding: 11px 26px;
            border-radius: 6px;
            font-size: 1rem;
        }
        a.voltar:hover { background-color: #004d28; }
    </style>
</head>
<body>
    <div class="caixa">
        <div class="marca">Chemicall</div>
        <div class="codigo"><?php echo $status; ?></div>
        <h1><?php echo e($titulo); ?></h1>
        <p><?php echo e($descricao); ?></p>
        <a class="voltar" href="<?php echo e($destino); ?>">Voltar ao sistema</a>
    </div>
</body>
</html>
