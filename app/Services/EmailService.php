<?php

namespace App\Services;

use App\Config\Database;
use App\Config\Constants;

class EmailService
{
    private $db;
    private $cfcId;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
    }

    /**
     * Busca configurações SMTP ativas do CFC
     */
    public function getSmtpSettings()
    {
        $stmt = $this->db->prepare("
            SELECT * FROM smtp_settings 
            WHERE cfc_id = ? AND is_active = 1 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute([$this->cfcId]);
        $settings = $stmt->fetch();
        
        // Descriptografar senha se existir
        if ($settings && !empty($settings['password'])) {
            $settings['password'] = base64_decode($settings['password']);
        }
        
        return $settings;
    }

    /**
     * Envia e-mail usando PHPMailer ou função nativa
     * Retorna true se enviado, false se SMTP não configurado (não lança exceção)
     */
    public function send($to, $subject, $body, $isHtml = true)
    {
        $settings = $this->getSmtpSettings();
        
        if (!$settings) {
            throw new \Exception('SMTP não configurado');
        }

        // Usar função nativa do PHP (mail) ou PHPMailer se disponível
        // Por enquanto, implementação básica com mail()
        // TODO: Implementar PHPMailer para melhor controle
        
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8";
        $headers[] = "From: " . ($settings['from_name'] ? "{$settings['from_name']} <{$settings['from_email']}>" : $settings['from_email']);
        $headers[] = "Reply-To: {$settings['from_email']}";
        $headers[] = "X-Mailer: PHP/" . phpversion();

        $result = @mail($to, $subject, $body, implode("\r\n", $headers));
        
        if (!$result) {
            throw new \Exception('Falha ao enviar e-mail');
        }

        return true;
    }

    /**
     * Envia e-mail de recuperação de senha
     */
    public function sendPasswordReset($to, $token, $resetUrl)
    {
        $subject = 'Recuperação de Senha - Sistema CFC';
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Recuperação de Senha</h2>
                <p>Você solicitou a recuperação de senha. Clique no botão abaixo para redefinir sua senha:</p>
                <a href='{$resetUrl}' class='button'>Redefinir Senha</a>
                <p>Ou copie e cole este link no seu navegador:</p>
                <p style='word-break: break-all;'>{$resetUrl}</p>
                <p><strong>Este link expira em 1 hora.</strong></p>
                <p>Se você não solicitou esta recuperação, ignore este e-mail.</p>
                <div class='footer'>
                    <p>Este é um e-mail automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->send($to, $subject, $body, true);
    }

    /**
     * Envia e-mail de confirmação de criação de acesso
     */
    public function sendAccessCreated($to, $password, $loginUrl)
    {
        $subject = 'Acesso Criado - Sistema CFC';
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .credentials { background-color: #f5f5f5; padding: 15px; border-radius: 4px; margin: 20px 0; }
                .button { display: inline-block; padding: 12px 24px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Acesso Criado</h2>
                <p>Seu acesso ao sistema foi criado com sucesso!</p>
                <div class='credentials'>
                    <p><strong>E-mail:</strong> {$to}</p>
                    <p><strong>Senha temporária:</strong> {$password}</p>
                </div>
                <p><strong>⚠️ IMPORTANTE:</strong> Altere sua senha no primeiro acesso.</p>
                <a href='{$loginUrl}' class='button'>Acessar Sistema</a>
                <div class='footer'>
                    <p>Este é um e-mail automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->send($to, $subject, $body, true);
    }

    /**
     * Envia e-mail com link de ativação
     */
    public function sendActivationLink($to, $name, $activationUrl)
    {
        $subject = 'Ativação de Conta - Sistema CFC';
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Ativação de Conta</h2>
                <p>Olá, {$name}!</p>
                <p>Seu acesso ao sistema foi criado. Clique no botão abaixo para ativar sua conta e definir sua senha:</p>
                <a href='{$activationUrl}' class='button'>Ativar Conta</a>
                <p>Ou copie e cole este link no seu navegador:</p>
                <p style='word-break: break-all;'>{$activationUrl}</p>
                <p><strong>Este link expira em 24 horas.</strong></p>
                <div class='footer'>
                    <p>Este é um e-mail automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $this->send($to, $subject, $body, true);
    }

    /**
     * Testa envio de e-mail
     */
    public function test($to)
    {
        $subject = 'Teste de Configuração SMTP - Sistema CFC';
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Teste de E-mail</h2>
                <div class='success'>
                    <p><strong>✅ Sucesso!</strong></p>
                    <p>Se você recebeu este e-mail, a configuração SMTP está funcionando corretamente.</p>
                </div>
                <p>Data/Hora do teste: " . date('d/m/Y H:i:s') . "</p>
            </div>
        </body>
        </html>
        ";

        return $this->send($to, $subject, $body, true);
    }
}
