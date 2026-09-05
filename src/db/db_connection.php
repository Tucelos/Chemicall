<?php
/**
 * Conexão com o banco de dados (PDO).
 *
 * O bootstrap é carregado aqui para garantir que sessão, tratamento de erros e
 * cabeçalhos de segurança estejam configurados mesmo em pontos de entrada que
 * incluam apenas este arquivo.
 */
require_once __DIR__ . '/../config/bootstrap.php';

$servername = env('DB_HOST', 'localhost');
$username   = env('DB_USER', 'root');
$password   = env('DB_PASS', '');
$dbname     = env('DB_NAME', 'chemicall');

try {
    $conn = new PDO(
        "mysql:host={$servername};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Prepared statements reais no servidor, e não emulados pelo driver.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // A mensagem original contém host, usuário e nome do banco: só vai para o log.
    chemicall_fail('Falha ao conectar no banco de dados: ' . $e->getMessage(), 503);
}
