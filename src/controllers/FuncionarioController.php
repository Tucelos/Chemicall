<?php
require_once __DIR__ . '/../db/db_connection.php';

/**
 * Cadastro e manutenção dos usuários do sistema.
 */
class FuncionarioController
{
    /** Perfis reconhecidos pelo controle de acesso. */
    public const TIPOS_VALIDOS = ['admin', 'gestor', 'user'];

    /** Situações possíveis de uma conta. */
    public const STATUS_VALIDOS = ['ativo', 'pendente', 'inativo'];

    /** Tamanho mínimo exigido para senhas. */
    public const SENHA_MINIMA = 8;

    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Valida a força da senha.
     *
     * @return string|null mensagem de erro, ou null se a senha for aceitável
     */
    public static function validarSenha(string $senha): ?string
    {
        if (strlen($senha) < self::SENHA_MINIMA) {
            return 'A senha deve ter no mínimo ' . self::SENHA_MINIMA . ' caracteres.';
        }
        if (!preg_match('/[A-Za-zÀ-ÿ]/', $senha) || !preg_match('/\d/', $senha)) {
            return 'A senha deve conter ao menos uma letra e um número.';
        }
        return null;
    }

    private function normalizarTipo(?string $tipo): string
    {
        return in_array($tipo, self::TIPOS_VALIDOS, true) ? $tipo : 'user';
    }

    private function normalizarStatus(?string $status): string
    {
        return in_array($status, self::STATUS_VALIDOS, true) ? $status : 'ativo';
    }

