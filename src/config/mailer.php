<?php
/**
 * Configuração de envio de e-mail (SMTP) a partir do .env.
 *
 * Concentra aqui tudo o que é específico de ambiente para que as telas não
 * precisem conhecer detalhes de servidor, porta ou certificado — e para que o
 * mesmo código funcione em Windows (XAMPP) e em Linux sem edição.
 */
require_once __DIR__ . '/bootstrap.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Indica se há configuração de SMTP suficiente para tentar um envio.
 *
 * Evita gastar tempo (e gerar exceção) quando o sistema foi instalado sem
 * e-mail configurado.
 */
function smtp_configurado(): bool
{
    return trim((string) env('SMTP_HOST', '')) !== ''
        && trim((string) env('SMTP_USER', '')) !== '';
}

/**
 * Localiza o pacote de certificados raiz (CA bundle) a ser usado no TLS.
 *
 * Ordem de resolução:
 *   1. `SMTP_CAFILE` do .env, quando informado;
 *   2. o que o PHP já tem configurado (`openssl.cafile` / `openssl.capath`)
 *      ou o padrão compilado do OpenSSL — caminho normal tanto no XAMPP
 *      quanto em servidores Linux;
 *   3. locais conhecidos das principais distribuições, como último recurso.
 *
 * @return string|null caminho a usar, ou null para deixar o OpenSSL decidir
 */
function smtp_ca_bundle(): ?string
{
    // 1. Definido explicitamente pelo administrador.
    $doEnv = trim((string) env('SMTP_CAFILE', ''));
    if ($doEnv !== '') {
        if (is_readable($doEnv)) {
            return $doEnv;
        }
        error_log("[Chemicall] SMTP_CAFILE aponta para um arquivo ilegível: {$doEnv}");
    }

    // 2. O PHP já sabe onde estão os certificados.
    if (trim((string) ini_get('openssl.cafile')) !== '' || trim((string) ini_get('openssl.capath')) !== '') {
        return null;
    }
    if (function_exists('openssl_get_cert_locations')) {
        $loc = openssl_get_cert_locations();
        if ((!empty($loc['default_cert_file']) && is_readable($loc['default_cert_file']))
            || (!empty($loc['default_cert_dir']) && is_dir($loc['default_cert_dir']))) {
            return null;
        }
    }

    // 3. Caminhos usuais quando nada acima está configurado.
    $candidatos = [
        '/etc/ssl/certs/ca-certificates.crt',   // Debian, Ubuntu
        '/etc/pki/tls/certs/ca-bundle.crt',     // RHEL, CentOS, Fedora
        '/etc/ssl/ca-bundle.pem',               // openSUSE
        '/etc/ssl/cert.pem',                    // Alpine, BSD, macOS
        'C:/xampp/apache/bin/curl-ca-bundle.crt',
    ];
    foreach ($candidatos as $caminho) {
        if (is_readable($caminho)) {
            return $caminho;
        }
    }

    error_log('[Chemicall] Nenhum pacote de certificados encontrado; o envio de e-mail pode falhar. '
        . 'Defina SMTP_CAFILE no .env.');
    return null;
}

/**
 * Aplica em uma instância do PHPMailer toda a configuração vinda do .env.
 *
 * A verificação do certificado do servidor permanece sempre ativa: desligá-la
 * abriria caminho para interceptação das mensagens de redefinição de senha.
 */
function configurar_smtp(PHPMailer $mail): void
{
    $mail->isSMTP();
    $mail->Host       = (string) env('SMTP_HOST', '');
    $mail->SMTPAuth   = true;
    $mail->Username   = (string) env('SMTP_USER', '');
    $mail->Password   = (string) env('SMTP_PASS', '');
    $mail->Port       = (int) env('SMTP_PORT', 465);
    $mail->CharSet    = PHPMailer::CHARSET_UTF8;
    $mail->Timeout    = (int) env('SMTP_TIMEOUT', 15);

    $seguranca = strtolower(trim((string) env('SMTP_SECURE', 'ssl')));
    if ($seguranca === 'tls' || $seguranca === 'starttls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($seguranca === 'none' || $seguranca === '') {
        // Relay interno sem criptografia: aceitável apenas dentro da rede da
        // instituição, e por escolha explícita de quem configurou.
        $mail->SMTPSecure  = '';
        $mail->SMTPAutoTLS = false;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    }

    $opcoesSsl = [
        'verify_peer'       => true,
        'verify_peer_name'  => true,
        'allow_self_signed' => false,
    ];

    // Só informa o caminho quando é preciso: sem esta chave, o OpenSSL usa a
    // configuração do php.ini ou a base de certificados do sistema.
    $caBundle = smtp_ca_bundle();
    if ($caBundle !== null) {
        $opcoesSsl[is_dir($caBundle) ? 'capath' : 'cafile'] = $caBundle;
    }

    $mail->SMTPOptions = ['ssl' => $opcoesSsl];

    $remetente = (string) env('SMTP_FROM_EMAIL', '') ?: (string) env('SMTP_USER', 'no-reply@chemicall.local');
    $nome      = (string) env('SMTP_FROM_NAME', 'Chemicall');
    $mail->setFrom($remetente, $nome);
    $mail->addReplyTo($remetente, $nome);
}
