<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\PasswordResetToken;
use App\Models\AccountActivationToken;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Config\Constants;
use App\Config\Database;

class AuthController extends Controller
{
    private $authService;
    private $emailService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->emailService = new EmailService();
    }

    public function showLogin()
    {
        if (!empty($_SESSION['user_id'])) {
            redirect(base_url('/dashboard'));
        }
        $this->viewRaw('auth/login');
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->showLogin();
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Email e senha são obrigatórios';
            redirect(base_url('/login'));
        }

        $user = $this->authService->attempt($email, $password);

        if ($user) {
            $this->authService->login($user);
            
            // Verificar se precisa trocar senha
            if (!empty($user['must_change_password']) && $user['must_change_password'] == 1) {
                $_SESSION['warning'] = 'Por segurança, você precisa alterar sua senha no primeiro acesso.';
                redirect(base_url('/change-password'));
            }
            
            redirect(base_url('/dashboard'));
        } else {
            $_SESSION['error'] = 'Credenciais inválidas';
            redirect(base_url('/login'));
        }
    }

    public function logout()
    {
        $this->authService->logout();
        redirect(base_url('/login'));
    }

    /**
     * Tela de recuperação de senha
     */
    public function showForgotPassword()
    {
        if (!empty($_SESSION['user_id'])) {
            redirect(base_url('/dashboard'));
        }
        $this->viewRaw('auth/forgot-password');
    }

    /**
     * Processa solicitação de recuperação de senha
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->showForgotPassword();
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'E-mail inválido.';
            redirect(base_url('/forgot-password'));
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        // Sempre retornar sucesso (segurança - não revelar se email existe)
        if ($user && $user['status'] === 'ativo') {
            try {
                $tokenModel = new PasswordResetToken();
                $token = $tokenModel->createToken($user['id'], 1); // 1 hora

                if ($token) {
                    $resetUrl = base_url("/reset-password?token={$token}");
                    $this->emailService->sendPasswordReset($email, $token, $resetUrl);
                }
            } catch (\Exception $e) {
                error_log("Erro ao enviar e-mail de recuperação: " . $e->getMessage());
            }
        }

        $_SESSION['success'] = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';
        redirect(base_url('/login'));
    }

    /**
     * Tela de redefinição de senha
     */
    public function showResetPassword()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $_SESSION['error'] = 'Token inválido.';
            redirect(base_url('/forgot-password'));
        }

        $tokenModel = new PasswordResetToken();
        $tokenData = $tokenModel->findValidToken($token);

        if (!$tokenData) {
            $_SESSION['error'] = 'Token inválido ou expirado.';
            redirect(base_url('/forgot-password'));
        }

        $data = [
            'token' => $token,
            'user' => $tokenData
        ];

        $this->viewRaw('auth/reset-password', $data);
    }

    /**
     * Processa redefinição de senha
     */
    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            redirect(base_url('/forgot-password'));
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($token) || empty($password) || empty($passwordConfirm)) {
            $_SESSION['error'] = 'Preencha todos os campos.';
            redirect(base_url("/reset-password?token={$token}"));
        }

        if ($password !== $passwordConfirm) {
            $_SESSION['error'] = 'As senhas não coincidem.';
            redirect(base_url("/reset-password?token={$token}"));
        }

        // Validar política de senha (mínimo 8 caracteres)
        if (strlen($password) < 8) {
            $_SESSION['error'] = 'A senha deve ter no mínimo 8 caracteres.';
            redirect(base_url("/reset-password?token={$token}"));
        }

        $tokenModel = new PasswordResetToken();
        $tokenData = $tokenModel->findValidToken($token);

        if (!$tokenData) {
            $_SESSION['error'] = 'Token inválido ou expirado.';
            redirect(base_url('/forgot-password'));
        }

        // Atualizar senha
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $userModel = new User();
        $userModel->updatePassword($tokenData['user_id'], $hashedPassword);

        // Marcar token como usado
        $tokenModel->markAsUsed($token);

        $_SESSION['success'] = 'Senha redefinida com sucesso! Faça login com sua nova senha.';
        redirect(base_url('/login'));
    }

    /**
     * Tela de alteração de senha (usuário logado)
     */
    public function showChangePassword()
    {
        if (empty($_SESSION['user_id'])) {
            redirect(base_url('/login'));
        }

        $data = [
            'pageTitle' => 'Alterar Senha'
        ];
        $this->view('auth/change-password', $data);
    }

    /**
     * Processa alteração de senha (usuário logado)
     */
    public function changePassword()
    {
        if (empty($_SESSION['user_id'])) {
            redirect(base_url('/login'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->showChangePassword();
            return;
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('/change-password'));
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($newPasswordConfirm)) {
            $_SESSION['error'] = 'Preencha todos os campos.';
            redirect(base_url('/change-password'));
        }

        if ($newPassword !== $newPasswordConfirm) {
            $_SESSION['error'] = 'As novas senhas não coincidem.';
            redirect(base_url('/change-password'));
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = 'A senha deve ter no mínimo 8 caracteres.';
            redirect(base_url('/change-password'));
        }

        // Verificar senha atual
        $userModel = new User();
        $user = $userModel->find($_SESSION['user_id']);

        if (!password_verify($currentPassword, $user['password'])) {
            $_SESSION['error'] = 'Senha atual incorreta.';
            redirect(base_url('/change-password'));
        }

        // Atualizar senha e remover flag de troca obrigatória
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE usuarios SET password = ?, must_change_password = 0 WHERE id = ?");
        $stmt->execute([$hashedPassword, $_SESSION['user_id']]);

        $_SESSION['success'] = 'Senha alterada com sucesso!';
        
        // Se estava obrigado a trocar, redirecionar para dashboard
        if (!empty($user['must_change_password']) && $user['must_change_password'] == 1) {
            redirect(base_url('/dashboard'));
        }
        
        redirect(base_url('/change-password'));
    }

    /**
     * Mostra tela de ativação de conta
     */
    public function showActivateAccount()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $_SESSION['error'] = 'Token de ativação não fornecido.';
            redirect(base_url('/login'));
        }

        // Validar token
        $tokenHash = hash('sha256', $token);
        $tokenModel = new AccountActivationToken();
        $tokenData = $tokenModel->findByTokenHash($tokenHash);

        if (!$tokenData) {
            $_SESSION['error'] = 'Token de ativação inválido ou expirado. Solicite um novo link.';
            redirect(base_url('/login'));
        }

        // Buscar usuário
        $userModel = new User();
        $user = $userModel->find($tokenData['user_id']);

        if (!$user || $user['status'] !== 'ativo') {
            $_SESSION['error'] = 'Usuário não encontrado ou inativo.';
            redirect(base_url('/login'));
        }

        $data = [
            'token' => $token,
            'user' => $user
        ];

        $this->view('auth/activate-account', $data);
    }

    /**
     * Processa ativação de conta (definir senha)
     */
    public function activateAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->showActivateAccount();
            return;
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('/login'));
        }

        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

        if (empty($token)) {
            $_SESSION['error'] = 'Token de ativação não fornecido.';
            redirect(base_url('/login'));
        }

        if (empty($newPassword) || empty($newPasswordConfirm)) {
            $_SESSION['error'] = 'Preencha todos os campos.';
            redirect(base_url("/ativar-conta?token={$token}"));
        }

        if ($newPassword !== $newPasswordConfirm) {
            $_SESSION['error'] = 'As senhas não coincidem.';
            redirect(base_url("/ativar-conta?token={$token}"));
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = 'A senha deve ter no mínimo 8 caracteres.';
            redirect(base_url("/ativar-conta?token={$token}"));
        }

        // Validar token
        $tokenHash = hash('sha256', $token);
        $tokenModel = new AccountActivationToken();
        $tokenData = $tokenModel->findByTokenHash($tokenHash);

        if (!$tokenData) {
            $_SESSION['error'] = 'Token de ativação inválido ou expirado. Solicite um novo link.';
            redirect(base_url('/login'));
        }

        // Buscar usuário
        $userModel = new User();
        $user = $userModel->find($tokenData['user_id']);

        if (!$user || $user['status'] !== 'ativo') {
            $_SESSION['error'] = 'Usuário não encontrado ou inativo.';
            redirect(base_url('/login'));
        }

        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            // Atualizar senha e remover flag de troca obrigatória
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare("
                UPDATE usuarios 
                SET password = ?, must_change_password = 0 
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $user['id']]);

            // Marcar token como usado
            $tokenModel->markAsUsed($tokenData['id']);

            $db->commit();

            $_SESSION['success'] = 'Conta ativada com sucesso! Você já pode fazer login.';
            redirect(base_url('/login'));
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Erro ao ativar conta: ' . $e->getMessage();
            redirect(base_url("/ativar-conta?token={$token}"));
        }
    }
}
