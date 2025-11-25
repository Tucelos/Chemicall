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

            // Verificar se login já existe
            $stmt = $this->conn->prepare("SELECT cod_funcionario FROM funcionario WHERE login_funcionario = :login");
            $stmt->execute([':login' => $dados['login']]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Login já cadastrado.'];
            }

            $sql = "INSERT INTO funcionario (nome, email, login_funcionario, senha, tipo) 
                    VALUES (:nome, :email, :login, :senha, :tipo)";
            
            $stmt = $this->conn->prepare($sql);
            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':email' => $dados['email'],
                ':login' => $dados['login'],
                ':senha' => $senhaHash,
                ':tipo' => $dados['tipo']
            ]);
            
            return ['success' => true, 'message' => 'Usuário cadastrado com sucesso!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    public function listar() {
        try {
            $stmt = $this->conn->query("SELECT * FROM funcionario ORDER BY nome ASC");
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
            $sql = "UPDATE funcionario SET nome = :nome, email = :email, login_funcionario = :login, tipo = :tipo WHERE cod_funcionario = :id";
            $params = [
                ':nome' => $dados['nome'],
                ':email' => $dados['email'],
                ':login' => $dados['login'],
                ':tipo' => $dados['tipo'],
                ':id' => $id
            ];

            if (!empty($dados['senha'])) {
                $sql = "UPDATE funcionario SET nome = :nome, email = :email, login_funcionario = :login, senha = :senha, tipo = :tipo WHERE cod_funcionario = :id";
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
}
?>
