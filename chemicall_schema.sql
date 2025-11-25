DROP DATABASE IF EXISTS chemicall;
CREATE DATABASE chemicall;
USE chemicall;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'docente') NOT NULL DEFAULT 'docente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reagentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    formula_quimica VARCHAR(100),
    massa_molar DECIMAL(10, 2),
    concentracao VARCHAR(50),
    densidade DECIMAL(10, 3),
    validade DATE,
    fabricante VARCHAR(100),
    condicao ENUM('aberto', 'fechado') DEFAULT 'fechado',
    numero_cas VARCHAR(50),
    numero_ncm VARCHAR(50),
    numero_nota_fiscal VARCHAR(100),
    quantidade INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@chemicall.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Professor Teste', 'prof@chemicall.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'docente');
