<?php
require_once __DIR__ . '/../db/db_connection.php';

class ReagenteController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar($busca = '', $apenasControlados = false, $apenasUtilizados = false) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
            $isGestor = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'gestor';
            
            // Verificar permissão de acesso a produtos controlados
            $hasAccessControlados = false;
            if ($isAdmin || $isGestor) {
                $hasAccessControlados = true;
            } else {
                $funcionarioId = $_SESSION['user_id'] ?? null;
                if ($funcionarioId) {
                    $stmtUser = $this->conn->prepare("SELECT acesso_controlados FROM funcionario WHERE cod_funcionario = :id");
                    $stmtUser->execute([':id' => $funcionarioId]);
                    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
                    $hasAccessControlados = $userData && $userData['acesso_controlados'] == 1;
                }
            }

            $sql = "SELECT * FROM reagentes";
            $conditions = ["ativo = 1"];
            $params = [];

            if ($apenasUtilizados) {
                $conditions[] = "quantidade = 0";
            } else {
                $conditions[] = "quantidade > 0";
            }

            if (!empty($busca)) {
                $conditions[] = "(nome LIKE :busca OR formula_quimica LIKE :busca OR numero_cas LIKE :busca)";
                $params[':busca'] = "%$busca%";
            }

            if ($apenasControlados) {
                $conditions[] = "controlado = 1";
            }

            // Se o usuário não tiver permissão para controlados, esconde
            if (!$hasAccessControlados) {
                $conditions[] = "controlado = 0";
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " ORDER BY nome ASC, validade ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    private function registrarLog($reagenteId, $tipo, $quantidade, $motivo = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $funcionarioId = $_SESSION['user_id'] ?? null;
        
        if ($funcionarioId) {
            try {
                $stmt = $this->conn->prepare("INSERT INTO movimentacoes (reagente_id, funcionario_id, tipo_movimentacao, quantidade, motivo_retirada) VALUES (:rid, :fid, :tipo, :qtd, :motivo)");
                $stmt->execute([
                    ':rid' => $reagenteId,
                    ':fid' => $funcionarioId,
                    ':tipo' => $tipo,
                    ':qtd' => $quantidade,
                    ':motivo' => $motivo
                ]);
            } catch (PDOException $e) {
                // Silently fail logging to not disrupt operation, or log to file
            }
        }
    }

    public function criar($dados) {
        try {
            $sql = "INSERT INTO reagentes (nome, formula_quimica, massa_molar, concentracao, densidade, validade, fabricante, numero_cas, numero_ncm, numero_nota_fiscal, quantidade, quantidade_original, unidade_medida, capacidade_medida, unidade_capacidade, controlado) 
                    VALUES (:nome, :formula, :massa, :conc, :dens, :val, :fab, :cas, :ncm, :nf, :qtd, :qtd_orig, :unidade, :capacidade, :uni_cap, :ctrl)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':formula' => $dados['formula_quimica'],
                ':massa' => $dados['massa_molar'],
                ':conc' => $dados['concentracao'],
                ':dens' => $dados['densidade'],
                ':val' => $dados['validade'],
                ':fab' => $dados['fabricante'],
                ':cas' => $dados['numero_cas'],
                ':ncm' => $dados['numero_ncm'],
                ':nf' => $dados['numero_nota_fiscal'],
                ':qtd' => $dados['quantidade'],
                ':qtd_orig' => $dados['quantidade'],
                ':unidade' => $dados['unidade_medida'] ?? 'frasco',
                ':capacidade' => $dados['capacidade_medida'] ?? null,
                ':uni_cap' => $dados['unidade_capacidade'] ?? 'ml',
                ':ctrl' => $dados['controlado']
            ]);
            
            $id = $this->conn->lastInsertId();
            $this->registrarLog($id, 'criacao', $dados['quantidade']);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function buscarPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM reagentes WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function atualizar($id, $dados) {
        try {
            $sql = "UPDATE reagentes SET 
                    nome = :nome, formula_quimica = :formula, massa_molar = :massa, 
                    concentracao = :conc, densidade = :dens, validade = :val, 
                    fabricante = :fab, numero_cas = :cas, 
                    numero_ncm = :ncm, numero_nota_fiscal = :nf, quantidade = :qtd, 
                    unidade_medida = :unidade, capacidade_medida = :capacidade, unidade_capacidade = :uni_cap,
                    controlado = :ctrl 
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':formula' => $dados['formula_quimica'],
                ':massa' => $dados['massa_molar'],
                ':conc' => $dados['concentracao'],
                ':dens' => $dados['densidade'],
                ':val' => $dados['validade'],
                ':fab' => $dados['fabricante'],
                ':cas' => $dados['numero_cas'],
                ':ncm' => $dados['numero_ncm'],
                ':nf' => $dados['numero_nota_fiscal'],
                ':qtd' => $dados['quantidade'],
                ':unidade' => $dados['unidade_medida'] ?? 'frasco',
                ':capacidade' => $dados['capacidade_medida'] ?? null,
                ':uni_cap' => $dados['unidade_capacidade'] ?? 'ml',
                ':ctrl' => $dados['controlado'],
                ':id' => $id
            ]);
            
            $this->registrarLog($id, 'edicao', $dados['quantidade']);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deletar($id) {
        try {
            $this->conn->beginTransaction();

            // Fetch the reagent first to log the current remaining quantity at deletion time
            $reagente = $this->buscarPorId($id);
            if ($reagente) {
                // Record the deletion event as a movement log entry with type 'exclusao'
                $this->registrarLog($id, 'exclusao', $reagente['quantidade']);
            }

            // Mark the reagent as inactive (soft delete)
            $stmt = $this->conn->prepare("UPDATE reagentes SET ativo = 0 WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function atualizarQuantidade($id, $quantidade, $operacao, $motivo = null) {
        try {
            // Primeiro busca a quantidade atual e se é controlado
            $stmt = $this->conn->prepare("SELECT quantidade, controlado FROM reagentes WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $atual = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$atual) return false;

            // Se for produto controlado, verifica se o usuário logado tem permissão
            if ($atual['controlado'] == 1) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
                $hasAccessControlados = false;
                if ($isAdmin) {
                    $hasAccessControlados = true;
                } else {
                    $funcionarioId = $_SESSION['user_id'] ?? null;
                    if ($funcionarioId) {
                        $stmtUser = $this->conn->prepare("SELECT acesso_controlados FROM funcionario WHERE cod_funcionario = :id");
                        $stmtUser->execute([':id' => $funcionarioId]);
                        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
                        $hasAccessControlados = $userData && $userData['acesso_controlados'] == 1;
                    }
                }
                if (!$hasAccessControlados) {
                    return false; // Acesso negado para produtos controlados
                }
            }

            $novaQuantidade = $atual['quantidade'];
            $tipoMovimentacao = '';
            
            if ($operacao === 'adicionar') {
                $novaQuantidade += $quantidade;
                $tipoMovimentacao = 'entrada';
            } elseif ($operacao === 'remover') {
                $novaQuantidade -= $quantidade;
                if ($novaQuantidade < 0) $novaQuantidade = 0;
                $tipoMovimentacao = 'saida';
            }

            $stmt = $this->conn->prepare("UPDATE reagentes SET quantidade = :qtd WHERE id = :id");
            $stmt->execute([
                ':qtd' => $novaQuantidade,
                ':id' => $id
            ]);
            
            $this->registrarLog($id, $tipoMovimentacao, $quantidade, $motivo);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
