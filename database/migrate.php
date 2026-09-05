<?php
/**
 * Migração idempotente do banco do Chemicall.
 *
 * Aplica as alterações de esquema necessárias para as correções de segurança:
 *   - tabela `tentativas_login` (proteção contra força bruta);
 *   - colunas `expira_em` e `usado_em` em `esqueceu_senha` (token de uso único
 *     com validade);
 *   - índices de apoio nas consultas de relatório.
 *
 * Uso: php database/migrate.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.');
}

require_once __DIR__ . '/../src/db/db_connection.php';

/** Executa uma instrução e informa o resultado. */
function passo(PDO $conn, string $descricao, string $sql): void
{
    try {
        $conn->exec($sql);
        echo "  [ok]     {$descricao}\n";
    } catch (PDOException $e) {
        echo "  [ERRO]   {$descricao}: " . $e->getMessage() . "\n";
    }
}

/** Verifica se uma coluna já existe na tabela. */
function temColuna(PDO $conn, string $tabela, string $coluna): bool
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
    );
    $stmt->execute([':t' => $tabela, ':c' => $coluna]);
    return (int) $stmt->fetchColumn() > 0;
}

/** Verifica se um índice já existe na tabela. */
function temIndice(PDO $conn, string $tabela, string $indice): bool
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND INDEX_NAME = :i'
    );
    $stmt->execute([':t' => $tabela, ':i' => $indice]);
    return (int) $stmt->fetchColumn() > 0;
}

echo "Migrando banco '" . env('DB_NAME', 'chemicall') . "'...\n";

// 1. Controle de tentativas de login -----------------------------------------
passo($conn, "tabela 'tentativas_login'", "
    CREATE TABLE IF NOT EXISTS tentativas_login (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        tentado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_data (email, tentado_em),
        INDEX idx_ip_data (ip, tentado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// 2. Token de redefinição com validade e uso único ---------------------------
if (!temColuna($conn, 'esqueceu_senha', 'expira_em')) {
    passo($conn, "coluna 'esqueceu_senha.expira_em'",
        "ALTER TABLE esqueceu_senha ADD COLUMN expira_em DATETIME NULL AFTER token");
} else {
    echo "  [pulado] coluna 'esqueceu_senha.expira_em' já existe\n";
}

if (!temColuna($conn, 'esqueceu_senha', 'usado_em')) {
    passo($conn, "coluna 'esqueceu_senha.usado_em'",
        "ALTER TABLE esqueceu_senha ADD COLUMN usado_em DATETIME NULL AFTER expira_em");
} else {
    echo "  [pulado] coluna 'esqueceu_senha.usado_em' já existe\n";
}

if (!temIndice($conn, 'esqueceu_senha', 'idx_token')) {
    passo($conn, "índice 'esqueceu_senha.idx_token'",
        "ALTER TABLE esqueceu_senha ADD INDEX idx_token (token)");
} else {
    echo "  [pulado] índice 'esqueceu_senha.idx_token' já existe\n";
}

// Tokens antigos foram emitidos sem validade: invalida todos por segurança.
passo($conn, "invalidação dos tokens de redefinição legados",
    "UPDATE esqueceu_senha SET expira_em = NOW() WHERE expira_em IS NULL");

// 3. Índices de apoio aos relatórios -----------------------------------------
if (!temIndice($conn, 'movimentacoes', 'idx_tipo_data')) {
    passo($conn, "índice 'movimentacoes.idx_tipo_data'",
        "ALTER TABLE movimentacoes ADD INDEX idx_tipo_data (tipo_movimentacao, data_hora)");
} else {
    echo "  [pulado] índice 'movimentacoes.idx_tipo_data' já existe\n";
}

if (!temIndice($conn, 'reagentes', 'idx_ativo_qtd')) {
    passo($conn, "índice 'reagentes.idx_ativo_qtd'",
        "ALTER TABLE reagentes ADD INDEX idx_ativo_qtd (ativo, quantidade)");
} else {
    echo "  [pulado] índice 'reagentes.idx_ativo_qtd' já existe\n";
}

// 4. Normalização de perfis --------------------------------------------------
// O sistema reconhece apenas 'admin', 'gestor' e 'user'. Perfis legados como
// 'docente' caíam silenciosamente no perfil mais restrito.
$legados = $conn->query(
    "SELECT COUNT(*) FROM funcionario WHERE tipo NOT IN ('admin','gestor','user')"
)->fetchColumn();

if ((int) $legados > 0) {
    passo($conn, "normalização de {$legados} perfil(is) legado(s) para 'user'",
        "UPDATE funcionario SET tipo = 'user' WHERE tipo NOT IN ('admin','gestor','user')");
} else {
    echo "  [pulado] nenhum perfil legado a normalizar\n";
}

echo "Migração concluída.\n";
