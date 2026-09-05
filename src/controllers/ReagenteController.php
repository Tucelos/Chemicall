<?php
require_once __DIR__ . '/../db/db_connection.php';

/**
 * Regras de negócio do inventário de reagentes.
 */
class ReagenteController
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /** Dias de antecedência a partir dos quais um reagente é sinalizado. */
    public const DIAS_ALERTA_VALIDADE = 90;

    /**
     * Classifica a validade de um reagente.
     *
     * A informação é devolvida com rótulo e ícone além da cor, para que a
     * situação continue legível em impressão em preto e branco e para quem não
     * distingue as cores.
     *
     * @return array{estado:string, rotulo:string, classe:string, icone:string, dias:int|null}
     */
    public static function situacaoValidade(?string $validade): array
    {
        if (empty($validade)) {
            return ['estado' => 'sem_data', 'rotulo' => 'Sem validade', 'classe' => 'secondary', 'icone' => 'fa-circle-question', 'dias' => null];
        }

        $hoje = new DateTimeImmutable('today');
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $validade);
        if ($data === false) {
            return ['estado' => 'sem_data', 'rotulo' => 'Data inválida', 'classe' => 'secondary', 'icone' => 'fa-circle-question', 'dias' => null];
        }

        $dias = (int) $hoje->diff($data)->format('%r%a');

        if ($dias < 0) {
            return [
                'estado' => 'vencido',
                'rotulo' => 'Vencido há ' . abs($dias) . ' dia' . (abs($dias) === 1 ? '' : 's'),
                'classe' => 'danger',
                'icone'  => 'fa-triangle-exclamation',
                'dias'   => $dias,
            ];
        }

        if ($dias <= self::DIAS_ALERTA_VALIDADE) {
            return [
                'estado' => 'vence_em_breve',
                'rotulo' => $dias === 0 ? 'Vence hoje' : 'Vence em ' . $dias . ' dia' . ($dias === 1 ? '' : 's'),
                'classe' => 'warning',
                'icone'  => 'fa-clock',
                'dias'   => $dias,
            ];
        }

        return ['estado' => 'valido', 'rotulo' => 'Dentro da validade', 'classe' => 'success', 'icone' => 'fa-circle-check', 'dias' => $dias];
    }

    /**
     * Contadores para o painel inicial, em uma única consulta.
     *
     * @return array{total:int, vencidos:int, vencendo:int, esgotados:int, controlados:int}
     */
    public function resumoEstoque(): array
    {
        $vazio = ['total' => 0, 'vencidos' => 0, 'vencendo' => 0, 'esgotados' => 0, 'controlados' => 0];

        try {
            $stmt = $this->conn->prepare(
                // Vencidos e "vencendo" contam apenas itens que ainda existem em
                // estoque: são os que exigem ação. O que já zerou é contado como
                // esgotado, para o número do painel bater com a lista de alertas.
                'SELECT
                    COUNT(*) AS total,
                    SUM(quantidade > 0 AND validade IS NOT NULL AND validade < CURDATE()) AS vencidos,
                    SUM(quantidade > 0 AND validade IS NOT NULL AND validade >= CURDATE()
                        AND validade <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)) AS vencendo,
                    SUM(quantidade = 0) AS esgotados,
                    SUM(controlado = 1) AS controlados
                 FROM reagentes
                 WHERE ativo = 1' . ($this->podeAcessarControlados() ? '' : ' AND controlado = 0')
            );
            $stmt->bindValue(':dias', self::DIAS_ALERTA_VALIDADE, PDO::PARAM_INT);
            $stmt->execute();
            $linha = $stmt->fetch();

            return $linha ? array_map('intval', array_merge($vazio, array_filter($linha, 'is_numeric'))) : $vazio;
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao montar resumo do estoque: ' . $e->getMessage());
            return $vazio;
        }
    }

    /**
     * Reagentes que exigem atenção — vencidos primeiro, depois os que vencem
     * dentro da janela de alerta.
     */
    public function reagentesEmAlerta(int $limite = 5): array
    {
        try {
            $sql = 'SELECT id, nome, validade, quantidade, unidade_medida, controlado
                    FROM reagentes
                    WHERE ativo = 1 AND quantidade > 0
                      AND validade IS NOT NULL
                      AND validade <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)'
                 . ($this->podeAcessarControlados() ? '' : ' AND controlado = 0')
                 . ' ORDER BY validade ASC LIMIT :limite';

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':dias', self::DIAS_ALERTA_VALIDADE, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao listar reagentes em alerta: ' . $e->getMessage());
            return [];
        }
    }

    /** Últimas movimentações registradas, para acompanhamento no painel. */
    public function ultimasMovimentacoes(int $limite = 5): array
    {
        try {
            $stmt = $this->conn->prepare(
                'SELECT m.tipo_movimentacao, m.quantidade, m.data_hora, m.motivo_retirada,
                        r.nome AS reagente, f.nome AS funcionario
                 FROM movimentacoes m
                 JOIN reagentes r ON r.id = m.reagente_id
                 JOIN funcionario f ON f.cod_funcionario = m.funcionario_id
                 WHERE r.ativo = 1' . ($this->podeAcessarControlados() ? '' : ' AND r.controlado = 0') . '
                 ORDER BY m.data_hora DESC, m.id DESC
                 LIMIT :limite'
            );
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao listar últimas movimentações: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Indica se o usuário logado pode ver e movimentar produtos controlados.
     *
     * Admin e gestor têm acesso por definição de perfil; os demais dependem da
     * flag `acesso_controlados` concedida individualmente.
     */
    public function podeAcessarControlados(): bool
    {
        $tipo = $_SESSION['user_type'] ?? null;
        if ($tipo === 'admin' || $tipo === 'gestor') {
            return true;
        }

        $funcionarioId = $_SESSION['user_id'] ?? null;
        if (!$funcionarioId) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare(
                'SELECT acesso_controlados FROM funcionario WHERE cod_funcionario = :id'
            );
            $stmt->execute([':id' => $funcionarioId]);
            $dados = $stmt->fetch();
            return $dados && (int) $dados['acesso_controlados'] === 1;
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao verificar acesso a controlados: ' . $e->getMessage());
            return false;
        }
    }

    public function listar(string $busca = '', bool $apenasControlados = false, bool $apenasUtilizados = false): array
    {
        try {
            $conditions = ['ativo = 1'];
            $params = [];

            $conditions[] = $apenasUtilizados ? 'quantidade = 0' : 'quantidade > 0';

            if ($busca !== '') {
                $conditions[] = '(nome LIKE :busca OR formula_quimica LIKE :busca OR numero_cas LIKE :busca)';
                $params[':busca'] = '%' . $busca . '%';
            }

            if ($apenasControlados) {
                $conditions[] = 'controlado = 1';
            }

            if (!$this->podeAcessarControlados()) {
                $conditions[] = 'controlado = 0';
            }

            $sql = 'SELECT * FROM reagentes WHERE ' . implode(' AND ', $conditions)
                 . ' ORDER BY nome ASC, validade ASC';

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao listar reagentes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Grava a movimentação no histórico.
     *
     * Retorna false em caso de falha para que operações transacionais possam
     * abortar: um movimento de estoque sem registro de auditoria quebraria o
     * rastreamento exigido para produtos controlados.
     */
    private function registrarLog(int $reagenteId, string $tipo, int $quantidade, ?string $motivo = null): bool
    {
        $funcionarioId = $_SESSION['user_id'] ?? null;
        if (!$funcionarioId) {
            error_log('[Chemicall] Movimentação sem usuário em sessão; log não registrado.');
            return false;
        }

        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO movimentacoes (reagente_id, funcionario_id, tipo_movimentacao, quantidade, motivo_retirada)
                 VALUES (:rid, :fid, :tipo, :qtd, :motivo)'
            );
            $stmt->execute([
                ':rid'    => $reagenteId,
                ':fid'    => $funcionarioId,
                ':tipo'   => $tipo,
                ':qtd'    => $quantidade,
                ':motivo' => $motivo,
            ]);
            return true;
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao registrar movimentação: ' . $e->getMessage());
            return false;
        }
    }

    public function criar(array $dados): bool
    {
        try {
            $sql = 'INSERT INTO reagentes
                    (nome, formula_quimica, massa_molar, concentracao, densidade, validade, fabricante,
                     numero_cas, numero_ncm, numero_nota_fiscal, quantidade, quantidade_original,
                     unidade_medida, capacidade_medida, unidade_capacidade, controlado)
                    VALUES (:nome, :formula, :massa, :conc, :dens, :val, :fab, :cas, :ncm, :nf,
                            :qtd, :qtd_orig, :unidade, :capacidade, :uni_cap, :ctrl)';

            $quantidade = max(0, (int) $dados['quantidade']);

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nome'       => $dados['nome'],
                ':formula'    => $dados['formula_quimica'],
                ':massa'      => $dados['massa_molar'],
                ':conc'       => $dados['concentracao'],
                ':dens'       => $dados['densidade'],
                ':val'        => $dados['validade'],
                ':fab'        => $dados['fabricante'],
                ':cas'        => $dados['numero_cas'],
                ':ncm'        => $dados['numero_ncm'],
                ':nf'         => $dados['numero_nota_fiscal'],
                ':qtd'        => $quantidade,
                ':qtd_orig'   => $quantidade,
                ':unidade'    => $dados['unidade_medida'] ?? 'frasco',
                ':capacidade' => $dados['capacidade_medida'] ?? null,
                ':uni_cap'    => $dados['unidade_capacidade'] ?? 'ml',
                ':ctrl'       => (int) $dados['controlado'],
            ]);

            $this->registrarLog((int) $this->conn->lastInsertId(), 'criacao', $quantidade);
            return true;
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao criar reagente: ' . $e->getMessage());
            return false;
        }
    }

    public function buscarPorId($id)
    {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM reagentes WHERE id = :id');
            $stmt->execute([':id' => (int) $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao buscar reagente: ' . $e->getMessage());
            return false;
        }
    }

    public function atualizar($id, array $dados): bool
    {
        try {
            $sql = 'UPDATE reagentes SET
                        nome = :nome, formula_quimica = :formula, massa_molar = :massa,
                        concentracao = :conc, densidade = :dens, validade = :val,
                        fabricante = :fab, numero_cas = :cas,
                        numero_ncm = :ncm, numero_nota_fiscal = :nf, quantidade = :qtd,
                        unidade_medida = :unidade, capacidade_medida = :capacidade,
                        unidade_capacidade = :uni_cap, controlado = :ctrl
                    WHERE id = :id';

            $quantidade = max(0, (int) $dados['quantidade']);

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nome'       => $dados['nome'],
                ':formula'    => $dados['formula_quimica'],
                ':massa'      => $dados['massa_molar'],
                ':conc'       => $dados['concentracao'],
                ':dens'       => $dados['densidade'],
                ':val'        => $dados['validade'],
                ':fab'        => $dados['fabricante'],
                ':cas'        => $dados['numero_cas'],
                ':ncm'        => $dados['numero_ncm'],
                ':nf'         => $dados['numero_nota_fiscal'],
                ':qtd'        => $quantidade,
                ':unidade'    => $dados['unidade_medida'] ?? 'frasco',
                ':capacidade' => $dados['capacidade_medida'] ?? null,
                ':uni_cap'    => $dados['unidade_capacidade'] ?? 'ml',
                ':ctrl'       => (int) $dados['controlado'],
                ':id'         => (int) $id,
            ]);

            $this->registrarLog((int) $id, 'edicao', $quantidade);
            return true;
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao atualizar reagente: ' . $e->getMessage());
            return false;
        }
    }

    /** Exclusão lógica: o reagente é inativado e o histórico é preservado. */
    public function deletar($id): bool
    {
        $id = (int) $id;
        try {
            $this->conn->beginTransaction();

            $reagente = $this->buscarPorId($id);
            if (!$reagente) {
                $this->conn->rollBack();
                return false;
            }

            if (!$this->registrarLog($id, 'exclusao', (int) $reagente['quantidade'])) {
                $this->conn->rollBack();
                return false;
            }

            $stmt = $this->conn->prepare('UPDATE reagentes SET ativo = 0 WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('[Chemicall] Falha ao excluir reagente: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aplica uma entrada ou saída de estoque.
     *
     * A retirada nunca é silenciosamente truncada: pedir mais do que existe é
     * recusado, para que o histórico de movimentações continue refletindo a
     * quantidade realmente movimentada.
     *
     * @return array{success:bool, message:string}
     */
    public function atualizarQuantidade($id, $quantidade, string $operacao, ?string $motivo = null): array
    {
        $id = (int) $id;

        if (!in_array($operacao, ['adicionar', 'remover'], true)) {
            return ['success' => false, 'message' => 'Operação inválida.'];
        }

        // Só inteiros positivos: rejeita "abc", "1e10", "-5", "3.7".
        if (filter_var($quantidade, FILTER_VALIDATE_INT) === false || (int) $quantidade <= 0) {
            return ['success' => false, 'message' => 'Informe uma quantidade inteira maior que zero.'];
        }
        $quantidade = (int) $quantidade;

        try {
            $this->conn->beginTransaction();

            // FOR UPDATE bloqueia a linha até o commit, evitando que duas
            // retiradas simultâneas leiam o mesmo saldo.
            $stmt = $this->conn->prepare(
                'SELECT quantidade, controlado, ativo FROM reagentes WHERE id = :id FOR UPDATE'
            );
            $stmt->execute([':id' => $id]);
            $atual = $stmt->fetch();

            if (!$atual || (int) $atual['ativo'] !== 1) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Reagente não encontrado.'];
            }

            if ((int) $atual['controlado'] === 1 && !$this->podeAcessarControlados()) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Você não tem permissão para movimentar produtos controlados.'];
            }

            $estoqueAtual = (int) $atual['quantidade'];

            if ($operacao === 'remover') {
                if ($quantidade > $estoqueAtual) {
                    $this->conn->rollBack();
                    return [
                        'success' => false,
                        'message' => "Quantidade indisponível: há apenas {$estoqueAtual} em estoque.",
                    ];
                }
                $novaQuantidade = $estoqueAtual - $quantidade;
                $tipoMovimentacao = 'saida';
            } else {
                $novaQuantidade = $estoqueAtual + $quantidade;
                $tipoMovimentacao = 'entrada';
            }

            $upd = $this->conn->prepare('UPDATE reagentes SET quantidade = :qtd WHERE id = :id');
            $upd->execute([':qtd' => $novaQuantidade, ':id' => $id]);

            if (!$this->registrarLog($id, $tipoMovimentacao, $quantidade, $motivo)) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Não foi possível registrar a movimentação no histórico.'];
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Estoque atualizado com sucesso.'];
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('[Chemicall] Falha ao movimentar estoque: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Não foi possível atualizar o estoque.'];
        }
    }
}
