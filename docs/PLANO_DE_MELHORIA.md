# Plano de Melhoria — Chemicall

Auditoria de segurança, estabilidade e qualidade realizada antes da entrada em
produção. Cada falha listada na Parte 1 foi **reproduzida em ambiente real**
(PHP 8.2 + MariaDB 10.4, banco `chemicall` com dados de exemplo) antes de ser
corrigida, e a correção foi verificada re-executando o mesmo teste.

---

## Parte 1 — Falhas corrigidas

### 🔴 Críticas

#### C1. CSRF em todo o sistema

Nenhum endpoint validava a origem da requisição. A exclusão de reagentes ainda
era feita por **GET**, então bastava uma imagem em outro site para apagar dados
do estoque de um administrador logado.

*Reproduzido:* uma requisição GET com `Referer` externo excluiu o reagente
*Permanganato de Potássio (Controlado)* — `ativo` passou de `1` para `0`. Um POST
sem token aprovou um cadastro pendente.

**Correção** — `src/config/bootstrap.php`
* Token de 32 bytes por sessão, comparado com `hash_equals()`.
* `csrf_exigir()` em todos os endpoints que alteram estado; sem token → HTTP 403.
* `exigir_post()` nas operações destrutivas; `delete.php` agora só aceita POST.
* Cookie de sessão com `SameSite=Lax`.

*Verificado:* GET de exclusão → HTTP 405 sem alterar o banco; POST sem token →
rejeitado; POST com token → funciona. Dos 17 formulários da tela de estoque, os
16 que alteram estado carregam o token (o 17º é a busca por GET).

---

#### C2. XSS armazenado no formulário de reagentes

Os campos de `form.php` eram impressos sem escape:
`value="<?php echo $reagente['nome']; ?>"`.

*Reproduzido:* um reagente com o nome `" autofocus onfocus=alert(document.cookie) x="`
escapou do atributo e virou HTML executável.

**Correção** — os 12 campos passaram a usar o helper `e()`.
*Verificado:* o mesmo payload agora é renderizado como `&quot;...` — texto inerte.

---

#### C3. Cookie de sessão sem HttpOnly

`db_connection.php` configurava `session.cookie_httponly`, mas
`AuthController.php` chamava `session_start()` na linha 2, **antes** do
`require`. Com a sessão já iniciada, o `ini_set` não tinha efeito — a proteção
era código morto.

*Reproduzido:* `Set-Cookie: PHPSESSID=...; path=/` — sem HttpOnly, sem SameSite.
Combinado com C2, permitia roubo de sessão e tomada da conta de administrador.

**Correção** — toda a configuração de sessão foi para `bootstrap.php`, que roda
antes de qualquer `session_start()`. Cookie renomeado para `CHEMICALL_SESSID`,
com `HttpOnly`, `SameSite=Lax`, `use_strict_mode` e `Secure` em produção.

*Verificado:* `Set-Cookie: CHEMICALL_SESSID=...; path=/; HttpOnly; SameSite=Lax`.

---

#### C4. Vazamento de informação em erros

`display_errors` estava ligado. Qualquer falha exibia caminho absoluto do
servidor, stack trace e estrutura do banco.

*Reproduzido:* `Fatal error: ... Table 'chemicall.insumo' doesn't exist in
C:\xampp\htdocs\Chemicall\src\componentes\pesquisa.php:19` — exibido a um
visitante **não autenticado**.

**Correção** — exibição controlada por `APP_ENV`. Em produção o usuário vê uma
mensagem genérica e o detalhe vai para `storage/logs/app.log`. Todos os
`catch (PDOException)` que devolviam `$e->getMessage()` passaram a registrar no
log e retornar texto neutro.

*Verificado:* com o banco apontando para um nome inexistente, a resposta HTTP foi
apenas *"Ocorreu um erro inesperado..."*, enquanto o log registrou
`Unknown database 'banco_inexistente'`.

---

#### C5. Scripts administrativos expostos na web

