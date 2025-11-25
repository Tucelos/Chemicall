<?php
require_once __DIR__ . '/../db/db_connection.php';

class ReagenteController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar($busca = '') {
        try {
            $sql = "SELECT * FROM reagentes";
            if (!empty($busca)) {
                $sql .= " WHERE nome LIKE :busca OR formula_quimica LIKE :busca OR numero_cas LIKE :busca";
            }
            $sql .= " ORDER BY nome ASC";
            
            $stmt = $this->conn->prepare($sql);
            if (!empty($busca)) {
                $busca = "%$busca%";
                $stmt->bindParam(':busca', $busca);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    private function registrarLog($reagenteId, $tipo, $quantidade) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $funcionarioId = $_SESSION['user_id'] ?? null;
        
        if ($funcionarioId) {
            try {
                $stmt = $this->conn->prepare("INSERT INTO movimentacoes (reagente_id, funcionario_id, tipo_movimentacao, quantidade) VALUES (:rid, :fid, :tipo, :qtd)");
                $stmt->execute([
                    ':rid' => $reagenteId,
                    ':fid' => $funcionarioId,
                    ':tipo' => $tipo,
                    ':qtd' => $quantidade
                ]);
            } catch (PDOException $e) {
                // Silently fail logging to not disrupt operation, or log to file
            }
        }
    }

    public function criar($dados) {
        try {
            $sql = "INSERT INTO reagentes (nome, formula_quimica, massa_molar, concentracao, densidade, validade, fabricante, numero_cas, numero_ncm, numero_nota_fiscal, quantidade) 
                    VALUES (:nome, :formula, :massa, :conc, :dens, :val, :fab, :cas, :ncm, :nf, :qtd)";
            
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
                ':qtd' => $dados['quantidade']
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
                    numero_ncm = :ncm, numero_nota_fiscal = :nf, quantidade = :qtd 
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $dados['id'] = $id;
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

            // Delete related logs first
            $stmtLogs = $this->conn->prepare("DELETE FROM movimentacoes WHERE reagente_id = :id");
            $stmtLogs->bindParam(':id', $id);
            $stmtLogs->execute();

            // Then delete the reagent
            $stmt = $this->conn->prepare("DELETE FROM reagentes WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function atualizarQuantidade($id, $quantidade, $operacao) {
        try {
            // Primeiro busca a quantidade atual
            $stmt = $this->conn->prepare("SELECT quantidade FROM reagentes WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $atual = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$atual) return false;

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
            
            $this->registrarLog($id, $tipoMovimentacao, $quantidade);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