    /**
     * @return array{success:bool, message:string}
     */
    public function criar(array $dados): array
    {
        $email = trim((string) ($dados['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Informe um e-mail válido.'];
        }
        if (trim((string) ($dados['nome'] ?? '')) === '') {
            return ['success' => false, 'message' => 'O nome é obrigatório.'];
        }
        if ($erroSenha = self::validarSenha((string) ($dados['senha'] ?? ''))) {
            return ['success' => false, 'message' => $erroSenha];
        }

        try {
            $sql = 'INSERT INTO funcionario
                    (nome, matricula, email, email_secundario, senha, tipo, status, cargo, acesso_controlados)
                    VALUES (:nome, :matricula, :email, :email_secundario, :senha, :tipo, :status, :cargo, :acesso_controlados)';

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nome'               => trim((string) $dados['nome']),
                ':matricula'          => $dados['matricula'] ?? null,
                ':email'              => $email,
                ':email_secundario'   => $dados['email_secundario'] ?? null,
                ':senha'              => password_hash($dados['senha'], PASSWORD_DEFAULT),
                ':tipo'               => $this->normalizarTipo($dados['tipo'] ?? null),
                ':status'             => $this->normalizarStatus($dados['status'] ?? null),
                ':cargo'              => $dados['cargo'] ?? null,
                ':acesso_controlados' => !empty($dados['acesso_controlados']) ? 1 : 0,
            ]);

            return ['success' => true, 'message' => 'Usuário cadastrado com sucesso!'];
        } catch (PDOException $e) {
            // A coluna `email` é UNIQUE: a violação de chave é o caminho normal
            // para e-mail duplicado (sem a condição de corrida de um SELECT antes).
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => 'Este e-mail já está cadastrado.'];
            }
            // Detalhes do banco nunca chegam ao usuário: só ao log.
            error_log('[Chemicall] Falha ao criar usuário: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Não foi possível concluir o cadastro. Tente novamente.'];
        }
    }

    public function listar(): array
    {
        try {
            $stmt = $this->conn->query(
                "SELECT cod_funcionario, nome, matricula, email, email_secundario, tipo, status, cargo, acesso_controlados
                 FROM funcionario WHERE status = 'ativo' ORDER BY nome ASC"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao listar usuários: ' . $e->getMessage());
            return [];
        }
    }

    public function buscarPorId($id)
    {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM funcionario WHERE cod_funcionario = :id');
            $stmt->execute([':id' => (int) $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao buscar usuário: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza um usuário. A senha só é alterada quando informada.
     *
     * @return array{success:bool, message:string}
     */
    public function atualizar($id, array $dados): array
    {
        $id = (int) $id;
        $email = trim((string) ($dados['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Informe um e-mail válido.'];
        }

        $novoTipo   = $this->normalizarTipo($dados['tipo'] ?? null);
        $novoStatus = $this->normalizarStatus($dados['status'] ?? null);

        // Rebaixar ou desativar o último administrador deixaria o sistema sem
        // ninguém capaz de gerenciar usuários.
        if (($novoTipo !== 'admin' || $novoStatus !== 'ativo') && $this->ehUltimoAdmin($id)) {
            return [
                'success' => false,
                'message' => 'Esta é a única conta de administrador ativa: seu perfil e status não podem ser alterados.',
            ];
        }

        $campos = [
            'nome = :nome',
            'matricula = :matricula',
            'email = :email',
            'email_secundario = :email_secundario',
            'tipo = :tipo',
            'status = :status',
            'cargo = :cargo',
            'acesso_controlados = :acesso_controlados',
        ];

        $params = [
            ':nome'               => trim((string) $dados['nome']),
            ':matricula'          => $dados['matricula'] ?? null,
            ':email'              => $email,
            ':email_secundario'   => $dados['email_secundario'] ?? null,
            ':tipo'               => $novoTipo,
            ':status'             => $novoStatus,
            ':cargo'              => $dados['cargo'] ?? null,
            ':acesso_controlados' => !empty($dados['acesso_controlados']) ? 1 : 0,
            ':id'                 => $id,
        ];

        if (!empty($dados['senha'])) {
            if ($erroSenha = self::validarSenha((string) $dados['senha'])) {
                return ['success' => false, 'message' => $erroSenha];
            }
            $campos[] = 'senha = :senha';
            $params[':senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        }

        try {
            $sql = 'UPDATE funcionario SET ' . implode(', ', $campos) . ' WHERE cod_funcionario = :id';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return ['success' => true, 'message' => 'Usuário atualizado com sucesso!'];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => 'Este e-mail já está em uso por outro usuário.'];
            }
            error_log('[Chemicall] Falha ao atualizar usuário: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Não foi possível atualizar o usuário.'];
        }
    }

    /**
     * Exclui um usuário, impedindo a autoexclusão e a remoção do último admin.
     *
     * @param int|null $solicitanteId id do administrador que pediu a exclusão
     * @return array{success:bool, message:string}
     */
    public function deletar($id, ?int $solicitanteId = null): array
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            return ['success' => false, 'message' => 'Usuário inválido.'];
        }
        if ($solicitanteId !== null && $id === $solicitanteId) {
            return ['success' => false, 'message' => 'Você não pode excluir a própria conta.'];
        }
        if ($this->ehUltimoAdmin($id)) {
            return ['success' => false, 'message' => 'Não é possível excluir a única conta de administrador ativa.'];
        }

        try {
            $stmt = $this->conn->prepare('DELETE FROM funcionario WHERE cod_funcionario = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Usuário não encontrado.'];
            }
            return ['success' => true, 'message' => 'Usuário excluído com sucesso.'];
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao excluir usuário: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Não foi possível excluir o usuário.'];
        }
    }

    /** Indica se o id informado é o único administrador ativo do sistema. */
    private function ehUltimoAdmin(int $id): bool
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT tipo, status FROM funcionario WHERE cod_funcionario = :id"
            );
            $stmt->execute([':id' => $id]);
            $alvo = $stmt->fetch();

            if (!$alvo || $alvo['tipo'] !== 'admin' || $alvo['status'] !== 'ativo') {
                return false;
            }

            $total = (int) $this->conn->query(
                "SELECT COUNT(*) FROM funcionario WHERE tipo = 'admin' AND status = 'ativo'"
            )->fetchColumn();

            return $total <= 1;
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao verificar administradores: ' . $e->getMessage());
            // Na dúvida, protege a conta.
            return true;
        }
    }

    public function listarSolicitacoesPendentes(): array
    {
        try {
            $stmt = $this->conn->query(
                "SELECT cod_funcionario, nome, matricula, email, cargo
                 FROM funcionario WHERE status = 'pendente' ORDER BY nome ASC"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao listar solicitações: ' . $e->getMessage());
            return [];
        }
    }

    public function aprovarSolicitacao($id): bool
    {
        try {
            $stmt = $this->conn->prepare(
                "UPDATE funcionario SET status = 'ativo' WHERE cod_funcionario = :id AND status = 'pendente'"
            );
            $stmt->execute([':id' => (int) $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao aprovar solicitação: ' . $e->getMessage());
            return false;
        }
    }

    public function rejeitarSolicitacao($id): bool
    {
        try {
            $stmt = $this->conn->prepare(
                "DELETE FROM funcionario WHERE cod_funcionario = :id AND status = 'pendente'"
            );
            $stmt->execute([':id' => (int) $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao rejeitar solicitação: ' . $e->getMessage());
            return false;
        }
    }

    /** Atualiza apenas os dados que o próprio usuário pode alterar em seu perfil. */
    public function atualizarPerfil(int $id, ?string $emailSecundario, ?string $novaSenha): array
    {
        $campos = ['email_secundario = :email_secundario'];
        $params = [
            ':email_secundario' => $emailSecundario,
            ':id'               => $id,
        ];

        if ($novaSenha !== null && $novaSenha !== '') {
            if ($erroSenha = self::validarSenha($novaSenha)) {
                return ['success' => false, 'message' => $erroSenha];
            }
            $campos[] = 'senha = :senha';
            $params[':senha'] = password_hash($novaSenha, PASSWORD_DEFAULT);
        }

        try {
            $sql = 'UPDATE funcionario SET ' . implode(', ', $campos) . ' WHERE cod_funcionario = :id';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return ['success' => true, 'message' => 'Perfil atualizado com sucesso!'];
        } catch (PDOException $e) {
            error_log('[Chemicall] Falha ao atualizar perfil: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Não foi possível atualizar o perfil.'];
        }
    }
}