`database/check_db.php` executava `SELECT * FROM funcionario` e imprimia cada
registro com `print_r()` — **incluindo o hash de senha de todos os usuários**.
`database/create_tables.php` **redefinia a senha do administrador para
`admin123`** a cada execução. Ambos respondiam pela web.

Hoje falhavam por um caminho de `require` quebrado — ou seja, estavam protegidos
por acidente, não por decisão.

**Correção** — arquivos removidos do repositório. Os scripts legítimos que
sobraram (`migrate.php`, seeds) recusam execução fora da linha de comando, e
`database/.htaccess` nega acesso ao diretório inteiro.

*Verificado:* `check_db.php`, `create_tables.php` e `gerar_hash.php` → HTTP 404;
`migrate.php` → HTTP 403.

---

#### C6. Telas sem verificação de autenticação

Cinco endpoints não checavam login: `estoque/estoque.php`,
`gerenciar/gerenciar.php`, `componentes/pesquisa.php`, `componentes/filtro.php`
e `componentes/paginacao_pesquisa.php`.

*Reproduzido:* `curl` sem cookie recebeu HTTP 200 com o menu de navegação
renderizado e o rótulo de perfil "Usuário Comum".

**Correção** — faziam parte do subsistema morto removido em A1.
*Verificado:* os cinco → HTTP 404.

---

### 🟠 Altas

#### A1. Subsistema morto com SQL injection

As telas `estoque/`, `gerenciar/`, `visualizar/`, `inicio/` e os componentes de
busca consultavam a tabela **`insumo`, que não existe no schema**. Todas
quebravam com erro fatal, e concatenavam entrada do usuário direto no SQL:

```php
$sql = "SELECT * FROM insumo WHERE produto LIKE '%$pesquisar%' ... LIMIT $limit OFFSET $offset";
$query = "SELECT estoque_min, estoque_medio FROM insumo WHERE cod_insumo = '$cod_insumo'";
```

**Correção** — 21 arquivos removidos (4 telas, 3 componentes, 6 scripts JS e 7
folhas de estilo órfãs). Com eles saiu a conexão MySQLi legada: o sistema agora
usa exclusivamente PDO. O histórico do git preserva tudo.

---

#### A2. Corrupção do histórico de movimentações

`atualizarQuantidade()` truncava silenciosamente a retirada
(`if ($novaQuantidade < 0) $novaQuantidade = 0;`) mas registrava no log a
quantidade **pedida**, não a movimentada.

*Reproduzido:* retirada de 99 999 unidades de um estoque de 10 → estoque foi a 0
e `movimentacoes` gravou `quantidade = 99999`. Como `estatisticas.php` calcula
`SUM(m.quantidade)`, um único erro de digitação distorcia permanentemente o
relatório de consumo — justamente o registro que precisa ser confiável para
produtos controlados.

**Correção**
* Retirada acima do saldo é **recusada** com mensagem clara.
* Quantidade validada com `FILTER_VALIDATE_INT` (rejeita `-5`, `abc`, `1e10`, `3.7`, `0`).
* Movimentação em transação com `SELECT ... FOR UPDATE`, evitando que duas
  retiradas simultâneas leiam o mesmo saldo.
* Se o registro de auditoria falhar, a transação inteira é revertida.

*Verificado:* 99 999 de 10 → recusado, estoque intacto, nenhum log gravado.
Retirada válida de 3 unidades pela interface → estoque 10 → 7 e log com
`quantidade = 3`. Os cinco valores inválidos foram todos rejeitados.

---

#### A3. Login sem limite de tentativas

Força bruta ilimitada contra qualquer conta.

**Correção** — tabela `tentativas_login`: 5 falhas por e-mail/IP bloqueiam por 15
minutos. O login também compara a senha contra um hash descartável quando o
usuário não existe, para o tempo de resposta não denunciar contas válidas.

*Verificado:* tentativas 1–5 → "Email ou senha incorretos!"; da 6ª em diante →
"Muitas tentativas de login. Tente novamente em 15 minuto(s)." A senha **correta**
também é recusada durante o bloqueio.

