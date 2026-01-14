<?php

namespace App\Controllers;

use App\Models\Setting;
use App\Services\PermissionService;
use App\Services\EmailService;
use App\Config\Constants;

class ConfiguracoesController extends Controller
{
    private $cfcId;

    public function __construct()
    {
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        
        // Apenas ADMIN pode acessar configurações
        if ($_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para acessar este módulo.';
            redirect(base_url('dashboard'));
        }
    }

    /**
     * Tela de configurações SMTP
     */
    public function smtp()
    {
        $settingModel = new Setting();
        $settings = $settingModel->findByCfc($this->cfcId);

        $data = [
            'pageTitle' => 'Configurações SMTP',
            'settings' => $settings
        ];

        $this->view('configuracoes/smtp', $data);
    }

    /**
     * Salva configurações SMTP
     */
    public function salvarSmtp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('configuracoes/smtp'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('configuracoes/smtp'));
        }

        $host = trim($_POST['host'] ?? '');
        $port = (int)($_POST['port'] ?? 587);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $encryption = $_POST['encryption'] ?? 'tls';
        $fromEmail = trim($_POST['from_email'] ?? '');
        $fromName = trim($_POST['from_name'] ?? '');

        // Validações
        if (empty($host) || empty($username) || empty($fromEmail)) {
            $_SESSION['error'] = 'Preencha todos os campos obrigatórios.';
            redirect(base_url('configuracoes/smtp'));
        }

        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'E-mail remetente inválido.';
            redirect(base_url('configuracoes/smtp'));
        }

        if (!in_array($encryption, ['tls', 'ssl', 'none'])) {
            $encryption = 'tls';
        }

        // Se senha não foi informada e já existe configuração, manter a atual
        if (empty($password) && $settings) {
            $encryptedPassword = $settings['password']; // Já está criptografada
        } else {
            // Criptografar senha (usar base64 simples por enquanto, ideal seria usar openssl)
            $encryptedPassword = base64_encode($password);
        }

        $settingModel = new Setting();
        $data = [
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $encryptedPassword,
            'encryption' => $encryption,
            'from_email' => $fromEmail,
            'from_name' => $fromName
        ];

        if ($settingModel->save($this->cfcId, $data)) {
            $_SESSION['success'] = 'Configurações SMTP salvas com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao salvar configurações.';
        }

        redirect(base_url('configuracoes/smtp'));
    }

    /**
     * Testa envio de e-mail
     */
    public function testarSmtp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('configuracoes/smtp'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('configuracoes/smtp'));
        }

        $testEmail = trim($_POST['test_email'] ?? '');

        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'E-mail de teste inválido.';
            redirect(base_url('configuracoes/smtp'));
        }

        try {
            $emailService = new EmailService();
            $emailService->test($testEmail);
            $_SESSION['success'] = 'E-mail de teste enviado com sucesso! Verifique a caixa de entrada.';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Erro ao enviar e-mail de teste: ' . $e->getMessage();
        }

        redirect(base_url('configuracoes/smtp'));
    }
}
