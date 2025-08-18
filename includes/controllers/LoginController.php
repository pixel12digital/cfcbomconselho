<?php
// =====================================================
// CONTROLLER DE LOGIN - SISTEMA CFC
// VERSÃO 2.0 - ARQUITETURA REFATORADA
// =====================================================

class LoginController {
    private $authService;
    private $config;
    
    public function __construct() {
        $this->authService = new AuthService();
        $this->config = [
            'max_attempts' => MAX_LOGIN_ATTEMPTS ?? 5,
            'lockout_time' => LOGIN_TIMEOUT ?? 900,
            'audit_enabled' => AUDIT_ENABLED ?? true
        ];
    }
    
    /**
     * Processa o formulário de login
     */
    public function handleLogin($postData) {
        try {
            // Validar dados de entrada
            $validation = $this->validateInput($postData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message'],
                    'field' => $validation['field'] ?? null
                ];
            }
            
            // Verificar tentativas de login
            if ($this->isAccountLocked()) {
                return [
                    'success' => false,
                    'message' => 'Conta temporariamente bloqueada. Tente novamente em alguns minutos.',
                    'locked' => true
                ];
            }
            
            // Tentar autenticação
            $result = $this->authService->authenticate(
                $postData['email'],
                $postData['senha'],
                $postData['remember'] ?? false
            );
            
            if ($result['success']) {
                // Log de sucesso
                $this->logLoginAttempt(true);
                
                return [
                    'success' => true,
                    'message' => 'Login realizado com sucesso!',
                    'redirect' => 'admin/dashboard.php',
                    'user' => $result['user'] ?? null
                ];
            } else {
                // Log de falha
                $this->logLoginAttempt(false);
                
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Credenciais inválidas.',
                    'attempts_remaining' => $this->getRemainingAttempts()
                ];
            }
            
        } catch (Exception $e) {
            error_log('Erro no controller de login: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do sistema. Tente novamente.',
                'debug' => DEBUG_MODE ? $e->getMessage() : null
            ];
        }
    }
    
    /**
     * Valida os dados de entrada
     */
    private function validateInput($data) {
        $email = trim($data['email'] ?? '');
        $senha = $data['senha'] ?? '';
        
        if (empty($email)) {
            return [
                'valid' => false,
                'message' => 'Por favor, informe seu e-mail.',
                'field' => 'email'
            ];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => 'Por favor, informe um e-mail válido.',
                'field' => 'email'
            ];
        }
        
        if (empty($senha)) {
            return [
                'valid' => false,
                'message' => 'Por favor, informe sua senha.',
                'field' => 'senha'
            ];
        }
        
        if (strlen($senha) < 6) {
            return [
                'valid' => false,
                'message' => 'A senha deve ter pelo menos 6 caracteres.',
                'field' => 'senha'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Verifica se a conta está bloqueada
     */
    private function isAccountLocked() {
        if (!$this->config['audit_enabled']) {
            return false;
        }
        
        $clientIP = $this->getClientIP();
        $sql = "SELECT COUNT(*) as tentativas FROM logs 
                WHERE ip_address = :ip 
                AND acao = 'login_failed' 
                AND criado_em > DATE_SUB(NOW(), INTERVAL :timeout SECOND)";
        
        $result = db()->fetch($sql, [
            'ip' => $clientIP, 
            'timeout' => $this->config['lockout_time']
        ]);
        
        return $result['tentativas'] >= $this->config['max_attempts'];
    }
    
    /**
     * Obtém tentativas restantes
     */
    private function getRemainingAttempts() {
        if (!$this->config['audit_enabled']) {
            return $this->config['max_attempts'];
        }
        
        $clientIP = $this->getClientIP();
        $sql = "SELECT COUNT(*) as tentativas FROM logs 
                WHERE ip_address = :ip 
                AND acao = 'login_failed' 
                AND criado_em > DATE_SUB(NOW(), INTERVAL :timeout SECOND)";
        
        $result = db()->fetch($sql, [
            'ip' => $clientIP, 
            'timeout' => $this->config['lockout_time']
        ]);
        
        return max(0, $this->config['max_attempts'] - $result['tentativas']);
    }
    
    /**
     * Registra tentativa de login
     */
    private function logLoginAttempt($success) {
        if (!$this->config['audit_enabled']) {
            return;
        }
        
        $clientIP = $this->getClientIP();
        $acao = $success ? 'login_success' : 'login_failed';
        
        $sql = "INSERT INTO logs (ip_address, acao, user_agent, criado_em) 
                VALUES (:ip, :acao, :user_agent, NOW())";
        
        db()->execute($sql, [
            'ip' => $clientIP,
            'acao' => $acao,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    }
    
    /**
     * Obtém IP do cliente
     */
    private function getClientIP() {
        return $_SERVER['HTTP_CLIENT_IP'] ?? 
               $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
               $_SERVER['REMOTE_ADDR'] ?? 
               '0.0.0.0';
    }
    
    /**
     * Verifica se deve mostrar captcha
     */
    public function shouldShowCaptcha() {
        if (!$this->config['audit_enabled']) {
            return false;
        }
        
        $clientIP = $this->getClientIP();
        $sql = "SELECT COUNT(*) as tentativas FROM logs 
                WHERE ip_address = :ip 
                AND acao = 'login_failed' 
                AND criado_em > DATE_SUB(NOW(), INTERVAL :timeout SECOND)";
        
        $result = db()->fetch($sql, [
            'ip' => $clientIP, 
            'timeout' => $this->config['lockout_time']
        ]);
        
        return $result['tentativas'] >= ($this->config['max_attempts'] - 1);
    }
    
    /**
     * Obtém estatísticas de login
     */
    public function getLoginStats() {
        return [
            'max_attempts' => $this->config['max_attempts'],
            'lockout_time' => $this->config['lockout_time'],
            'audit_enabled' => $this->config['audit_enabled'],
            'remaining_attempts' => $this->getRemainingAttempts(),
            'is_locked' => $this->isAccountLocked()
        ];
    }
}