> Durante o teste, a primeira versão do bloqueio não funcionou: o cálculo do
> tempo restante comparava `NOW()` do MySQL com `time()` do PHP, que estavam em
> fusos diferentes, e o resultado negativo zerava o bloqueio. O cálculo passou a
> ser feito inteiramente em SQL com `TIMESTAMPDIFF`.

---

#### A4. Token de redefinição de senha sem validade

O token não expirava nem era invalidado após o uso: um link vazado funcionava
para sempre. A nova senha só precisava não ser vazia — `"a"` era aceita.

**Correção** — validade de 30 minutos (`expira_em`), uso único (`usado_em`,
consumido dentro de transação), invalidação dos tokens anteriores a cada nova
solicitação, e política de senha aplicada. O link passou a ser montado a partir
de `APP_URL` em vez do cabeçalho `Host`, que um atacante pode forjar para
receber o token da vítima.

*Verificado:* token expirado e token já usado → "Link de redefinição inválido ou
expirado"; token válido → formulário exibido.

---

#### A5. Enumeração de usuários

`esqueceu_senha.php` respondia "Usuário não encontrado", permitindo mapear quais
e-mails estão cadastrados. O cadastro público respondia "Email já cadastrado".

**Correção** — resposta idêntica em ambos os casos; o motivo real vai para o log.

*Verificado:* e-mail existente e inexistente produzem exatamente a mesma resposta.

---

#### A6. Ausência de cabeçalhos de segurança

Nenhum cabeçalho de proteção era enviado — o sistema podia ser embutido em um
iframe para ataques de clickjacking.

**Correção** — `Content-Security-Policy`, `X-Frame-Options: DENY`,
`X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` e, em
produção, `Strict-Transport-Security`.

---

### 🟡 Médias

| # | Problema | Correção |
|---|---|---|
| M1 | `usuarios/editar.php` não enviava `status`, e o controller usava `?? 'ativo'`: editar um cadastro pendente o **aprovava silenciosamente**. | O status atual é preservado explicitamente. |
| M2 | O banco continha `tipo = 'docente'`, valor que nenhuma verificação reconhecia — esses usuários caíam no perfil mais restrito sem aviso. | `migrate.php` normalizou os 2 registros afetados; o controller passou a validar `tipo` e `status` contra listas fechadas. |
| M3 | `listar()` liberava controlados para admin **e** gestor, mas `atualizarQuantidade()` só para admin: o gestor via itens que não conseguia movimentar. | Regra unificada em `podeAcessarControlados()`. |
| M4 | O gestor não podia "adicionar estoque", mas podia criar um reagente com qualquer quantidade. | Entrada de estoque liberada para admin e gestor. |
| M5 | Um administrador podia excluir a si mesmo ou o último admin do sistema (a interface escondia o botão, mas o servidor não verificava). | Bloqueado no controller, com proteção também contra rebaixar/desativar o último admin. |
| M6 | Sem validação de força de senha em vários fluxos. | `validarSenha()` centraliza a regra: mínimo 8 caracteres, com letra e número. |
| M7 | `criar()` devolvia `$e->getMessage()` do banco para a tela pública de cadastro. | Erro no log; usuário recebe mensagem neutra. Duplicidade de e-mail agora é detectada pela constraint `UNIQUE`, sem a condição de corrida do `SELECT` anterior. |
| M8 | Migrations com caminhos de `require` quebrados, que nunca executavam. | Consolidadas em `database/migrate.php`, idempotente e testado. |
| M9 | README documentava a credencial `prof@chemicall.com / admin123`, que não existia no banco. | Documentação corrigida. |
| M10 | Sem expiração de sessão por inatividade. | `SESSION_IDLE_TIMEOUT` (padrão 30 min), com aviso na tela de login. |

---

## Parte 2 — Ações necessárias antes de publicar

Estas dependem de você e do ambiente; não dá para resolver no código.

### 🔴 Fazer obrigatoriamente

1. **Trocar a senha do administrador.** `admin123` está em texto claro no
   `README.md` e no `chemicall_schema.sql` deste repositório público. Trocar
   pelo modal de Perfil e remover a conta `prof@chemicall.com`.

