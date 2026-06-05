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

1. **Pré-requisitos**: Tenha o [XAMPP](https://www.apachefriends.org/) instalado.
2. **Mover o Projeto**:
   - Mova a pasta `Chemicall` para dentro do diretório de servidores web do XAMPP: `C:\xampp\htdocs\Chemicall`.
3. **Configurar o Ambiente (.env)**:
   - Verifique ou crie o arquivo `.env` na raiz do projeto com os dados do seu MySQL.
   - *Nota: Caso seu root do XAMPP não possua senha, configure `DB_PASS=` (em branco).*
4. **Configuração do Banco de Dados**:
   - Inicie o **Apache** e **MySQL** no Painel do XAMPP.
   - Acesse `http://localhost/phpmyadmin` no navegador.
   - Crie um banco de dados chamado `chemicall`.
   - Selecione o banco `chemicall` e importe o arquivo [chemicall_schema.sql](file:///C:/xampp/htdocs/Chemicall/chemicall_schema.sql) da raiz do projeto. Isso criará todas as tabelas (funcionarios, reagentes, movimentacoes, etc.) e inserirá dados fictícios de teste.
5. **Execução**:
   - Acesse `http://localhost/Chemicall/src/telas/login/index.php` no seu navegador.
   - Faça login com as credenciais padrão de teste:
     * **Administrador**: `admin@chemicall.com` / Senha: `admin123`
     * **Professor**: `prof@chemicall.com` / Senha: `admin123`

---


