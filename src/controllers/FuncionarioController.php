<?php
require_once __DIR__ . '/../db/db_connection.php';

class FuncionarioController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function criar($dados) {
        try {
            // Verificar se email já existe
            $stmt = $this->conn->prepare("SELECT cod_funcionario FROM funcionario WHERE email = :email");
            $stmt->execute([':email' => $dados['email']]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email já cadastrado.'];
            }

            $sql = "INSERT INTO funcionario (nome, matricula, email, email_secundario, senha, tipo, status, cargo, acesso_controlados) 
                    VALUES (:nome, :matricula, :email, :email_secundario, :senha, :tipo, :status, :cargo, :acesso_controlados)";
            
            $stmt = $this->conn->prepare($sql);
            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':matricula' => $dados['matricula'] ?? null,
                ':email' => $dados['email'],
                ':email_secundario' => $dados['email_secundario'] ?? null,
                ':senha' => $senhaHash,
                ':tipo' => $dados['tipo'] ?? 'user',
                ':status' => $dados['status'] ?? 'ativo',
                ':cargo' => $dados['cargo'] ?? null,
                ':acesso_controlados' => $dados['acesso_controlados'] ?? 0
            ]);
            
            return ['success' => true, 'message' => 'Usuário cadastrado com sucesso!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    public function listar() {
        try {
            // Listar apenas usuários ativos na listagem normal
            $stmt = $this->conn->query("SELECT * FROM funcionario WHERE status = 'ativo' ORDER BY nome ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorId($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM funcionario WHERE cod_funcionario = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function atualizar($id, $dados) {
        try {
            $sql = "UPDATE funcionario SET 
                        nome = :nome, 
                        matricula = :matricula, 
                        email = :email, 
                        email_secundario = :email_secundario, 
                        tipo = :tipo, 
                        status = :status,
                        cargo = :cargo,
                        acesso_controlados = :acesso_controlados 
                    WHERE cod_funcionario = :id";
            $params = [
                ':nome' => $dados['nome'],
                ':matricula' => $dados['matricula'] ?? null,
                ':email' => $dados['email'],
                ':email_secundario' => $dados['email_secundario'] ?? null,
                ':tipo' => $dados['tipo'],
                ':status' => $dados['status'] ?? 'ativo',
                ':cargo' => $dados['cargo'] ?? null,
                ':acesso_controlados' => $dados['acesso_controlados'] ?? 0,
                ':id' => $id
            ];

            if (!empty($dados['senha'])) {
                $sql = "UPDATE funcionario SET 
                            nome = :nome, 
                            matricula = :matricula, 
                            email = :email, 
                            email_secundario = :email_secundario, 
                            senha = :senha, 
                            tipo = :tipo, 
                            status = :status,
                            cargo = :cargo,
                            acesso_controlados = :acesso_controlados 
                        WHERE cod_funcionario = :id";
                $params[':senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
            }

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deletar($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM funcionario WHERE cod_funcionario = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listarSolicitacoesPendentes() {
        try {
            $stmt = $this->conn->query("SELECT * FROM funcionario WHERE status = 'pendente' ORDER BY nome ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function aprovarSolicitacao($id) {
        try {
            $stmt = $this->conn->prepare("UPDATE funcionario SET status = 'ativo' WHERE cod_funcionario = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function rejeitarSolicitacao($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM funcionario WHERE cod_funcionario = :id AND status = 'pendente'");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