2. **Rotacionar as credenciais do `.env`.** O arquivo contém uma senha de
   aplicativo do Gmail de uma conta pessoal (`anacarol.farias11@gmail.com`).
   Ela está apenas no disco local — confirmei que o `.env` **nunca foi commitado**
   —, mas para produção o correto é revogá-la no Google e usar uma conta
   institucional de serviço.

3. **Criar um usuário MySQL dedicado.** Hoje a aplicação conecta como `root`.
   Um usuário com permissão apenas no banco `chemicall` limita o estrago de uma
   eventual falha:

   ```sql
   CREATE USER 'chemicall_app'@'localhost' IDENTIFIED BY 'senha_forte_aqui';
   GRANT SELECT, INSERT, UPDATE, DELETE ON chemicall.* TO 'chemicall_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

4. **Definir `APP_ENV=production` e `APP_URL`** no `.env` do servidor.

5. **Habilitar HTTPS** e conferir que `mod_rewrite` e `mod_headers` estão ativos
   no Apache — o `.htaccess` depende deles.

6. **Esconder a versão do servidor.** O cabeçalho `Server:` ainda anuncia
   `Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12`, e o `X-Powered-By` do PHP
   idem. Estas duas diretivas não podem ser definidas em `.htaccess`; edite os
   arquivos de configuração do servidor:

   ```apache
   # C:\xampp\apache\conf\extra\httpd-default.conf
   ServerTokens Prod
   ServerSignature Off
   ```

   ```ini
   ; C:\xampp\php\php.ini
   expose_php = Off
   ```

   Não apliquei estas mudanças porque valem para **todos** os sites do XAMPP,
   não só o Chemicall — a decisão é sua.

### 🟠 Recomendado a seguir

6. **Migrar as bibliotecas para o Composer.** Dompdf 3.0.0 e PHPMailer 6.9.2
   estão versionados dentro do projeto. As versões atuais não têm
   vulnerabilidades conhecidas, mas sem o Composer não há como aplicar uma
   atualização de segurança de forma controlada.

7. **Backup automático do banco.** Um `mysqldump` diário com retenção de 30 dias.
   Para um sistema de controle de produtos controlados, perder o histórico de
   movimentações é o pior cenário possível.

8. **Testes automatizados.** Os pontos de maior risco, na ordem: as regras de
   `atualizarQuantidade()`, as guardas de permissão do `AuthController` e o ciclo
   de vida do token de redefinição.

9. **Retirar `'unsafe-inline'` da CSP.** Exige mover os blocos `<style>` e os
   handlers `onclick` das telas para arquivos externos.

### 🟢 Evolução do produto

10. **Auditoria de leitura para produtos controlados.** Hoje registramos quem
    movimentou. Para fiscalização da Polícia Federal / Exército, registrar
    também quem *consultou* costuma ser exigido.
11. **Alerta ativo de validade.** Existe o destaque visual; falta a notificação
    por e-mail (a infraestrutura SMTP já está pronta).
12. **Paginação na listagem de estoque.** Com 14 reagentes o desempenho é
    irrelevante; acima de algumas centenas, deixa de ser.
13. **Exportação em CSV**, além do PDF, para análise em planilha.

---

## Parte 2.1 — Varredura OWASP ZAP (05/09/2026)

Varredura com ZAP 2.17.0 contra `http://localhost/chemicall/`. Resultado bruto:
0 alertas Altos, 4 Médios, 3 Baixos, 3 Informativos.

### Leitura do relatório: a varredura não autenticou

O dado mais importante do relatório não é um alerta, e sim esta estatística:
**"Count of total endpoints: 6"**. As únicas URLs alcançadas foram:

| URL | O que é |
|---|---|
| `GET /chemicall/` | tela de login |
| `POST /chemicall/` | tentativa de login com `zaproxy@example.com` / `ZAP` |
| `GET /robots.txt` | não existe — 404 padrão do Apache, **fora do projeto** |
| `GET /sitemap.xml` | não existe — 404 padrão do Apache, **fora do projeto** |

