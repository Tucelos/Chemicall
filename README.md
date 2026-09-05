# 🧪 Chemicall

> **Sistema de Gerenciamento de Reagentes Químicos**

![Status do Projeto](https://img.shields.io/badge/Status-Em_Desenvolvimento-yellow)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📖 Sobre o Projeto

**Chemicall** é um sistema web desenvolvido para otimizar e controlar o gerenciamento de reagentes químicos em laboratórios. O sistema permite o rastreamento detalhado de estoque, controle de validade, registro de movimentações (entradas e saídas) e geração de relatórios gerenciais.

Este projeto foi desenvolvido com foco na segurança, eficiência e facilidade de uso para técnicos e gestores de laboratório.

---

## 🚀 Funcionalidades Principais

- 📦 **Controle de Estoque**: Cadastro completo de reagentes com detalhes como fórmula, fabricante, validade e localização.
- 📊 **Relatórios e Estatísticas**: Geração de relatórios em PDF e visualização de estatísticas de consumo.
- 🔔 **Alertas de Validade**: Notificações visuais para reagentes próximos do vencimento.
- 🔐 **Controle de Acesso**: Sistema de login seguro com diferentes níveis de permissão (Administrador/Usuário).
- 📝 **Histórico de Movimentações**: Log detalhado de quem retirou ou adicionou itens ao estoque.

---

## 🛠️ Tecnologias Utilizadas

O projeto foi construído utilizando as seguintes tecnologias:

- **Back-end**: PHP (Vanilla)
- **Banco de Dados**: MySQL
- **Front-end**: HTML5, CSS3, JavaScript
- **Servidor Local**: XAMPP (Apache)
- **Geração de PDF**: Dompdf

---

## 👨‍💻 Autor

<div align="center">

### Tuigg Barcelos

**Trabalho de Conclusão de Curso (TCC)**  
*Análise e Desenvolvimento de Sistemas*

</div>

---

## ⚙️ Instalação e Configuração

1. **Pré-requisitos**: [XAMPP](https://www.apachefriends.org/) com PHP 7.4 ou superior
   (testado em 8.2), MySQL/MariaDB com tabelas InnoDB e Apache com `mod_rewrite`
   e `mod_headers`.
2. **Mover o Projeto**:
   - Mova a pasta `Chemicall` para o diretório de servidores web do XAMPP: `C:\xampp\htdocs\Chemicall`.
3. **Configurar o Ambiente (`.env`)**:
   - Copie [.env.example](.env.example) para `.env` e preencha os valores.
   - Em desenvolvimento local use `APP_ENV=development` (mostra erros na tela).
     Em produção mantenha `APP_ENV=production` — os erros passam a ir só para
     `storage/logs/app.log`.
   - Defina `APP_URL` com a URL pública do sistema: é a partir dela que o link
     de redefinição de senha é montado.
   - *Se o root do MySQL não tiver senha, use `DB_PASS=` (em branco).*
4. **Banco de Dados**:
   - Inicie o **Apache** e o **MySQL** no Painel do XAMPP.
   - Em `http://localhost/phpmyadmin`, importe [database/chemicall_schema.sql](database/chemicall_schema.sql).
     O script cria o banco `chemicall`, todas as tabelas e dados de exemplo.
   - Se você estiver **atualizando** uma instalação existente, rode a migração:

     ```bash
     php database/migrate.php
     ```

5. **Execução**:
   - Acesse `http://localhost/Chemicall/src/telas/login/index.php`.
   - Credencial de demonstração: `admin@chemicall.com` / `admin123`.

> ⚠️ **Antes de colocar em produção**, leia [docs/PLANO_DE_MELHORIA.md](docs/PLANO_DE_MELHORIA.md).
> A senha `admin123` é pública neste repositório e precisa ser trocada, entre
> outros itens de preparação listados no documento.

---


