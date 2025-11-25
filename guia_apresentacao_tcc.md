# Guia de Apresentação do Sistema Chemicall - TCC

Este documento serve como um roteiro de estudo para a apresentação do seu Trabalho de Conclusão de Curso. Ele detalha o funcionamento, a estrutura e as tecnologias do sistema **Chemicall**.

---

## 1. Visão Geral do Sistema

**O que é o Chemicall?**
É um sistema web para gerenciamento de reagentes químicos, focado em controlar o estoque, validade e movimentação de produtos em laboratórios.

**Objetivo Principal:**
Substituir controles manuais (papel/planilhas) por um sistema digital seguro, garantindo rastreabilidade e evitando desperdícios ou uso de produtos vencidos.

---

## 2. Tecnologias e Ferramentas

Você deve saber explicar por que escolheu cada uma:

*   **Linguagem de Back-end: PHP (Vanilla/Puro)**
    *   *Por que?* Amplamente suportado, fácil de hospedar, roda nativamente no XAMPP, ideal para desenvolvimento web rápido.
*   **Banco de Dados: MySQL**
    *   *Por que?* Banco relacional robusto, gratuito e padrão de mercado para aplicações web PHP.
*   **Front-end: HTML5, CSS3, JavaScript**
    *   *HTML5:* Estrutura semântica das páginas.
    *   *CSS3:* Estilização e layout (responsividade).
    *   *JavaScript:* Interatividade no lado do cliente (validações, alertas, confirmações).
*   **Servidor Web: Apache (via XAMPP)**
    *   *Por que?* Servidor web mais utilizado do mundo, fácil configuração local.
*   **Bibliotecas Externas:**
    *   **PHPMailer:** Para envio de e-mails (recuperação de senha).
    *   **Dompdf:** Para gerar relatórios em PDF.

---

## 3. Estrutura de Arquivos e Pastas

Explique como o projeto está organizado. Isso mostra organização e boas práticas.

### Raiz do Projeto (`Chemicall_5/`)
*   `index.php`: Ponto de entrada (geralmente redireciona para login).
*   `chemicall_schema.sql`: Script SQL para criar o banco de dados e tabelas.
*   `diagrama_er.md`: Documentação do banco de dados.

### Código Fonte (`src/`)
A pasta `src` contém todo o código lógico do sistema.

*   **`db/`**: Conexão com o banco de dados.
    *   `db_connection.php`: Arquivo crucial. Cria a conexão PDO com o MySQL. É incluído em quase todas as páginas que precisam de dados.
*   **`componentes/`**: Partes reutilizáveis da interface.
    *   `header.php`: O cabeçalho (menu de navegação). Feito em um arquivo só para facilitar manutenção. Se mudar o menu aqui, muda em todas as páginas.
    *   `logout.php`: Script que destrói a sessão do usuário e redireciona para o login.
*   **`telas/`**: As páginas visíveis do sistema, organizadas por funcionalidade.
    *   `login/`:
        *   `index.php`: Tela de login.
        *   `valida_login.php`: Recebe os dados do form, verifica no banco e cria a sessão `$_SESSION`.
        *   `esqueceu_senha.php` & `reset.php`: Fluxo de recuperação de senha via e-mail.
    *   `inicio/`:
        *   `index.php`: A "Home" ou Dashboard do sistema após logar.
    *   `reagentes/`:
        *   `index.php`: Listagem de reagentes.
        *   `form.php`: Formulário para cadastrar ou editar reagentes.
    *   `relatorio/`:
        *   `relatorios.php`: Tela de filtros para gerar relatórios.
        *   `pdf/inventario.php`: Gera o PDF usando a biblioteca Dompdf.

---

## 4. Fluxos Principais (Como Funciona)

A banca pode pedir para você "seguir o caminho do dado".

### A. Login e Autenticação
1.  Usuário digita e-mail e senha em `telas/login/index.php`.
2.  O formulário envia POST para `valida_login.php`.
3.  O PHP verifica se o e-mail existe e se a senha bate (usando `password_verify` se estiver criptografada).
4.  Se OK: Cria variáveis de sessão (`$_SESSION['usuario_id']`, etc.) e redireciona para `telas/inicio/index.php`.
5.  Se Erro: Redireciona de volta com mensagem de erro.
6.  **Segurança**: Todas as páginas internas verificam se `$_SESSION` existe no topo. Se não, chuta o usuário para o login.

### B. Cadastro de Reagente (CRUD)
1.  **Create (Criar)**: Usuário preenche `telas/reagentes/form.php`. O PHP recebe, valida e faz um `INSERT INTO reagentes ...`.
2.  **Read (Ler)**: A página `telas/reagentes/index.php` faz um `SELECT * FROM reagentes` e exibe uma tabela HTML com um loop `foreach`.
3.  **Update (Atualizar)**: Ao clicar em editar, o ID vai pela URL (`?id=1`). O formulário carrega os dados desse ID (`SELECT ... WHERE id=1`). Ao salvar, faz um `UPDATE reagentes SET ...`.
4.  **Delete (Deletar)**: Botão de excluir envia o ID. O sistema pede confirmação (JS) e o PHP executa `DELETE FROM reagentes WHERE id=...`.

### C. Relatórios
1.  Usuário seleciona filtros (ex: data, tipo) em `telas/relatorio/relatorios.php`.
2.  Ao clicar em "Gerar PDF", os dados vão para `telas/relatorio/pdf/inventario.php`.
3.  Este arquivo faz a consulta no banco (`SELECT`) com os filtros.
4.  Monta um HTML com os resultados.
5.  A biblioteca **Dompdf** pega esse HTML e converte para um arquivo `.pdf` para download.

---

## 5. Banco de Dados

Se perguntarem sobre o banco:
*   **Tabelas Principais**:
    *   `reagentes`: Guarda os produtos.
    *   `funcionario`: Guarda os usuários do sistema.
    *   `movimentacoes`: Tabela de log/histórico. Relaciona `reagente_id` e `funcionario_id` para saber quem mexeu no que e quando.
*   **Relacionamentos**:
    *   Um reagente pode ter várias movimentações (1:N).
    *   Um funcionário pode fazer várias movimentações (1:N).

---

## Dicas para a Apresentação

*   **Não leia código linha por linha.** Explique a *lógica*. Ex: "Aqui nós verificamos se o usuário está logado" em vez de ler `if (!isset($_SESSION...))`.
*   **Destaque a segurança.** Mencione que senhas são (ou deveriam ser) hash, que existe controle de sessão e proteção contra SQL Injection (uso de Prepared Statements no PDO).
*   **Valorize o problema resolvido.** O sistema evita que reagentes vençam na prateleira (prejuízo/perigo) e organiza o laboratório.

Boa sorte, Tuigg! Você construiu um sistema completo. 🚀