O ZAP tentou entrar, foi recusado (corretamente) e parou ali. Ele até
identificou a proteção CSRF (`csrfToken=_csrf` no relatório), mas **nenhuma tela
autenticada foi testada** — estoque, usuários, relatórios, movimentações. Ou
seja: a varredura não diz nada sobre as falhas de CSRF, XSS, controle de acesso
e integridade de estoque tratadas na Parte 1, porque todas vivem atrás do login.

Para uma varredura com valor real, configure autenticação no ZAP
(*Context → Authentication → Form-based*, com `email`/`password`, usuário de
teste, e um indicador de sessão como a presença do link "Sair").

### Alertas corrigidos a partir do relatório

| Alerta | Risco | O que foi feito |
|---|---|---|
| **Sub Resource Integrity Attribute Missing** | Médio | Adicionado `integrity` (SHA-384 calculado a partir dos arquivos reais do CDN), `crossorigin` e `referrerpolicy` em 33 tags de 11 telas. Sem isso, um CDN comprometido injetaria JavaScript arbitrário no sistema. O carregador do Google Charts ficou de fora: é um *loader* dinâmico e SRI o quebraria. |
| **Cross-Domain JavaScript Source File Inclusion** | Baixo | Mesma causa; o SRI acima é a mitigação. |
| **In Page Banner Information Leak** / **CSP Header Not Set** em páginas de erro | Baixo / Médio | As páginas de erro do Apache não passam pelo PHP: exibiam `Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12` e não recebiam cabeçalho nenhum. Criada `src/telas/erro.php`, para onde os endereços inexistentes são desviados; `ServerSignature Off` remove a versão dos demais status. |

### Alertas que não se aplicam ao Chemicall

* **CSP Header Not Set (4)** — os alertas são de `/robots.txt` e `/sitemap.xml`,
  que são 404 do XAMPP na raiz do servidor, fora do projeto. O próprio relatório
  registra a resposta de `/chemicall/` **com** a CSP completa.
* **Server Leaks Version Information (5)** — real, mas resolvido em
  configuração de servidor (item 6 da Parte 2), não no código do projeto.
* **Session Management Response Identified** e **Solicitação de autenticação
  identificada** — informativos: o ZAP apenas registra que encontrou um cookie
  de sessão e um formulário de login.
* **User Controllable HTML Element Attribute** — informativo, confiança baixa,
  sobre a tela de login, que não reflete entrada do usuário em atributo algum.

### Pendência que a varredura confirma

**CSP com `unsafe-inline`** (2 alertas Médios) — legítimo e ainda em aberto. O
código tem 11 blocos `<style>`, 48 atributos `style="..."`, 13 blocos `<script>`
e 12 handlers `on*=` inline. Remover o `unsafe-inline` exige mover tudo isso
para arquivos externos (com *nonce* para os scripts). É a maior melhoria de
segurança ainda disponível, mas é refatoração de front-end, não correção
pontual.

### Bug encontrado durante esta rodada

A varredura acessou `http://localhost/chemicall/`, e foi por aí que apareceu um
defeito **introduzido pelo próprio `.htaccess` da entrega anterior**: a regra
`RewriteRule ^$ src/telas/login/index.php [L]` servia o login sob `/Chemicall/`
por reescrita interna, mantendo a base do navegador na raiz do projeto. Como as
telas usam caminhos relativos, o login funcionava mas redirecionava para
`http://localhost/dashboard/index.php` — 404 —, e os links "Solicitar Cadastro"
e "Esqueceu a senha?" também caíam em 404. Substituído por um `index.php` na
raiz que faz redirecionamento externo calculando a base a partir da requisição.

Na mesma rodada, o código de recusa por CSRF passou de `419` para `403`: o 419
não é um código HTTP registrado e o Apache o convertia em `500`, fazendo uma
requisição corretamente barrada parecer falha do servidor.

---

## Parte 2.2 — Portabilidade Windows / Linux

