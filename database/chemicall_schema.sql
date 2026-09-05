-- ==========================================================
-- Chemicall - Sistema de Gerenciamento de Reagentes Químicos
-- Banco de Dados Completo com Tabelas, Usuários e Sementes
-- ==========================================================

DROP DATABASE IF EXISTS chemicall;
CREATE DATABASE chemicall CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE chemicall;

-- ----------------------------------------------------------
-- 1. Tabela: funcionario (Usuários do sistema)
-- ----------------------------------------------------------
CREATE TABLE funcionario (
    cod_funcionario INT AUTO_INCREMENT PRIMARY KEY,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    matricula VARCHAR(50) NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_secundario VARCHAR(255) NULL,
    tipo VARCHAR(20) DEFAULT 'user',
    status VARCHAR(20) NOT NULL DEFAULT 'ativo',
    cargo VARCHAR(100) NULL,
    acesso_controlados TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 2. Tabela: esqueceu_senha (Tokens de recuperação)
-- ----------------------------------------------------------
-- O token é gravado como hash SHA-256; `expira_em` define a validade e
-- `usado_em` garante que cada link só possa ser consumido uma vez.
CREATE TABLE esqueceu_senha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expira_em DATETIME NULL,
    usado_em DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 2.1 Tabela: tentativas_login (proteção contra força bruta)
-- ----------------------------------------------------------
CREATE TABLE tentativas_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    tentado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_data (email, tentado_em),
    INDEX idx_ip_data (ip, tentado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 3. Tabela: reagentes (Inventário de reagentes e insumos)
-- ----------------------------------------------------------
CREATE TABLE reagentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    formula_quimica VARCHAR(100) DEFAULT NULL,
    massa_molar DECIMAL(10, 2) DEFAULT NULL,
    concentracao VARCHAR(50) DEFAULT NULL,
    densidade DECIMAL(10, 3) DEFAULT NULL,
    validade DATE DEFAULT NULL,
    fabricante VARCHAR(100) DEFAULT NULL,
    condicao ENUM('aberto', 'fechado') DEFAULT 'fechado',
    numero_cas VARCHAR(50) DEFAULT NULL,
    numero_ncm VARCHAR(50) DEFAULT NULL,
    numero_nota_fiscal VARCHAR(100) DEFAULT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    quantidade_original INT NOT NULL DEFAULT 0,
    unidade_medida VARCHAR(20) NOT NULL DEFAULT 'frasco',
    capacidade_medida DECIMAL(10, 2) DEFAULT NULL,
    unidade_capacidade VARCHAR(10) DEFAULT 'ml',
    controlado TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 3.1 Índices de apoio às consultas de estoque e relatórios
-- ----------------------------------------------------------
ALTER TABLE reagentes ADD INDEX idx_ativo_qtd (ativo, quantidade);

-- ----------------------------------------------------------
-- 4. Tabela: movimentacoes (Histórico de logs do estoque)
-- ----------------------------------------------------------
CREATE TABLE movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reagente_id INT NOT NULL,
    funcionario_id INT NOT NULL,
    tipo_movimentacao VARCHAR(20) NOT NULL,
    quantidade INT NOT NULL,
    motivo_retirada VARCHAR(255) DEFAULT NULL,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reagente_id) REFERENCES reagentes(id) ON DELETE CASCADE,
    FOREIGN KEY (funcionario_id) REFERENCES funcionario(cod_funcionario) ON DELETE CASCADE,
    -- Apoia as consultas de consumo em estatisticas.php e no relatório PDF.
    INDEX idx_tipo_data (tipo_movimentacao, data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- 5. Inserções: Funcionários Iniciais
--
-- ATENÇÃO: a senha destas contas é `admin123`, pública neste arquivo e
-- destinada apenas a demonstração. Antes de qualquer uso real, troque a senha
-- do administrador e remova a conta de teste.
--
-- Perfis reconhecidos pelo sistema: 'admin', 'gestor' e 'user'.
-- ----------------------------------------------------------
INSERT INTO funcionario (cod_funcionario, senha, nome, email, tipo, acesso_controlados) VALUES
(1, '$2y$10$1X5w5id2V0EjyF8VA/AhwuUbstosnL05OfZVhY07iGfRlWxsxlpye', 'Administrador', 'admin@chemicall.com', 'admin', 1),
(2, '$2y$10$1X5w5id2V0EjyF8VA/AhwuUbstosnL05OfZVhY07iGfRlWxsxlpye', 'Professor Teste', 'prof@chemicall.com', 'user', 0);

-- ----------------------------------------------------------
-- 6. Inserções: Reagentes de Teste
-- ----------------------------------------------------------
INSERT INTO reagentes (id, nome, formula_quimica, massa_molar, concentracao, densidade, validade, fabricante, condicao, numero_cas, numero_ncm, numero_nota_fiscal, quantidade, quantidade_original, unidade_medida, capacidade_medida, unidade_capacidade, controlado) VALUES
-- Reagentes Comuns
(1, 'Ácido Sulfúrico', 'H2SO4', 98.08, '98%', 1.840, '2026-12-31', 'Sigma-Aldrich', 'fechado', '7664-93-9', '28070010', 'NF-1001', 5, 5, 'frasco', 1000.00, 'ml', 0),
(2, 'Ácido Clorídrico', 'HCl', 36.46, '37%', 1.180, '2025-06-30', 'Merck', 'aberto', '7647-01-0', '28061020', 'NF-1002', 3, 3, 'frasco', 1000.00, 'ml', 0),
(3, 'Hidróxido de Sódio', 'NaOH', 40.00, '99%', 2.130, '2027-01-15', 'Neon', 'fechado', '1310-73-2', '28151100', 'NF-1003', 10, 10, 'frasco', 500.00, 'g', 0),
(4, 'Etanol', 'C2H6O', 46.07, '99.5%', 0.789, '2025-11-20', 'Synth', 'fechado', '64-17-5', '22071010', 'NF-1004', 20, 20, 'frasco', 1000.00, 'ml', 0),
(5, 'Acetona', 'C3H6O', 58.08, '99.5%', 0.784, '2026-03-10', 'Dinâmica', 'aberto', '67-64-1', '29141100', 'NF-1005', 8, 8, 'galão', 5000.00, 'ml', 0),
(6, 'Sulfato de Cobre', 'CuSO4', 159.61, '98%', 3.600, '2028-05-22', 'Vetec', 'fechado', '7758-98-7', '28332510', 'NF-1006', 2, 2, 'frasco', 250.00, 'g', 0),
(7, 'Cloreto de Sódio', 'NaCl', 58.44, '99%', 2.160, '2030-12-31', 'Cinthia', 'aberto', '7647-14-5', '25010020', 'NF-1007', 15, 15, 'frasco', 1000.00, 'g', 0),
(8, 'Permanganato de Potássio', 'KMnO4', 158.03, '99%', 2.700, '2025-08-15', 'Labsynth', 'fechado', '7722-64-7', '28416100', 'NF-1008', 4, 4, 'frasco', 100.00, 'g', 0),
(9, 'Ácido Acético', 'CH3COOH', 60.05, '99.7%', 1.050, '2026-09-01', 'Anidrol', 'aberto', '64-19-7', '29152100', 'NF-1009', 6, 6, 'frasco', 1000.00, 'ml', 0),
(10, 'Nitrato de Prata', 'AgNO3', 169.87, '99.8%', 4.350, '2025-12-12', 'Sigma', 'fechado', '7761-88-8', '28432100', 'NF-1010', 1, 1, 'frasco', 100.00, 'g', 0),
-- Reagentes Controlados (Polícia Federal / Exército)
(11, 'Ácido Sulfúrico P.A.', 'H2SO4', 98.08, '98%', 1.840, '2027-06-05', 'Merck', 'fechado', '7664-93-9', '2807.00.10', 'NF-12345', 10, 10, 'frasco', 1000.00, 'ml', 1),
(12, 'Acetona P.A.', 'C3H6O', 58.08, '99.5%', 0.790, '2028-06-05', 'Sigma-Aldrich', 'fechado', '67-64-1', '2914.11.00', 'NF-67890', 25, 25, 'galão', 5000.00, 'ml', 1),
(13, 'Tolueno', 'C7H8', 92.14, '99%', 0.870, '2027-12-05', 'Synth', 'fechado', '108-88-3', '2902.30.00', 'NF-11223', 5, 5, 'frasco', 1000.00, 'ml', 1),
(14, 'Clorofórmio', 'CHCl3', 119.38, '99.8%', 1.490, '2027-06-05', 'Neon', 'fechado', '67-66-3', '2903.13.00', 'NF-44556', 8, 8, 'frasco', 1000.00, 'ml', 1),
(15, 'Permanganato de Potássio (Controlado)', 'KMnO4', 158.03, '99%', 2.700, '2029-06-05', 'Dinâmica', 'fechado', '7722-64-7', '2841.61.00', 'NF-99887', 2, 2, 'frasco', 500.00, 'g', 1);

-- ----------------------------------------------------------
-- 7. Inserções: Histórico de Movimentações (Criação inicial)
-- ----------------------------------------------------------
INSERT INTO movimentacoes (reagente_id, funcionario_id, tipo_movimentacao, quantidade, data_hora) VALUES
(1, 1, 'criacao', 5, NOW()),
(2, 1, 'criacao', 3, NOW()),
(3, 1, 'criacao', 10, NOW()),
(4, 1, 'criacao', 20, NOW()),
(5, 1, 'criacao', 8, NOW()),
(6, 1, 'criacao', 2, NOW()),
(7, 1, 'criacao', 15, NOW()),
(8, 1, 'criacao', 4, NOW()),
(9, 1, 'criacao', 6, NOW()),
(10, 1, 'criacao', 1, NOW()),
(11, 1, 'criacao', 10, NOW()),
(12, 1, 'criacao', 25, NOW()),
(13, 1, 'criacao', 5, NOW()),
(14, 1, 'criacao', 8, NOW()),
(15, 1, 'criacao', 2, NOW());
