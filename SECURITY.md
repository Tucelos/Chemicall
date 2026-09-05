# 🛡️ Política de Segurança (SECURITY.md) - Chemicall

Este documento define a política de segurança do projeto **Chemicall**, orienta sobre como relatar vulnerabilidades e descreve as práticas de segurança adotadas e recomendadas para o sistema.

---

## 📞 Relatando uma Vulnerabilidade

Se você descobrir uma vulnerabilidade de segurança neste projeto, por favor **não abra uma issue pública**. Em vez disso, siga as instruções abaixo para relatar o problema de forma responsável:

1. Envie um e-mail para o mantenedor do projeto em: **[Inserir E-mail de Contato]**.
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

As proteções abaixo estão **implementadas e verificadas** no código atual.

### 1. Prevenção de SQL Injection (SQLi)
* Todo acesso ao banco usa **PDO com prepared statements** e parâmetros vinculados (`:email`, `:id`), nunca concatenação de entrada do usuário.
* `PDO::ATTR_EMULATE_PREPARES` está desativado, de modo que os statements são preparados pelo próprio MySQL.
* A conexão legada via MySQLi, usada por telas antigas que concatenavam `$_GET` diretamente no SQL, foi removida junto com essas telas.

### 2. Armazenamento Seguro de Credenciais
* Senhas armazenadas com `password_hash($senha, PASSWORD_DEFAULT)` (Bcrypt) e validadas com `password_verify()`.
* Política mínima: 8 caracteres, com ao menos uma letra e um número (`FuncionarioController::validarSenha()`), aplicada no cadastro, na edição, no perfil e na redefinição por e-mail.
* Credenciais de banco e SMTP ficam em `.env`, fora do controle de versão e bloqueado pelo `.htaccess`.

### 3. Prevenção de Cross-Site Scripting (XSS)
* O helper `e()` (`src/config/bootstrap.php`) aplica `htmlspecialchars(..., ENT_QUOTES)` a toda saída dinâmica.
* Valores inseridos em contexto JavaScript usam `json_encode()` com as flags `JSON_HEX_*`.
* Uma política de `Content-Security-Policy` restringe as origens de scripts.

### 4. Proteção contra CSRF
* Token de 32 bytes por sessão, comparado com `hash_equals()`.
* `csrf_exigir()` protege **todo** endpoint que altera estado; requisições sem token válido recebem HTTP 403.
* Operações destrutivas exigem POST (`exigir_post()`): a exclusão de reagentes, que antes acontecia por um simples GET, não é mais acionável por um link ou imagem em outro site.
* Cookie de sessão com `SameSite=Lax` como camada adicional.

### 5. Controle de Acesso e Autenticação
* Sessão com `HttpOnly`, `SameSite=Lax`, `use_strict_mode` e `Secure` quando em produção/HTTPS. O identificador é regenerado no login e na troca de senha.
* Expiração automática por inatividade (`SESSION_IDLE_TIMEOUT`, padrão 30 minutos).
* Guardas centralizadas em `AuthController`: `exigirLogin()`, `exigirGestao()` e `exigirAdmin()`.
* O usuário só edita e-mail secundário e senha no próprio perfil; `tipo`, `status` e `acesso_controlados` não são alteráveis por ele.
* Produtos controlados dependem de permissão explícita, verificada tanto na listagem quanto em cada movimentação.
* O sistema protege a última conta de administrador ativa contra exclusão e rebaixamento, e impede a autoexclusão.

### 6. Proteção contra Força Bruta e Enumeração
* Cinco tentativas malsucedidas por e-mail/IP bloqueiam novos logins por 15 minutos (tabela `tentativas_login`).
* O login compara a senha contra um hash descartável quando o usuário não existe, para não vazar a informação pelo tempo de resposta.
* A recuperação de senha e a solicitação de cadastro devolvem sempre a mesma mensagem, existindo ou não o e-mail informado.

### 7. Recuperação de Senha
* Token aleatório de 32 bytes, armazenado apenas como hash SHA-256.
* Validade de 30 minutos, uso único (`usado_em`) e invalidação dos tokens anteriores a cada nova solicitação.
* O link é montado a partir de `APP_URL`, e não do cabeçalho `Host`, que pode ser forjado por um atacante.

### 8. Tratamento de Erros
* Com `APP_ENV=production`, nenhum detalhe técnico chega ao navegador: o usuário vê uma mensagem genérica e o erro real vai para `storage/logs/app.log`, diretório bloqueado pelo servidor web.

### 9. Cabeçalhos de Segurança
* `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy` e, em produção, `Strict-Transport-Security`.
* Páginas de erro próprias (`src/telas/erro.php`): as páginas padrão do Apache não passam pelo PHP, então não recebiam cabeçalho algum e ainda exibiam a versão do servidor. O endereço inexistente é desviado por reescrita com caminho relativo, e o `ServerSignature Off` do `.htaccess` remove a versão também dos demais status (403, 500).

### 9.1. Integridade de Recursos Externos (SRI)
* Bootstrap e Font Awesome, carregados de CDN, usam `integrity` com hash SHA-384, `crossorigin="anonymous"` e `referrerpolicy="no-referrer"`. Se o arquivo servido pelo CDN for alterado, o navegador o recusa em vez de executá-lo.
* O carregador do Google Charts (`gstatic.com/charts/loader.js`) é a exceção: por ser um *loader* dinâmico, seu conteúdo muda e o SRI o quebraria.

### 10. Superfície de Ataque
* O `.htaccess` bloqueia `.env`, `.sql`, `.md`, `.log`, a listagem de diretórios e o diretório `database/`.
* Scripts de manutenção (`migrate.php`, seeds) só executam via CLI.
* Ferramentas de depuração que expunham dados (`check_db.php`, que listava todos os usuários com seus hashes, e `create_tables.php`, que redefinia a senha do administrador) foram removidas do repositório.

---

## ⚠️ Pendências Conhecidas

Itens ainda não implementados, detalhados em [docs/PLANO_DE_MELHORIA.md](docs/PLANO_DE_MELHORIA.md):

* A CSP ainda usa `'unsafe-inline'`, necessário por causa dos blocos `<style>` e handlers inline herdados das telas.
* Não há autenticação em dois fatores.
* As bibliotecas (Dompdf 3.0.0, PHPMailer 6.9.2) estão versionadas na pasta em vez de gerenciadas por Composer, o que dificulta aplicar atualizações de segurança.
* Não há suíte de testes automatizados.
* A senha `admin123` da conta de demonstração é pública neste repositório e precisa ser trocada antes de qualquer uso real.

---

## ✅ Checklist antes de publicar em produção

1. `APP_ENV=production` e `APP_URL` preenchida no `.env`.
2. Senha do administrador trocada e conta `prof@chemicall.com` removida.
3. Usuário MySQL dedicado, com permissão apenas no banco `chemicall` (não use `root`).
4. HTTPS ativo e `mod_rewrite`/`mod_headers` habilitados no Apache.
5. `php database/migrate.php` executado.
6. Diretório `storage/logs/` gravável pelo servidor web e inacessível pela web.
7. Credenciais SMTP de uma conta institucional de serviço.