Ao levantar os requisitos para publicação no servidor da instituição,
apareceram três pontos que quebrariam a implantação em Linux. Todos corrigidos,
sem perder compatibilidade com o XAMPP.

### 1. Certificado do SMTP com caminho fixo do Windows

`esqueceu_senha.php` trazia `cafile => C:/xampp/apache/bin/cacert.pem`
fixo no código. Em Linux esse arquivo não existe e, com `verify_peer` ligado,
**todo envio de e-mail falharia**.

O teste revelou algo pior: **o arquivo não existe nem no Windows**. O bundle do
XAMPP chama-se `curl-ca-bundle.crt`. Ou seja, a redefinição de senha por e-mail
já estava quebrada — a conexão TLS falhava antes de chegar ao servidor.

**Correção** — novo módulo `src/config/mailer.php`, que resolve o certificado
nesta ordem:

1. `SMTP_CAFILE` do `.env`, se informado e legível;
2. o que o PHP já tem configurado (`openssl.cafile` do php.ini ou o padrão do
   OpenSSL) — caminho normal tanto no XAMPP quanto em Linux;
3. locais conhecidos das principais distribuições, como último recurso.

Quando nada precisa ser forçado, a chave `cafile` simplesmente não é enviada e o
OpenSSL usa a base do sistema. A verificação do certificado **continua sempre
ativa**: desligá-la abriria caminho para interceptar as mensagens de
redefinição de senha.

*Verificado:* com o caminho antigo, o handshake TLS com `smtp.gmail.com:465`
falhava; com a correção, conecta. A autenticação ainda é recusada, mas por
credencial — a senha de aplicativo no `.env` precisa ser renovada (item 2 da
Parte 2), o que é outro problema.

O módulo também passou a ler **toda** a configuração de e-mail do `.env`,
incluindo `SMTP_TIMEOUT` e o modo de criptografia, que agora aceita `ssl`,
`tls`/`starttls` e `none` — este último para relay interno da instituição.

### 2. Páginas de erro com o nome da pasta fixo

Os `ErrorDocument` do `.htaccess` apontavam para `/Chemicall/src/telas/erro.php`.
Publicar o projeto em pasta com outro nome faria o Apache voltar a servir a
página de erro padrão.

**Correção** — o caso comum (endereço inexistente) passou a ser tratado por
reescrita interna com destino **relativo**, que não depende do nome da pasta:

```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ src/telas/erro.php [L]
```

Para os demais status (403 no `.env`, 500 do PHP), o `ServerSignature Off` já
remove a versão do servidor da página padrão do Apache — verificado
isoladamente, sem `ErrorDocument`. Não há, portanto, perda de segurança.

*Verificado:* o projeto foi copiado para uma pasta chamada `sistema_quimica`
e, **sem editar uma linha**, o 404 continuou sendo servido pela página da
aplicação, o `.env` continuou bloqueado sem banner e a raiz redirecionou
corretamente para `/sistema_quimica/src/telas/login/index.php`.

### 3. Includes relativos do PHPMailer

`require PHPMailer/src/PHPMailer.php` dependia do diretório de trabalho do
processo, que varia entre Apache com mod_php e PHP-FPM. Trocado por
`__DIR__`, como no restante do projeto.

### Ordem das regras no .htaccess

Aproveitando a revisão: o redirecionamento para HTTPS estava **depois** do
tratador de erro, que tem a flag `[L]`. Parte das requisições nunca chegaria à
regra de HTTPS. Os dois blocos foram unificados, com o HTTPS avaliado primeiro.

---

## Parte 2.3 — Revisão de engenharia, design e acessibilidade

Avaliação conduzida em três frentes paralelas: um agente para práticas de
engenharia, outro para auditoria WCAG 2.1 AA, e crítica visual das telas no
navegador com a skill `design-critique`.

### XSS armazenado que passou na auditoria anterior

O achado mais grave veio de raspão, durante a auditoria de acessibilidade. Em
`reagentes/index.php`, o modal "Retirar em Lote" montava o HTML por template
literal e o inseria com `insertAdjacentHTML`:

