<?php
/**
 * Sistema de Envio de Email - Sistema CFC
 * 
 * Gerencia envio de emails via SMTP com fallback seguro
 */

class Mailer {
    
    /**
     * Obter configurações SMTP (do banco primeiro, fallback para config.php)
     * 
     * @return array|null ['host', 'port', 'user', 'pass', 'encryption_mode', 'from_name', 'from_email']
     */
    private static function getSMTPConfig() {
        // Tentar obter do banco primeiro
        // Sempre tentar carregar a classe (não depender de autoload)
        if (!class_exists('SMTPConfigService')) {
            $smtpServicePath = __DIR__ . '/SMTPConfigService.php';
            if (file_exists($smtpServicePath)) {
                require_once $smtpServicePath;
            }
        }
        
        // Se a classe existe agora, tentar obter configurações do banco
        if (class_exists('SMTPConfigService')) {
            try {
                $dbConfig = SMTPConfigService::getConfig();
                if ($dbConfig) {
                    // Verificar se todos os campos obrigatórios estão presentes
                    if (!empty($dbConfig['host']) && !empty($dbConfig['user']) && !empty($dbConfig['pass'])) {
                        if (LOG_ENABLED) {
                            error_log('[MAILER] Usando configurações SMTP do banco de dados');
                        }
                        return $dbConfig;
                    } else {
                        // Config retornado mas campos obrigatórios faltando
                        if (LOG_ENABLED) {
                            error_log(sprintf(
                                '[MAILER] Configurações do banco incompletas - host: %s, user: %s, pass: %s',
                                !empty($dbConfig['host']) ? 'OK' : 'VAZIO',
                                !empty($dbConfig['user']) ? 'OK' : 'VAZIO',
                                !empty($dbConfig['pass']) ? 'OK' : 'VAZIO'
                            ));
                        }
                    }
                } else {
                    // getConfig() retornou null
                    if (LOG_ENABLED) {
                        error_log('[MAILER] SMTPConfigService::getConfig() retornou null - nenhuma configuração ativa no banco');
                    }
                }
            } catch (Throwable $e) {
                // Se houver erro ao obter do banco, logar e continuar para fallback
                if (LOG_ENABLED) {
                    error_log(sprintf(
                        '[MAILER] Erro ao obter SMTP do banco - Erro: %s, Arquivo: %s:%d',
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    ));
                }
            }
        } else {
            // Classe não existe mesmo após tentar carregar
            if (LOG_ENABLED) {
                error_log('[MAILER] SMTPConfigService não encontrada - arquivo pode não existir ou erro ao carregar');
            }
        }
        
        // Fallback para config.php
        if (LOG_ENABLED) {
            error_log('[MAILER] Tentando fallback para config.php (banco não retornou configurações)');
        }
        
        $host = defined('SMTP_HOST') ? SMTP_HOST : '';
        $port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $user = defined('SMTP_USER') ? SMTP_USER : '';
        $pass = defined('SMTP_PASS') ? SMTP_PASS : '';
        $encryption = 'tls'; // Padrão
        
        // Verificar se não são placeholders
        if (empty($host) || empty($user) || empty($pass)) {
            if (LOG_ENABLED) {
                error_log('[MAILER] Fallback config.php falhou - campos vazios ou não definidos');
            }
            return null;
        }
        
        if (strpos($user, 'seu_email@seudominio.com') !== false) {
            if (LOG_ENABLED) {
                error_log('[MAILER] Fallback config.php falhou - placeholder detectado no user');
            }
            return null;
        }
        
        if (strpos($pass, 'sua_senha_smtp') !== false) {
            if (LOG_ENABLED) {
                error_log('[MAILER] Fallback config.php falhou - placeholder detectado no pass');
            }
            return null;
        }
        
        if (LOG_ENABLED) {
            error_log('[MAILER] Usando configurações SMTP do config.php (fallback)');
        }
        
        return [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'pass' => $pass,
            'encryption_mode' => $encryption,
            'from_name' => null,
            'from_email' => null
        ];
    }
    
