<?php
session_start();
require_once __DIR__ . '/../db/db_connection.php';

class AuthController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $senha) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM funcionario WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['cod_funcionario'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_type'] = $user['tipo'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function logout() {
        session_destroy();
    }

    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
}
?>
