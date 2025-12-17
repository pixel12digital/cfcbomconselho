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
        if (class_exists('SMTPConfigService')) {
            require_once __DIR__ . '/SMTPConfigService.php';
            $dbConfig = SMTPConfigService::getConfig();
            if ($dbConfig) {
                return $dbConfig;
            }
        }
        
        // Fallback para config.php
        $host = defined('SMTP_HOST') ? SMTP_HOST : '';
        $port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $user = defined('SMTP_USER') ? SMTP_USER : '';
        $pass = defined('SMTP_PASS') ? SMTP_PASS : '';
        $encryption = 'tls'; // Padrão
        
        // Verificar se não são placeholders
        if (empty($host) || empty($user) || empty($pass)) {
            return null;
        }
        
        if (strpos($user, 'seu_email@seudominio.com') !== false) {
            return null;
        }
        
        if (strpos($pass, 'sua_senha_smtp') !== false) {
            return null;
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
        return $config !== null;
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
            
            if ($result['success']) {
                if (LOG_ENABLED) {
                    error_log('[MAILER] Email de recuperação enviado com sucesso para: ' . $to);
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[MAILER] Erro ao enviar email: ' . $e->getMessage());
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao enviar email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar email via SMTP usando mail() nativo do PHP
     * 
     * Nota: Para produção, considere usar PHPMailer ou similar
     * Por enquanto, usa mail() nativo como implementação mínima
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
            
            // Configurar headers
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
            $headers[] = 'Reply-To: ' . (defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : $fromEmail);
            $headers[] = 'X-Mailer: PHP/' . phpversion();
            
            $headersString = implode("\r\n", $headers);
            
            // Tentar enviar via mail() nativo
            // Nota: Para produção, considere usar PHPMailer com autenticação SMTP real
            $sent = @mail($to, $subject, $htmlBody, $headersString);
            
            if ($sent) {
                if (LOG_ENABLED) {
                    error_log(sprintf('[MAILER] Email enviado via SMTP - From: %s, To: %s', $fromEmail, $to));
                }
                return [
                    'success' => true,
                    'message' => 'Email enviado com sucesso'
                ];
            } else {
                if (LOG_ENABLED) {
                    error_log('[MAILER] Falha ao enviar email - função mail() retornou false');
                }
                return [
                    'success' => false,
                    'message' => 'Falha ao enviar email (função mail() retornou false). Verifique configurações SMTP do servidor.'
                ];
            }
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[MAILER] Erro ao enviar email: ' . $e->getMessage());
            }
            return [
                'success' => false,
                'message' => 'Erro ao enviar email: ' . $e->getMessage()
            ];
        }
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