    /**
     * Verificar se SMTP está configurado
     * 
     * @return bool
     */
    public static function isConfigured() {
        $config = self::getSMTPConfig();
        $isConfigured = $config !== null;
        
        // Log detalhado para diagnóstico
        if (LOG_ENABLED) {
            if ($isConfigured) {
                error_log(sprintf(
                    '[MAILER] SMTP configurado - Host: %s, User: %s, Source: %s',
                    $config['host'] ?? 'N/A',
                    $config['user'] ?? 'N/A',
                    isset($config['from_name']) ? 'banco' : 'config.php'
                ));
            } else {
                error_log('[MAILER] SMTP NÃO configurado - getSMTPConfig() retornou null');
            }
        }
        
        return $isConfigured;
    }
    
    /**
     * Enviar email de recuperação de senha
     * 
     * @param string $to Email do destinatário
     * @param string $token Token de recuperação (em texto puro)
     * @param string $type Tipo de usuário (para personalização)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendPasswordResetEmail($to, $token, $type) {
        // Verificar se SMTP está configurado
        if (!self::isConfigured()) {
            if (LOG_ENABLED) {
                error_log('[MAILER] SMTP não configurado. Email não enviado para: ' . $to);
            }
            
            // Retornar sucesso mesmo assim (não quebrar fluxo)
            // Mas logar que SMTP está ausente
            return [
                'success' => false,
                'message' => 'Email não configurado',
                'smtp_configured' => false
            ];
        }
        
        try {
            $baseUrl = defined('APP_URL') ? APP_URL : '';
            if (empty($baseUrl)) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
                $baseUrl = $protocol . '://' . $host . ($scriptDir !== '/' ? $scriptDir : '');
            }
            
            // URL de reset
            $resetUrl = rtrim($baseUrl, '/') . '/reset-password.php?token=' . urlencode($token);
            
            // Assunto
            $subject = 'Recuperação de Senha - CFC Bom Conselho';
            
            // Corpo do email (HTML e texto)
            $htmlBody = self::getResetEmailTemplate($resetUrl, $type, true);
            $textBody = self::getResetEmailTemplate($resetUrl, $type, false);
            
            // Enviar via SMTP
            $result = self::sendSMTP($to, $subject, $htmlBody, $textBody);
            
            // Log detalhado do resultado
            if (LOG_ENABLED) {
                if ($result['success']) {
                    error_log(sprintf(
                        '[MAILER] Email de recuperação enviado com sucesso - To: %s, Type: %s, URL: %s',
                        $to,
                        $type,
                        $resetUrl
                    ));
                } else {
                    error_log(sprintf(
                        '[MAILER] Email de recuperação FALHOU - To: %s, Type: %s, Erro: %s',
                        $to,
                        $type,
                        $result['message'] ?? 'Erro desconhecido'
                    ));
                }
            }
            
            return $result;
            
        } catch (Throwable $e) {
            // Captura Exception e Error (PHP 7+)
            if (LOG_ENABLED) {
                error_log(sprintf(
                    '[MAILER] Exceção ao enviar email de recuperação - To: %s, Type: %s, Erro: %s, Arquivo: %s:%d',
                    $to,
                    $type,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar email via SMTP com autenticação real
     * 
     * Tenta usar PHPMailer se disponível, caso contrário usa socket nativo do PHP
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto
     * @param string $htmlBody Corpo HTML
     * @param string $textBody Corpo texto
     * @return array ['success' => bool, 'message' => string]
     */
    private static function sendSMTP($to, $subject, $htmlBody, $textBody) {
        try {
            // Obter configurações SMTP (banco primeiro, fallback config.php)
            $config = self::getSMTPConfig();
            if (!$config) {
                return [
                    'success' => false,
                    'message' => 'Configurações SMTP não encontradas'
                ];
            }
            
            // Determinar remetente
            $fromEmail = $config['from_email'] ?? $config['user'];
            $fromName = $config['from_name'] ?? 'CFC Bom Conselho';
            
            // Tentar usar PHPMailer primeiro (se disponível)
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') || class_exists('PHPMailer')) {
                return self::sendViaPHPMailer($to, $subject, $htmlBody, $textBody, $config, $fromEmail, $fromName);
            }
            
            // Fallback: usar socket nativo para SMTP real
            return self::sendViaSocket($to, $subject, $htmlBody, $textBody, $config, $fromEmail, $fromName);
            
        } catch (Throwable $e) {
            // Captura Exception e Error (PHP 7+)
            if (LOG_ENABLED) {
                error_log(sprintf(
                    '[MAILER] Erro ao enviar email via SMTP - To: %s, Erro: %s, Arquivo: %s:%d',
                    $to,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));
            }
            return [
                'success' => false,
                'message' => 'Erro ao enviar email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar via PHPMailer (se disponível)
     */
    private static function sendViaPHPMailer($to, $subject, $htmlBody, $textBody, $config, $fromEmail, $fromName) {
        try {
            // Detectar namespace do PHPMailer
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                $mailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
                $smtpClass = 'PHPMailer\\PHPMailer\\SMTP';
            } else {
                $mailerClass = 'PHPMailer';
                $smtpClass = 'SMTP';
            }
            
            $mail = new $mailerClass(true);
            
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['user'];
            $mail->Password = $config['pass'];
            $mail->Port = $config['port'];
            $mail->CharSet = 'UTF-8';
            
            // Configurar criptografia
            if ($config['encryption_mode'] === 'ssl') {
                $mail->SMTPSecure = $smtpClass::ENCRYPTION_SMTPS;
            } elseif ($config['encryption_mode'] === 'tls') {
                $mail->SMTPSecure = $smtpClass::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
            }
            
            // Remetente e destinatário
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($fromEmail, $fromName);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;
            
            // Enviar
            $mail->send();
            
            if (LOG_ENABLED) {
                error_log(sprintf('[MAILER] Email enviado via PHPMailer SMTP - From: %s, To: %s', $fromEmail, $to));
            }
            
            return [
                'success' => true,
                'message' => 'Email enviado com sucesso'
            ];
            
        } catch (Throwable $e) {
            // Captura Exception e Error (PHP 7+)
            if (LOG_ENABLED) {
                error_log(sprintf(
                    '[MAILER] Erro PHPMailer - To: %s, Erro: %s, Arquivo: %s:%d',
                    $to,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));
            }
            throw $e; // Deixar o catch principal tratar
        }
    }
    
    /**
     * Enviar via socket nativo (SMTP real sem PHPMailer)
     */
    private static function sendViaSocket($to, $subject, $htmlBody, $textBody, $config, $fromEmail, $fromName) {
        try {
            $host = $config['host'];
            $port = $config['port'];
            $user = $config['user'];
            $pass = $config['pass'];
            $encryption = $config['encryption_mode'] ?? 'tls';
            
            // Determinar contexto de conexão (TLS/SSL)
            $context = null;
            $transport = 'tcp';
            $targetHost = $host;
            
            if ($encryption === 'ssl') {
                $transport = 'ssl';
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]);
            }
            
            // Conectar ao servidor SMTP
            $socket = @stream_socket_client(
                "$transport://$targetHost:$port",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$socket) {
                throw new Exception("Falha ao conectar ao servidor SMTP: $errstr ($errno)");
            }
            
            // Ler resposta inicial
            self::readSMTPResponse($socket);
            
            // EHLO
            fwrite($socket, "EHLO $host\r\n");
            $ehloResponse = self::readSMTPResponse($socket);
            
            // STARTTLS se necessário
            if ($encryption === 'tls' && strpos($ehloResponse, '250-STARTTLS') !== false) {
                fwrite($socket, "STARTTLS\r\n");
                self::readSMTPResponse($socket);
                
                // Ativar criptografia
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // EHLO novamente após TLS
                fwrite($socket, "EHLO $host\r\n");
                self::readSMTPResponse($socket);
            }
            
            // Autenticação
            fwrite($socket, "AUTH LOGIN\r\n");
            self::readSMTPResponse($socket);
            
            fwrite($socket, base64_encode($user) . "\r\n");
            self::readSMTPResponse($socket);
            
            fwrite($socket, base64_encode($pass) . "\r\n");
            $authResponse = self::readSMTPResponse($socket);
            
            if (strpos($authResponse, '235') === false) {
                throw new Exception('Falha na autenticação SMTP');
            }
            
            // MAIL FROM
            fwrite($socket, "MAIL FROM: <$fromEmail>\r\n");
            self::readSMTPResponse($socket);
            
            // RCPT TO
            fwrite($socket, "RCPT TO: <$to>\r\n");
            self::readSMTPResponse($socket);
            
            // DATA
            fwrite($socket, "DATA\r\n");
            self::readSMTPResponse($socket);
            
            // Headers e corpo
            $message = "From: $fromName <$fromEmail>\r\n";
            $message .= "To: <$to>\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "\r\n";
            $message .= chunk_split(base64_encode($htmlBody));
            $message .= "\r\n.\r\n";
            
            fwrite($socket, $message);
            $dataResponse = self::readSMTPResponse($socket);
            
            if (strpos($dataResponse, '250') === false) {
                throw new Exception('Falha ao enviar mensagem: ' . $dataResponse);
            }
            
            // QUIT
            fwrite($socket, "QUIT\r\n");
            self::readSMTPResponse($socket);
            
            fclose($socket);
            
            if (LOG_ENABLED) {
                error_log(sprintf('[MAILER] Email enviado via socket SMTP - From: %s, To: %s', $fromEmail, $to));
            }
            
            return [
                'success' => true,
                'message' => 'Email enviado com sucesso'
            ];
            
        } catch (Throwable $e) {
            // Captura Exception e Error (PHP 7+)
            if (LOG_ENABLED) {
                error_log(sprintf(
                    '[MAILER] Erro socket SMTP - To: %s, Erro: %s, Arquivo: %s:%d',
                    $to,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));
            }
            throw $e;
        }
    }
    
    /**
     * Ler resposta do servidor SMTP
     */
    private static function readSMTPResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }
    
    /**
     * Template do email de recuperação
     * 
     * @param string $resetUrl URL completa de reset
     * @param string $type Tipo de usuário
     * @param bool $html Se deve retornar HTML ou texto
     * @return string
     */
    private static function getResetEmailTemplate($resetUrl, $type, $html = true) {
        if ($html) {
            return "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: #1A365D; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
        <h1 style='margin: 0; font-size: 24px;'>CFC Bom Conselho</h1>
        <p style='margin: 5px 0 0 0; font-size: 14px;'>Recuperação de Senha</p>
    </div>
    
    <div style='background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;'>
        <p>Olá,</p>
        
        <p>Recebemos uma solicitação para redefinir sua senha de acesso ao sistema.</p>
        
        <p>Clique no botão abaixo para criar uma nova senha:</p>
        
        <div style='text-align: center; margin: 30px 0;'>
            <a href='" . htmlspecialchars($resetUrl) . "' 
               style='display: inline-block; background: #1A365D; color: white; padding: 12px 30px; 
                      text-decoration: none; border-radius: 5px; font-weight: bold;'>
                Redefinir Senha
            </a>
        </div>
        
        <p style='font-size: 12px; color: #666;'>
            Ou copie e cole este link no seu navegador:<br>
            <a href='" . htmlspecialchars($resetUrl) . "' style='color: #1A365D; word-break: break-all;'>" . htmlspecialchars($resetUrl) . "</a>
        </p>
        
        <p style='color: #d63031; font-weight: bold;'>
            ⚠️ Este link é válido por apenas 30 minutos.
        </p>
        
        <p style='font-size: 12px; color: #666; margin-top: 30px;'>
            Se você não solicitou esta recuperação de senha, ignore este email.
        </p>
        
        <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
        
        <p style='font-size: 11px; color: #999; text-align: center;'>
            CFC Bom Conselho - Centro de Formação de Condutores<br>
            Este é um email automático, por favor não responda.
        </p>
    </div>
</body>
</html>
            ";
        } else {
            return "
CFC Bom Conselho - Recuperação de Senha

Olá,

Recebemos uma solicitação para redefinir sua senha de acesso ao sistema.

Clique no link abaixo para criar uma nova senha:

" . $resetUrl . "

⚠️ Este link é válido por apenas 30 minutos.

Se você não solicitou esta recuperação de senha, ignore este email.

---
CFC Bom Conselho - Centro de Formação de Condutores
Este é um email automático, por favor não responda.
            ";
        }
    }
}
