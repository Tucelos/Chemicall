# 🛡️ Política de Segurança - Chemicall

Este documento define a política de segurança do projeto **Chemicall**, orienta sobre como relatar vulnerabilidades e descreve as práticas de segurança adotadas e recomendadas para o sistema.

---

## 📞 Relatando uma Vulnerabilidade

Se você descobrir uma vulnerabilidade de segurança neste projeto, por favor **não abra uma issue pública**. Em vez disso, siga as instruções abaixo para relatar o problema de forma responsável:

1. Envie um e-mail para o mantenedor do projeto em: **tuiggbarcelos.aluno@unipampa.edu.br**.
2. No e-mail, inclua uma descrição detalhada do problema, incluindo:
   - Passos para reproduzir a vulnerabilidade (Proof of Concept).
   - O impacto potencial da falha.
   - Qualquer sugestão de correção.
3. Faremos o possível para responder e avaliar o relatório o mais rápido possível e definir um plano de mitigação.

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