```js
const nome = cb.getAttribute(data-nome);
const itemHtml = `<label ...>${nome} ...</label>`;
container.insertAdjacentHTML(beforeend, itemHtml);
```

O PHP escreve o nome escapado no atributo `data-nome`, mas **`getAttribute()`
devolve o valor decodificado** — e daí ele volta a ser HTML executável. Um
reagente chamado `<img src=x onerror=...>` executava script em qualquer usuário
que selecionasse o item e abrisse o modal.

Isso não apareceu na revisão anterior porque, no PHP, a saída *está* escapada; o
defeito nasce no caminho de volta, dentro do JavaScript. Ironicamente, o modal de
exclusão logo abaixo já fazia certo, com `createElement` + `textContent`.

*Reproduzido:* `payloadExecutou: true`.
**Correção** — os nós passaram a ser criados pela API do DOM, com o nome
atribuído via `textContent` (mesmo padrão do modal de exclusão).
*Verificado:* `payloadExecutou: false`, zero elementos `<img>` injetados, payload
renderizado como texto literal.

### Erros silenciosos

Sete pontos capturavam exceções sem registrar nada. Em `estatisticas.php`, isso
fazia "banco de dados fora do ar" e "nenhum consumo no período" produzirem
exatamente a mesma tela. E `pdf/inventario.php` ainda fazia
`die("Erro ao buscar estoque: " . $e->getMessage())`, contrariando a política de
tratamento de erros do próprio `bootstrap.php`.

**Correção** — `error_log()` nos 5 `catch` mudos e `chemicall_fail()` nos 2 `die`.

### N+1 confirmado por medição (não corrigido nesta rodada)

A tela de estoque executa 3 consultas **por linha renderizada**, dentro do
`foreach`, para popular os modais de detalhes e histórico.

*Medido:* **40 consultas para 13 reagentes**. Projeção para um estoque de 300
itens: cerca de 900 consultas por carregamento.

Não foi corrigido por decisão de escopo — hoje o volume é pequeno e a tela
responde bem. Fica registrado como o próximo item de performance, e é
pré-requisito para escrever o primeiro teste automatizado do sistema, já que a
mesma refatoração precisa tirar a leitura de `$_SESSION` de dentro do
`ReagenteController`.

### Alertas de validade — o README prometia, o sistema não entregava

O `README.md` anunciava "Alertas de Validade: notificações visuais para
reagentes próximos do vencimento". Na prática isso só existia como caixa de
seleção no relatório PDF: na tela de estoque, **4 reagentes vencidos — um deles
controlado — apareciam exatamente iguais aos válidos**.

**Correção** — `ReagenteController::situacaoValidade()` classifica cada reagente
em vencido / vence em breve (90 dias) / válido, devolvendo cor, ícone **e**
rótulo em texto, para a informação não depender só de cor. O alerta aparece na
coluna Validade e, nas telas pequenas, é espelhado sob o nome do reagente.

### Tabela de estoque no celular

Em 375 px, as colunas **Qtd.** e **Ações** ficavam fora da tela: o técnico via
Densidade e Concentração, mas precisava rolar lateralmente para chegar ao que
usa. Como a tabela é a tela mais acessada e o uso típico é com o celular na
bancada, a ordem foi invertida para **Nome → Qtd. → Ações → Validade**, e as
propriedades físicas passaram a aparecer só a partir de `md`/`lg`/`xl`. Os
selos "Controlado" e de vencimento são espelhados sob o nome no celular.

### Painel inicial

O dashboard fazia **zero consultas ao banco** — era um menu de atalhos. Passou a
abrir com quatro indicadores (total, vencidos em estoque, vencendo em 90 dias,
esgotados), a lista dos reagentes que exigem atenção e as movimentações
recentes. *Medido:* 3 consultas, todas agregadas ou com `LIMIT` — sem N+1.

### Acessibilidade — auditado, não corrigido

