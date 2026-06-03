# 🛡️ Política de Segurança (SECURITY.md) - Chemicall

Este documento define a política de segurança do projeto **Chemicall**, orienta sobre como relatar vulnerabilidades e descreve as práticas de segurança adotadas e recomendadas para o sistema.

---

## 📞 Relatando uma Vulnerabilidade

Se você descobrir uma vulnerabilidade de segurança neste projeto, por favor **não abra uma issue pública**. Em vez disso, siga as instruções abaixo para relatar o problema de forma responsável:

1. Envie um e-mail para o mantenedor do projeto em: **[Inserir E-mail de Contato, ex: tuigg.barcelos@exemplo.com]**.
2. No e-mail, inclua uma descrição detalhada do problema, incluindo:
   - Passos para reproduzir a vulnerabilidade (Proof of Concept).
   - O impacto potencial da falha.
   - Qualquer sugestão de correção.
3. Faremos o possível para responder e avaliar o relatório o mais rápido possível e definir um plano de mitigação.

---

## 🚀 Versões Suportadas

Atualmente, o suporte a correções de segurança é aplicado da seguinte forma:

| Versão | Suportada | Notas |
| :--- | :---: | :--- |
| **1.0.x (Atual)** | 🟢 Sim | Versão ativa de desenvolvimento e ajustes. |
| **< 1.0.0** | 🔴 Não | Versões anteriores ou betas não recebem atualizações. |

---

## 🔒 Práticas de Segurança Implementadas

O projeto Chemicall foi desenvolvido com foco em boas práticas de segurança recomendadas pela OWASP para aplicações PHP clássicas:

### 1. Prevenção de SQL Injection (SQLi)
*   **Adoção do PDO (PHP Data Objects):** Todas as interações com o banco de dados utilizam a extensão PDO com Prepared Statements (`$conn->prepare()`).
*   **Parâmetros Vinculados:** Valores dinâmicos são associados usando marcadores nomeados (ex: `:email`), garantindo que o banco de dados trate as entradas estritamente como dados e não como código executável.

### 2. Armazenamento Seguro de Credenciais
*   **Criptografia de Senhas:** As senhas dos usuários nunca são armazenadas em texto limpo. O sistema utiliza a função `password_hash($senha, PASSWORD_DEFAULT)`, que atualmente utiliza o algoritmo robusto **Bcrypt**.
*   **Verificação Segura:** A validação do login utiliza `password_verify()`, que é resistente a ataques de temporização (timing attacks).

### 3. Prevenção de Cross-Site Scripting (XSS)
*   **Sanitização na Saída:** Dados vindos do banco de dados ou parâmetros de URL (`GET`/`POST`) que são exibidos no HTML passam pela função `htmlspecialchars()`, convertendo caracteres especiais em entidades HTML seguras e impedindo a execução de scripts maliciosos injetados por usuários.

### 4. Controle de Acesso e Autenticação
*   **Sessões do PHP:** Validação de login baseada em variáveis de sessão (`$_SESSION['user_id']`).
*   **Verificação de Níveis:** Telas administrativas e gerenciais validam explicitamente se o usuário está autenticado e possui privilégios de administrador (`$auth->isAdmin()`), realizando o redirecionamento imediato caso contrário.

---

## ⚠️ Recomendações e Melhorias para Produção

Como este projeto é voltado para fins acadêmicos e desenvolvido em ambiente local (XAMPP), é altamente recomendado aplicar as seguintes melhorias antes de implantar o Chemicall em um servidor de produção:

### 1. Ocultar Mensagens de Erro (`display_errors`)
*   **Situação Atual:** No arquivo [db_connection.php](file:///c:/Users/tuigg/Desktop/Projetos/Chemicall/Chemicall/src/db/db_connection.php), as diretivas `display_errors` e `error_reporting` estão ativas para facilitar a depuração.
*   **Recomendação:** Em produção, defina `ini_set('display_errors', 0)` no arquivo de configuração do PHP (`php.ini`) ou diretamente no código para evitar o vazamento de caminhos internos do servidor e detalhes do banco de dados caso ocorra uma falha. Os erros devem ser direcionados apenas para arquivos de log ocultos.

### 2. Externalizar Credenciais do Banco de Dados
*   **Situação Atual:** As credenciais de acesso ao MySQL (`root`, senha e host) estão fixadas no código em `db_connection.php`.
*   **Recomendação:** Utilize variáveis de ambiente (por exemplo, através de uma biblioteca como `vlucas/phpdotenv` ou variáveis configuradas no próprio servidor Apache/Nginx) para evitar expor senhas no repositório Git.

### 3. Implementação de Proteção contra CSRF (Cross-Site Request Forgery)
*   **Recomendação:** Formulários que realizam ações sensíveis (como exclusão de usuários ou alteração de estoque) devem incluir um **token CSRF** gerado na sessão e validado no servidor no momento do envio do `POST`. Isso impede que requisições maliciosas de outros sites executem ações em nome de um usuário autenticado.

### 4. Configurações Seguras de Cookies de Sessão
*   **Recomendação:** Configure as diretivas de sessão para maior proteção contra roubo de sessão (Session Hijacking):
    ```php
    session_start([
        'cookie_lifetime' => 0,
        'cookie_secure' => true,      // Apenas via HTTPS
        'cookie_httponly' => true,    // Impede acesso via JavaScript (XSS)
        'cookie_samesite' => 'Strict' // Proteção adicional contra CSRF
    ]);
    ```

### 5. Uso Obrigatório de HTTPS
*   **Recomendação:** Em ambiente de produção, todo o tráfego deve ser forçado através de HTTPS para proteger credenciais e dados em trânsito contra ataques do tipo Man-in-the-Middle (MitM).
