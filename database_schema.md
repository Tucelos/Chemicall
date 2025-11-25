# Documentação do Banco de Dados - Chemicall

Esta documentação descreve a estrutura atual do banco de dados utilizado na aplicação Chemicall.

## Diagrama ER (Entidade-Relacionamento)

```mermaid
erDiagram
    funcionario ||--o{ movimentacoes : "realiza"
    reagentes ||--o{ movimentacoes : "sofre"

    funcionario {
        int cod_funcionario PK
        string login_funcionario
        string senha
        string nome
        string email
        string tipo "admin ou user"
    }

    reagentes {
        int id PK
        string nome
        string formula_quimica
        decimal massa_molar
        string concentracao
        decimal densidade
        date validade
        string fabricante
        enum condicao "aberto, fechado"
        string numero_cas
        string numero_ncm
        string numero_nota_fiscal
        int quantidade
        timestamp created_at
    }

    movimentacoes {
        int id PK
        int reagente_id FK
        int funcionario_id FK
        string tipo_movimentacao "entrada, saida, criacao, edicao"
        int quantidade
        datetime data_hora
    }
```

## Descrição das Tabelas

### 1. funcionario
Armazena os dados dos usuários do sistema (administradores e docentes/técnicos).
- **cod_funcionario**: Identificador único.
- **tipo**: Define o nível de acesso ('admin' tem acesso total).

### 2. reagentes
Armazena o inventário de reagentes químicos.
- **id**: Identificador único.
- **quantidade**: Quantidade atual em estoque (em unidades, ex: frascos).
- **validade**: Data de validade do reagente.

### 3. movimentacoes
Registra o histórico de todas as operações realizadas no estoque.
- **tipo_movimentacao**:
    - `criacao`: Quando um novo reagente é cadastrado.
    - `edicao`: Quando os dados de um reagente são alterados.
    - `entrada`: Adição de quantidade ao estoque.
    - `saida`: Remoção de quantidade do estoque.
- **data_hora**: Momento exato da operação.
