<?php
/**
 * Bootstrap de segurança do Chemicall.
 *
 * Deve ser o PRIMEIRO require de qualquer ponto de entrada da aplicação, pois
 * configura a sessão, o tratamento de erros e os cabeçalhos de segurança antes
 * de qualquer saída ser gerada.
 */

if (defined('CHEMICALL_BOOTSTRAPPED')) {
    return;
}
define('CHEMICALL_BOOTSTRAPPED', true);

define('CHEMICALL_ROOT', dirname(__DIR__, 2));

// ---------------------------------------------------------------------------
// 1. Variáveis de ambiente (.env)
// ---------------------------------------------------------------------------
$envPath = CHEMICALL_ROOT . '/.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (preg_match('/^"(.*)"$/s', $value, $m) || preg_match("/^'(.*)'$/s", $value, $m)) {
            $value = $m[1];
        }
        if (!isset($_ENV[$name])) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

/** Lê uma variável de ambiente com valor padrão. */
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

define('APP_ENV', strtolower((string) env('APP_ENV', 'production')));
define('APP_DEBUG', APP_ENV !== 'production');

/** Tempo de inatividade, em segundos, antes da sessão ser encerrada. */
define('SESSION_IDLE_TIMEOUT', (int) env('SESSION_IDLE_TIMEOUT', 1800));

// ---------------------------------------------------------------------------
// 2. Tratamento de erros
//    Em produção nada é exibido ao usuário: tudo vai para o log em disco.
// ---------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

$logDir = CHEMICALL_ROOT . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0770, true);
}
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/app.log');
}

/**
 * Encerra a requisição com uma mensagem genérica, registrando o detalhe apenas
 * no log. Evita vazar caminhos do servidor e estrutura do banco.
 */
function chemicall_fail(string $contextoInterno, int $status = 500): void
{
    error_log('[Chemicall] ' . $contextoInterno);
    if (!headers_sent()) {
        http_response_code($status);
    }
    exit('Ocorreu um erro inesperado. Se o problema persistir, contate o administrador do sistema.');
}

// ---------------------------------------------------------------------------
// 3. Sessão — configurada ANTES de session_start()
// ---------------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_IDLE_TIMEOUT);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        // Em produção o cookie só trafega sobre HTTPS. Em desenvolvimento
        // (HTTP local) a flag é desligada para o login não quebrar.
        'secure'   => $https || !APP_DEBUG,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('CHEMICALL_SESSID');
    session_start();

    // Expiração por inatividade.
    if (isset($_SESSION['ultima_atividade'])
        && (time() - $_SESSION['ultima_atividade']) > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['sessao_expirada'] = true;
    }
    $_SESSION['ultima_atividade'] = time();
}

// ---------------------------------------------------------------------------
// 4. Cabeçalhos de segurança
// ---------------------------------------------------------------------------
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header_remove('X-Powered-By');

    // A aplicação usa Bootstrap/FontAwesome via CDN e Google Charts nas
    // estatísticas. 'unsafe-inline' ainda é necessário por causa dos blocos
    // <style> e handlers inline herdados das telas.
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://code.jquery.com; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
        . "font-src 'self' https://cdnjs.cloudflare.com data:; "
        . "img-src 'self' data:; "
        . "connect-src 'self' https://pubchem.ncbi.nlm.nih.gov; "
        . "form-action 'self'; frame-ancestors 'none'; base-uri 'self'"
    );

    if (!APP_DEBUG) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// ---------------------------------------------------------------------------
// 5. Helpers de saída e CSRF
// ---------------------------------------------------------------------------

/** Escapa texto para inserção segura em HTML/atributos. */
function e($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Devolve (criando se necessário) o token CSRF da sessão. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Campo oculto pronto para embutir em qualquer formulário. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Compara em tempo constante o token recebido com o da sessão. */
function csrf_valido(?string $token): bool
{
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Bloqueia a requisição se o token CSRF for inválido.
 *
 * @param string $formato 'html' redireciona/encerra com texto; 'json' devolve JSON.
 */
function csrf_exigir(string $formato = 'html'): void
{
    $token = $_POST['_csrf'] ?? $_GET['_csrf'] ?? null;
    if (csrf_valido($token)) {
        return;
    }

    error_log('[Chemicall] Falha de validação CSRF em ' . ($_SERVER['REQUEST_URI'] ?? '?'));
    if (!headers_sent()) {
        // 403 e não 419: "419 Page Expired" é convenção de framework, não um
        // código HTTP registrado, e o Apache o converte em 500 — o que faria
        // uma requisição corretamente recusada parecer falha do servidor.
        http_response_code(403);
    }
    if ($formato === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Sessão inválida ou expirada. Recarregue a página e tente novamente.',
        ]);
    } else {
        echo 'Requisição inválida (token de segurança ausente ou expirado). Volte, recarregue a página e tente novamente.';
    }
    exit();
}

/** Exige que a requisição seja POST (evita alterações de estado via GET/CSRF simples). */
function exigir_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        if (!headers_sent()) {
            http_response_code(405);
            header('Allow: POST');
        }
        exit('Método não permitido.');
    }
}
