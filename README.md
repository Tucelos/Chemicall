# 🧪 Chemicall

> **Sistema de Gerenciamento de Reagentes Químicos**

![Status](https://img.shields.io/badge/Status-Em_Desenvolvimento-yellow)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MariaDB](https://img.shields.io/badge/MariaDB_%2F_MySQL-10.2%2B_%2F_5.7%2B-orange)
![License](https://img.shields.io/badge/License-GPL--3.0-green)

---

## 📖 Sobre o Projeto

**Chemicall** é um sistema web para controle de estoque de reagentes químicos em
laboratórios. Permite rastrear estoque, controlar validade, registrar
movimentações e gerar relatórios gerenciais — com atenção especial a produtos
controlados (Polícia Federal / Exército), cujo histórico de movimentação precisa
ser auditável.

Trabalho de Conclusão de Curso em Análise e Desenvolvimento de Sistemas, por
**Tuigg Barcelos**.

### Funcionalidades

- **Controle de estoque** — cadastro com fórmula, CAS, NCM, nota fiscal, validade e capacidade por unidade.
- **Alertas de validade** — reagentes vencidos ou a vencer em 90 dias são sinalizados na listagem e no painel, por cor, ícone e texto.
- **Produtos controlados** — visíveis e movimentáveis apenas por quem tem permissão explícita.
- **Histórico auditável** — toda entrada, saída, edição e exclusão registra quem fez, quando e por quê.
- **Relatórios em PDF e estatísticas** de consumo.
- **Três perfis de acesso** — administrador, gestor e usuário comum.

### Tecnologias

PHP puro (sem framework) · MariaDB/MySQL · Bootstrap 5.3 · Dompdf 3.0 · PHPMailer 6.9

---

## ✅ Requisitos

Verificados na instalação de referência descrita abaixo.

| Item | Versão | Observação |
| :--- | :--- | :--- |
| **PHP** | **7.4 ou superior** | Testado em 8.2.12. Usa propriedades tipadas e arrow functions. |
| **MariaDB** ou **MySQL** | 10.2+ / 5.7+ | Testado em MariaDB 10.4.32. |
| **Apache** | 2.4 | Com `mod_rewrite`, `mod_headers` e `AllowOverride All`. |
| **Espaço em disco** | ~20 MB | Dompdf e PHPMailer já vêm no repositório. |

**Extensões PHP** (todas presentes por padrão no XAMPP):

| Extensão | Para quê |
| :--- | :--- |
| `pdo_mysql` | conexão com o banco |
| `mbstring` | acentuação e geração de PDF |
| `dom` | geração de PDF (Dompdf) |
| `json` | respostas AJAX |
| `filter` | validação de e-mail e quantidades |
| `openssl` | envio de e-mail por TLS |
| `session` | autenticação |

Para conferir de uma vez:

```bash
C:/xampp/php/php.exe -r "foreach(['pdo_mysql','mbstring','dom','json','filter','openssl','session'] as \$e) printf('%-10s %s%s', \$e, extension_loaded(\$e)?'OK':'FALTA', PHP_EOL);"
```

Todas devem responder `OK`.

> **As tabelas precisam ser InnoDB.** O controle de estoque usa
> `SELECT ... FOR UPDATE` para impedir que duas retiradas simultâneas leiam o
> mesmo saldo — MyISAM não suporta isso. O schema fornecido já cria tudo em InnoDB.

---

## 🚀 Instalação

O procedimento abaixo foi executado do zero em uma instalação limpa; cada
comando é literal e pode ser copiado.

Os caminhos completos (`C:/xampp/php/php.exe`) são usados de propósito: assim os
comandos funcionam em qualquer terminal do Windows — **Shell do XAMPP**, Git Bash
ou PowerShell — sem depender do PATH. Em Linux, substitua por `php` e `mysql`.

### 1. Colocar o projeto no servidor

```bash
git clone <url-do-repositorio> C:/xampp/htdocs/Chemicall
```

Sem git, baixe o ZIP e extraia em `C:\xampp\htdocs\Chemicall`.

> O nome da pasta define a URL (`http://localhost/Chemicall/`). Em Linux o nome
> diferencia maiúsculas — escolha um e use sempre o mesmo.

Inicie **Apache** e **MySQL** no Painel de Controle do XAMPP.

### 2. Criar o banco e o usuário da aplicação

Não use `root` na aplicação: crie um usuário com acesso apenas a este banco.

```bash
C:/xampp/mysql/bin/mysql.exe -u root -p -e "CREATE USER 'chemicall_app'@'localhost' IDENTIFIED BY 'TROQUE_ESTA_SENHA'; GRANT SELECT, INSERT, UPDATE, DELETE ON chemicall.* TO 'chemicall_app'@'localhost'; FLUSH PRIVILEGES;"
```

*(Se o root do XAMPP não tiver senha, omita o `-p`.)*

### 3. Importar o schema

```bash
C:/xampp/mysql/bin/mysql.exe -u root -p < C:/xampp/htdocs/Chemicall/database/chemicall_schema.sql
```

O script cria o banco `chemicall`, as 5 tabelas (`funcionario`, `reagentes`,
`movimentacoes`, `esqueceu_senha`, `tentativas_login`) e dados de exemplo:
**2 usuários, 15 reagentes e 15 movimentações**.

> ⚠️ **O schema começa com `DROP DATABASE IF EXISTS chemicall`.** Se você já tem
> um banco `chemicall` com dados reais, **ele será apagado**. Para atualizar uma
> instalação existente, pule este passo e vá direto ao passo 5.

### 4. Configurar o `.env`

O arquivo `.env` **não vem no repositório** (contém senhas). Crie-o a partir do
modelo:

```bash
cd C:/xampp/htdocs/Chemicall && cp .env.example .env
```

Abra o `.env` e ajuste no mínimo estas quatro linhas:

```ini
APP_ENV=development
APP_URL=http://localhost/Chemicall
DB_PASS=TROQUE_ESTA_SENHA
DB_NAME=chemicall
```

| Variável | O que faz |
| :--- | :--- |
| `APP_ENV` | `development` mostra os erros na tela. **`production` esconde tudo** e exige HTTPS (veja o aviso abaixo). |
| `APP_URL` | Base do link de redefinição de senha. Sem ela o link é montado a partir do cabeçalho `Host`, que pode ser forjado. |
| `DB_*` | Credenciais do passo 2. |
| `SMTP_*` | Opcional. Sem SMTP o sistema funciona normalmente; apenas o "Esqueceu a senha?" fica indisponível. |

> ⚠️ **`APP_ENV=production` sem HTTPS impede o login.** Em produção o cookie de
> sessão recebe a flag `Secure` e o navegador deixa de enviá-lo por HTTP — o
> login falha em silêncio. Use `development` localmente e só mude para
> `production` quando houver certificado.

### 5. Aplicar a migração — só ao atualizar

**Instalação nova: pule este passo.** O schema do passo 3 já cria tudo.

Este script existe para **atualizar uma instalação antiga** sem perder dados. Ele
cria tabelas e índices, portanto exige permissão de `CREATE` e `ALTER` — que o
usuário `chemicall_app` do passo 2 **não tem**, de propósito. Rode com um usuário
administrador do MySQL:

```bash
cd C:/xampp/htdocs/Chemicall && DB_USER=root DB_PASS= C:/xampp/php/php.exe database/migrate.php
```

A sintaxe `VARIAVEL=valor comando` funciona no Shell do XAMPP e no Git Bash.
No PowerShell, use `$env:DB_USER="root"; C:/xampp/php/php.exe database/migrate.php`
— ou simplesmente edite `DB_USER` no `.env` durante a migração e reverta depois.

Variáveis de ambiente **têm precedência sobre o `.env`**, então nada precisa ser
alterado em arquivo.

O script é idempotente — rodar de novo é seguro. Passos já aplicados aparecem
como `[pulado]`. Se algum falhar, ele lista quais e encerra com código de erro
(em vez de anunciar sucesso pela metade).

### 6. Permissões de log

O sistema grava erros em `storage/logs/app.log`. No Windows/XAMPP funciona sem
ajuste. Em Linux:

```bash
mkdir -p storage/logs && chown -R www-data:www-data storage/logs && chmod 750 storage/logs
```

### 7. Acessar

Abra **`http://localhost/Chemicall/`** — a raiz redireciona para o login.

| Perfil | E-mail | Senha |
| :--- | :--- | :--- |
| Administrador | `admin@chemicall.com` | `admin123` |
| Usuário comum | `prof@chemicall.com` | `admin123` |

> 🔴 **Estas senhas são públicas neste repositório.** Troque a do administrador
> em *Perfil → Alterar Senha* e remova a conta de teste antes de qualquer uso real.

---

## 🔍 Verificando se deu certo

Depois do login, confira nesta ordem. Os resultados abaixo são os da instalação
de referência, feita com os dados de exemplo do schema.

| # | O que fazer | O que deve acontecer |
| :-- | :--- | :--- |
| 1 | Abrir o **Painel** | Quatro contadores. "Reagentes no sistema" = **15**. Vários aparecem em **Vencidos em estoque**, e a lista *Precisam de atenção* os detalha. |
| 2 | Ir em **Estoque** | Tabela com **15 linhas**. Os vencidos exibem o selo vermelho "Vencido há N dias". |
| 3 | Clicar no botão amarelo de um reagente e retirar **1** unidade | Mensagem de sucesso, quantidade diminui em 1, e a operação aparece em *Movimentações recentes* no painel. |
| 4 | Tentar retirar mais do que existe (ex.: 999) | Deve ser **recusado**, com a mensagem informando o saldo disponível — e nada é gravado no histórico. |
| 5 | **Relatórios → Gerar PDF** | Baixa um PDF de aproximadamente 19 KB. |
| 6 | Abrir **Usuários** | Lista os 2 usuários. O menu só aparece para o administrador. |

> Os dados de exemplo têm validades fixas entre 2025 e 2026, então a quantidade
> de itens vencidos aumenta conforme o tempo passa — isso é esperado e serve
> justamente para demonstrar os alertas de validade.

---

## 🩺 Se algo der errado

O sistema **não mostra detalhes de erro na tela por segurança**. A causa real
está sempre em `storage/logs/app.log`:

```bash
tail -20 C:/xampp/htdocs/Chemicall/storage/logs/app.log
```

> O arquivo só é criado no primeiro erro. Se o comando responder
> *"No such file or directory"*, é sinal de que nada falhou ainda — e o problema
> está antes do PHP (Apache parado, pasta errada, `mod_rewrite` desligado).

| Sintoma | Causa provável | Solução |
| :--- | :--- | :--- |
| *"Ocorreu um erro inesperado"* logo ao abrir | `.env` ausente ou credenciais de banco erradas | Confira o passo 4. O log traz o erro exato do MySQL. |
| Login não entra, sem mensagem de erro | `APP_ENV=production` sem HTTPS | Use `APP_ENV=development` em ambiente local. |
| *"Requisição inválida (token de segurança...)"* | Página aberta há muito tempo; a sessão expirou | Recarregue a página e tente de novo. |
| `Table 'chemicall.reagentes' doesn't exist` | Schema não importado | Refaça o passo 3. |
| Migração acusa `CREATE command denied` | Usuário sem permissão de DDL | Rode a migração como administrador do MySQL (passo 5). |
| 404 em vez do login | `mod_rewrite` desligado ou `AllowOverride None` | Acesse direto `/src/telas/login/index.php` para confirmar; ajuste o Apache. |
| "Esqueceu a senha?" não envia e-mail | SMTP não configurado ou bloqueado pela rede | Veja o log. Enquanto isso, o administrador troca a senha em *Usuários → Editar*. |
| Estatísticas vazias | Não há saídas registradas no período selecionado | Troque o período para "Todo o Período". |

---

## 📁 Estrutura

```
Chemicall/
├── index.php                 Redireciona a raiz para o login
├── .htaccess                 Cabeçalhos de segurança, bloqueio de arquivos, páginas de erro
├── .env.example              Modelo de configuração (copie para .env)
├── database/
│   ├── chemicall_schema.sql  Schema completo + dados de exemplo
│   ├── migrate.php           Migração idempotente (CLI)
│   └── seed_reagents.php     Carga opcional de exemplos (CLI)
├── docs/
│   └── PLANO_DE_MELHORIA.md  Auditoria de segurança e pendências
├── storage/logs/             Log da aplicação (criado em tempo de execução)
└── src/
    ├── config/               bootstrap.php (sessão, CSRF, CSP) e mailer.php
    ├── controllers/          Auth, Funcionario, Reagente
    ├── db/                   Conexão PDO
    ├── componentes/          Cabeçalho e logout
    └── telas/                Telas por área (login, dashboard, reagentes, usuários, relatório)
```

---

## 🚢 Produção

Este README cobre a instalação local. Para publicar em servidor, há passos
adicionais obrigatórios — usuário de banco dedicado, HTTPS, rotação de
credenciais, ocultação da versão do servidor — todos detalhados em
**[docs/PLANO_DE_MELHORIA.md](docs/PLANO_DE_MELHORIA.md)** (Parte 2) e no
checklist de **[SECURITY.md](SECURITY.md)**.

---

## 📄 Licença

Distribuído sob a **GNU General Public License v3.0** — veja [LICENSE](LICENSE).

> ⚠️ O arquivo [NOTICE](NOTICE) ainda menciona a Apache License 2.0, herdado de
> uma versão anterior do projeto. As duas licenças são incompatíveis entre si;
> o `NOTICE` precisa ser corrigido ou removido para que a licença do projeto
> fique inequívoca.