A auditoria WCAG 2.1 AA levantou 6 achados críticos, 12 sérios e 12 moderados,
sendo 4 de Nível A. Por decisão de escopo, **nada foi corrigido nesta rodada**.
Os principais pendentes:

| Achado | Critério | Severidade |
|---|---|---|
| Linha da tabela abre os detalhes só por clique de mouse (sem `tabindex`/`role`) | 2.1.1 Teclado (A) | Crítico |
| ~25 campos de `form.php` e dos modais sem `<label for>` | 3.3.2 / 1.3.1 (A) | Crítico |
| `outline: none` nos inputs de `reset.css`, sem indicador de foco substituto | 2.4.7 (AA) | Crítico |
| `text-warning` sobre branco = 1,63:1; mensagens do reset = 1,55:1 e 2,52:1 | 1.4.3 (AA) | Crítico |
| Sem skip link e sem `<main>` em nenhuma tela | 2.4.1 (A) | Sério |
| Gráficos sem alternativa textual; fatias distinguíveis só por cor | 1.1.1 / 1.4.1 (A) | Sério |

Estimativa da auditoria: 4 h para fechar os críticos e o Nível A; ~10 h para
conformidade AA completa. Como a instituição é pública, isso tem peso legal
(LBI/Lei 13.146) além de valor de banca.

**O que já está correto e não deve ser mexido:** a paleta da marca é acessível de
verdade — `#006233` rende **7,51:1** sobre branco nos dois sentidos, e o hover
`#004d28` chega a 10,04:1. Os selos já comunicam por texto ("Sim"/"Não",
"Administrador"), não só por cor. `lang="pt-br"` está em todas as telas. E
`solicitar_cadastro.php` e `usuarios/editar.php` já têm `for`/`id` em 100% dos
campos — servem de referência para corrigir os demais.

### Débito técnico levantado e deliberadamente não tratado

| Item | Por quê ficou de fora |
|---|---|
| Retornos incoerentes dos controllers (`bool` vs `array`) | Refatoração ampla; hoje degrada mensagens de erro, mas não quebra nada |
| ~280 linhas duplicadas (`formatarQuantidade()` em 3 cópias, dois modais quase idênticos) | Funciona; é custo de manutenção, não defeito |
| Sem `composer.json`, autoload, testes ou CI | Já registrado na Parte 2 |
| 330 linhas de `<style>` embutido, `#006233` repetido 33× | Extrair resolveria também o `unsafe-inline` da CSP — ver Parte 2.1 |

O parecer da revisão de engenharia foi explícito quanto a **não** reescrever o
projeto em framework nem mexer no `src/config/bootstrap.php`, apontado como a
melhor parte do código.

---

## Parte 3 — Como os testes foram feitos

Ambiente: PHP 8.2.12, MariaDB 10.4.32, servidor embutido do PHP servindo
`C:/xampp/htdocs`, banco `chemicall` com 14 reagentes e 4 usuários reais.

| Verificação | Método |
|---|---|
| Sintaxe | `php -l` em todos os arquivos do projeto — sem erros |
| CSRF | Requisições `curl` com `Referer` externo, com e sem token |
| XSS | Payload real gravado no banco e HTML de resposta inspecionado |
| Integridade de estoque | Retiradas acima do saldo e entradas inválidas, comparando `reagentes` e `movimentacoes` antes/depois |
| Força bruta | 7 tentativas consecutivas + tentativa com senha correta durante o bloqueio |
| Escalação de privilégio | Sessão de usuário comum tentando acessar telas e endpoints de admin/gestão |
| Produtos controlados | Usuário sem permissão tentando listar e movimentar item controlado |
| Vazamento de erro | Banco apontado para nome inexistente, em `production` e em `development` |
| Fluxo funcional | Navegador real: login, listagem, retirada pelo modal, perfil, usuários, relatórios, geração de PDF |
| Regressão | Todas as telas conferidas contra `Fatal error`, `Warning`, `Notice` e `Deprecated` — zero ocorrências |

Todos os dados de teste foram removidos ao final; o banco ficou no estado
original (14 reagentes ativos, 4 usuários).
