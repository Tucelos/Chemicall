<?php
/**
 * Ponto de entrada do projeto: encaminha a raiz para a tela de login.
 *
 * É um redirecionamento externo (302), e não um include, porque as telas usam
 * caminhos relativos — servir o login sob a raiz faria links como
 * "solicitar_cadastro.php" e o "Location: ../dashboard/index.php" após o login
 * apontarem para fora da pasta correta.
 *
 * A base é calculada a partir da requisição, então funciona igualmente em
 * http://host/Chemicall/ e em um virtual host apontado direto para o projeto.
 */
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

header('Location: ' . $base . '/src/telas/login/index.php', true, 302);
exit;
