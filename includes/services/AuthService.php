<?php
// =====================================================
// SERVICE DE AUTENTICAÇÃO - SISTEMA CFC
// VERSÃO 2.0 - ARQUITETURA REFATORADA
// =====================================================

class AuthService {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = db();
        $this->config = [
            'session_timeout' => SESSION_TIMEOUT ?? 3600,
            'jwt_secret' => JWT_SECRET ?? 'default_secret',
            'password_min_length' => PASSWORD_MIN_LENGTH ?? 8
        ];
    }
    
    /**
     * Autentica um usuário
     */
    public function authenticate($email, $senha, $remember = false) {
        try {
            // Buscar usuário por email
            $user = $this->getUserByEmail($email);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'E-mail ou senha incorretos.',
                    'code' => 'INVALID_CREDENTIALS'
                ];
            }
            
            // Verificar se a conta está ativa
            if (!$this->isAccountActive($user)) {
                return [
                    'success' => false,
                    'message' => 'Conta desativada. Entre em contato com o suporte.',
                    'code' => 'ACCOUNT_DISABLED'
                ];
            }
            
            // Verificar senha
            if (!$this->verifyPassword($senha, $user['senha'])) {
                return [
                    'success' => false,
                    'message' => 'E-mail ou senha incorretos.',
                    'code' => 'INVALID_CREDENTIALS'
                ];
            }
            
            // Verificar se a senha expirou
            if ($this->isPasswordExpired($user)) {
                return [
                    'success' => false,
                    'message' => 'Sua senha expirou. Solicite uma nova senha.',
                    'code' => 'PASSWORD_EXPIRED'
                ];
            }
            
            // Criar sessão
            $session = $this->createSession($user, $remember);
            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Erro ao criar sessão. Tente novamente.',
                    'code' => 'SESSION_ERROR'
                ];
            }
            
            // Registrar login bem-sucedido
            $this->logSuccessfulLogin($user['id']);
            
            // Retornar sucesso
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'user' => $this->sanitizeUserData($user),
                'session_id' => $session['session_id'],
                'expires_at' => $session['expires_at']
            ];
            
        } catch (Exception $e) {
            error_log('Erro no service de autenticação: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do sistema. Tente novamente.',
                'code' => 'INTERNAL_ERROR',
                'debug' => DEBUG_MODE ? $e->getMessage() : null
            ];
        }
    }
    
    /**
     * Busca usuário por email
     */
    private function getUserByEmail($email) {
        $sql = "SELECT id, nome, email, senha, status, tipo_usuario, 
                       ultimo_login, senha_alterada_em, tentativas_login
                FROM usuarios 
                WHERE email = :email 
                AND status != 'deleted'";
        
        return $this->db->fetch($sql, ['email' => $email]);
    }
    
    /**
     * Verifica se a conta está ativa
     */
    private function isAccountActive($user) {
        return $user['status'] === 'active';
    }
    
    /**
     * Verifica a senha
     */
    private function verifyPassword($inputPassword, $storedPassword) {
        // Verificar se é hash bcrypt
        if (password_verify($inputPassword, $storedPassword)) {
            return true;
        }
        
        // Fallback para hash antigo (se necessário)
        if (hash('sha256', $inputPassword) === $storedPassword) {
            // Migrar para bcrypt na próxima vez
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica se a senha expirou
     */
    private function isPasswordExpired($user) {
        if (empty($user['senha_alterada_em'])) {
            return false; // Primeira vez, não expirou
        }
        
        $passwordAge = time() - strtotime($user['senha_alterada_em']);
        $maxAge = 90 * 24 * 60 * 60; // 90 dias em segundos
        
        return $passwordAge > $maxAge;
    }
    
    /**
     * Cria uma nova sessão
     */
    private function createSession($user, $remember) {
        try {
            // Gerar ID de sessão único
            $sessionId = $this->generateSessionId();
            
            // Calcular tempo de expiração
            $expiresAt = $remember ? 
                date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)) : // 30 dias
                date('Y-m-d H:i:s', time() + $this->config['session_timeout']);
            
            // Inserir sessão no banco
            $sql = "INSERT INTO sessoes (session_id, usuario_id, ip_address, user_agent, 
                                       expires_at, remember_me, criado_em) 
                    VALUES (:session_id, :usuario_id, :ip_address, :user_agent, 
                           :expires_at, :remember_me, NOW())";
            
            $result = $this->db->execute($sql, [
                'session_id' => $sessionId,
                'usuario_id' => $user['id'],
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'expires_at' => $expiresAt,
                'remember_me' => $remember ? 1 : 0
            ]);
            
            if ($result) {
                // Configurar cookie de sessão
                $this->setSessionCookie($sessionId, $expiresAt);
                
                // Atualizar último login
                $this->updateLastLogin($user['id']);
                
                return [
                    'session_id' => $sessionId,
                    'expires_at' => $expiresAt
                ];
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Erro ao criar sessão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gera ID de sessão único
     */
    private function generateSessionId() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Configura cookie de sessão
     */
    private function setSessionCookie($sessionId, $expiresAt) {
        $expires = strtotime($expiresAt);
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $httponly = true;
        
        setcookie('CFC_SESSION', $sessionId, $expires, '/', '', $secure, $httponly);
    }
    
    /**
     * Atualiza último login
     */
    private function updateLastLogin($userId) {
        $sql = "UPDATE usuarios 
                SET ultimo_login = NOW(), tentativas_login = 0 
                WHERE id = :id";
        
        $this->db->execute($sql, ['id' => $userId]);
    }
    
    /**
     * Registra login bem-sucedido
     */
    private function logSuccessfulLogin($userId) {
        $sql = "INSERT INTO logs (usuario_id, acao, ip_address, user_agent, criado_em) 
                VALUES (:usuario_id, 'login_success', :ip_address, :user_agent, NOW())";
        
        $this->db->execute($sql, [
            'usuario_id' => $userId,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    }
    
    /**
     * Sanitiza dados do usuário para retorno
     */
    private function sanitizeUserData($user) {
        return [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'tipo_usuario' => $user['tipo_usuario'],
            'ultimo_login' => $user['ultimo_login']
        ];
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
     * Verifica se uma sessão é válida
     */
    public function validateSession($sessionId) {
        try {
            $sql = "SELECT s.*, u.nome, u.email, u.tipo_usuario, u.status 
                    FROM sessoes s 
                    JOIN usuarios u ON s.usuario_id = u.id 
                    WHERE s.session_id = :session_id 
                    AND s.expires_at > NOW() 
                    AND u.status = 'active'";
            
            $session = $this->db->fetch($sql, ['session_id' => $sessionId]);
            
            if ($session) {
                // Atualizar última atividade
                $this->updateLastActivity($sessionId);
                return $session;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Erro ao validar sessão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza última atividade da sessão
     */
    private function updateLastActivity($sessionId) {
        $sql = "UPDATE sessoes SET ultima_atividade = NOW() WHERE session_id = :session_id";
        $this->db->execute($sql, ['session_id' => $sessionId]);
    }
    
    /**
     * Destroi uma sessão
     */
    public function destroySession($sessionId) {
        try {
            // Remover do banco
            $sql = "DELETE FROM sessoes WHERE session_id = :session_id";
            $this->db->execute($sql, ['session_id' => $sessionId]);
            
            // Remover cookie
            setcookie('CFC_SESSION', '', time() - 3600, '/');
            
            return true;
            
        } catch (Exception $e) {
            error_log('Erro ao destruir sessão: ' . $e->getMessage());
            return false;
        }
    }
}
