<?php
require_once __DIR__ . '/../db/db_connection.php';

/**
 * Autenticação, autorização e proteção contra força bruta.
 */
class AuthController
{
    /** Tentativas malsucedidas toleradas dentro da janela antes do bloqueio. */
    private const MAX_TENTATIVAS = 5;

    /** Janela de contagem / duração do bloqueio, em segundos. */
    private const JANELA_SEGUNDOS = 900;

    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Valida as credenciais e cria a sessão.
     *
     * @return array{success:bool, message:string}
     */
    public function login(string $email, string $senha): array
    {
        $email = trim($email);
        $ip = $this->ip();

        $espera = $this->segundosDeBloqueio($email, $ip);
        if ($espera > 0) {
            return [
                'success' => false,
                'message' => 'Muitas tentativas de login. Tente novamente em '
                    . (int) ceil($espera / 60) . ' minuto(s).',
            ];
        }

        try {
            $stmt = $this->conn->prepare(
                'SELECT cod_funcionario, nome, senha, tipo, status
                 FROM funcionario WHERE email = :email'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            error_log('[Chemicall] Erro na consulta de login: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Não foi possível processar o login. Tente novamente.'];
        }

        // password_verify roda mesmo sem usuário encontrado, contra um hash
        // descartável, para que o tempo de resposta não revele se o e-mail existe.
        $hash = $user['senha'] ?? '$2y$10$usuarioInexistenteUsuarioInexistenteUsuarioInexistenteUsuarioIn';
        $senhaConfere = password_verify($senha, $hash);

        if (!$user || !$senhaConfere || $user['status'] !== 'ativo') {
            $this->registrarTentativa($email, $ip);

            // Conta pendente/inativa recebe uma mensagem específica somente se a
            // senha estiver correta — caso contrário nada é revelado.
            if ($user && $senhaConfere && $user['status'] === 'pendente') {
                return ['success' => false, 'message' => 'Seu cadastro ainda aguarda aprovação de um administrador.'];
            }
            return ['success' => false, 'message' => 'Email ou senha incorretos!'];
        }

        $this->limparTentativas($email, $ip);

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['cod_funcionario'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_type'] = $user['tipo'];
        unset($_SESSION['sessao_expirada']);

        // Token CSRF novo a cada login.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return ['success' => true, 'message' => 'Autenticado com sucesso.'];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin(): bool
    {
        return ($_SESSION['user_type'] ?? null) === 'admin';
    }

    public function isGestor(): bool
    {
        return ($_SESSION['user_type'] ?? null) === 'gestor';
    }

    /** Admin e gestor compartilham as permissões de manutenção do estoque. */
    public function podeGerenciarEstoque(): bool
    {
        return $this->isAdmin() || $this->isGestor();
    }

    /**
     * Exige sessão ativa; caso contrário redireciona para o login.
     *
     * @param string $loginUrl caminho relativo do login a partir da tela chamadora
     */
    public function exigirLogin(string $loginUrl = '../login/index.php'): void
    {
        if (!$this->isAuthenticated()) {
            header('Location: ' . $loginUrl);
            exit();
        }
    }

    /** Exige perfil de administrador. */
    public function exigirAdmin(string $fallbackUrl = '../dashboard/index.php'): void
    {
        $this->exigirLogin();
        if (!$this->isAdmin()) {
            header('Location: ' . $fallbackUrl);
            exit();
        }
    }

    /** Exige perfil de administrador ou gestor. */
    public function exigirGestao(string $fallbackUrl = '../dashboard/index.php'): void
    {
        $this->exigirLogin();
        if (!$this->podeGerenciarEstoque()) {
            header('Location: ' . $fallbackUrl);
            exit();
        }
    }

    // -----------------------------------------------------------------------
    // Controle de força bruta
    // -----------------------------------------------------------------------

    /** Segundos restantes de bloqueio; 0 quando liberado. */
    private function segundosDeBloqueio(string $email, string $ip): int
    {
        try {
            // O tempo restante é calculado pelo próprio MySQL. Comparar
            // NOW() do banco com time() do PHP daria resultado errado sempre
            // que os dois estivessem em fusos horários diferentes.
            $stmt = $this->conn->prepare(
                'SELECT COUNT(*) AS total,
                        TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MAX(tentado_em), INTERVAL :janela2 SECOND)) AS restante
                 FROM tentativas_login
                 WHERE (email = :email OR ip = :ip)
                   AND tentado_em > DATE_SUB(NOW(), INTERVAL :janela SECOND)'
            );
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':janela', self::JANELA_SEGUNDOS, PDO::PARAM_INT);
            $stmt->bindValue(':janela2', self::JANELA_SEGUNDOS, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
        } catch (PDOException $e) {
            // Sem a tabela de controle, o login segue funcionando sem throttle.
            error_log('[Chemicall] Throttle de login indisponível: ' . $e->getMessage());
            return 0;
        }

        if (!$row || (int) $row['total'] < self::MAX_TENTATIVAS) {
            return 0;
        }
        return max((int) $row['restante'], 0);
    }

    private function registrarTentativa(string $email, string $ip): void
    {
        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO tentativas_login (email, ip) VALUES (:email, :ip)'
            );
            $stmt->execute([':email' => $email, ':ip' => $ip]);

            // Limpeza oportunista dos registros já expirados.
            $this->conn->exec(
                'DELETE FROM tentativas_login
                 WHERE tentado_em < DATE_SUB(NOW(), INTERVAL 1 DAY)'
            );
        } catch (PDOException $e) {
            error_log('[Chemicall] Não foi possível registrar tentativa de login: ' . $e->getMessage());
        }
    }

    private function limparTentativas(string $email, string $ip): void
    {
        try {
            $stmt = $this->conn->prepare(
                'DELETE FROM tentativas_login WHERE email = :email OR ip = :ip'
            );
            $stmt->execute([':email' => $email, ':ip' => $ip]);
        } catch (PDOException $e) {
            error_log('[Chemicall] Não foi possível limpar tentativas de login: ' . $e->getMessage());
        }
    }

    private function ip(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }
}
