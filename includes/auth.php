<?php
// =====================================================
// SISTEMA DE AUTENTICAÇÃO E SESSÕES
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class Auth {
    private $db;
    private $maxAttempts;
    private $lockoutTime;
    
    public function __construct() {
        $this->db = db();
        $this->maxAttempts = MAX_LOGIN_ATTEMPTS;
        $this->lockoutTime = LOGIN_TIMEOUT;
        
        // Sessão já foi iniciada no config.php
    }
    
    // Método de login principal
    public function login($email, $senha, $remember = false) {
        try {
            // Validar entrada
            if (empty($email) || empty($senha)) {
                return ['success' => false, 'message' => 'Email e senha são obrigatórios'];
            }
            
            // Verificar se está bloqueado
            if ($this->isLocked($this->getClientIP())) {
                return ['success' => false, 'message' => 'Muitas tentativas de login. Tente novamente em ' . $this->getLockoutTimeRemaining() . ' minutos'];
            }
            
            // Buscar usuário
            $usuario = $this->getUserByEmail($email);
            if (!$usuario) {
                $this->incrementAttempts($this->getClientIP());
                return ['success' => false, 'message' => 'Email ou senha inválidos'];
            }
            
            // Verificar senha
            if (!password_verify($senha, $usuario['senha'])) {
                $this->incrementAttempts($this->getClientIP());
                return ['success' => false, 'message' => 'Email ou senha inválidos'];
            }
            
            // Verificar se usuário está ativo
            if (!$usuario['ativo']) {
                return ['success' => false, 'message' => 'Usuário inativo. Entre em contato com o administrador'];
            }
            
            // Login bem-sucedido
            $this->createSession($usuario, $remember);
            $this->resetAttempts($this->getClientIP());
            $this->updateLastLogin($usuario['id']);
            
            // Log de login
            if (AUDIT_ENABLED) {
                try {
                    // Verificar se a tabela logs existe antes de tentar inserir
                    $this->db->query("SHOW TABLES LIKE 'logs'");
                    dbLog($usuario['id'], 'login', 'usuarios', $usuario['id']);
                } catch (Exception $e) {
                    // Ignorar erros de log por enquanto
                    if (LOG_ENABLED) {
                        error_log('Erro ao registrar log: ' . $e->getMessage());
                    }
                }
            }
            
            return [
                'success' => true, 
                'message' => 'Login realizado com sucesso',
                'user' => $this->getUserData($usuario['id'])
            ];
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('Erro no login: ' . $e->getMessage());
            }
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    // Método de logout
    public function logout() {
        if (isset($_SESSION['user_id']) && AUDIT_ENABLED) {
            try {
                dbLog($_SESSION['user_id'], 'logout', 'usuarios', $_SESSION['user_id']);
            } catch (Exception $e) {
                // Ignorar erros de log por enquanto
                if (LOG_ENABLED) {
                    error_log('Erro ao registrar log de logout: ' . $e->getMessage());
                }
            }
        }
        
        // Remover cookies de "lembrar-me" ANTES de destruir a sessão
        if (isset($_COOKIE['remember_token'])) {
            $this->removeRememberToken($_COOKIE['remember_token']);
            $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            setcookie('remember_token', '', time() - 3600, '/', '', $is_https, true);
        }
        
        // Limpar todas as variáveis de sessão
        $_SESSION = array();
        
        // Destruir a sessão
        session_destroy();
        
        // Garantir que a sessão seja completamente limpa
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        
        // Remover todos os cookies relacionados à sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $is_https, $params["httponly"]
            );
        }
        
        // Remover cookie CFC_SESSION se existir
        if (isset($_COOKIE['CFC_SESSION'])) {
            $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            // Tentar remover com diferentes combinações de parâmetros
            setcookie('CFC_SESSION', '', time() - 42000, '/', '', $is_https, true);
            if (strpos($host, 'hostingersite.com') !== false) {
                setcookie('CFC_SESSION', '', time() - 42000, '/', '.hostingersite.com', $is_https, true);
                setcookie('CFC_SESSION', '', time() - 42000, '/', $host, $is_https, true);
            }
        }
        
        return ['success' => true, 'message' => 'Logout realizado com sucesso'];
    }
    
    // Verificar se usuário está logado
    public function isLoggedIn() {
        // Verificar sessão primeiro
        if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        // Só verificar cookie "lembrar-me" se não há sessão ativa
        // e se o cookie realmente existe
        if (isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token'])) {
            try {
                return $this->validateRememberToken($_COOKIE['remember_token']);
            } catch (Exception $e) {
                // Se houver erro ao validar token, remover cookie e retornar false
                setcookie('remember_token', '', time() - 3600, '/');
                return false;
            }
        }
        
        return false;
    }
    
    // Obter dados do usuário logado
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }
        
        return $this->getUserData($userId);
    }
    
    // Verificar permissões
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Admin tem todas as permissões
        if ($user['tipo'] === 'admin') {
            return true;
        }
        
        // Verificar permissões específicas por tipo
        $permissions = $this->getUserPermissions($user['tipo']);
        return in_array($permission, $permissions);
    }
    
    // Verificar se é admin
    public function isAdmin() {
        $user = $this->getCurrentUser();
        return $user && $user['tipo'] === 'admin';
    }
    
    // Verificar se é instrutor
    public function isInstructor() {
        $user = $this->getCurrentUser();
        return $user && $user['tipo'] === 'instrutor';
    }
    
    // Verificar se é secretaria
    public function isSecretary() {
        $user = $this->getCurrentUser();
        return $user && $user['tipo'] === 'secretaria';
    }
    
    // Criar nova sessão
    private function createSession($usuario, $remember = false) {
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_name'] = $usuario['nome'];
        $_SESSION['user_type'] = $usuario['tipo'];
        $_SESSION['user_cfc_id'] = $usuario['cfc_id'] ?? null;
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $this->getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Criar token de "lembrar-me" se solicitado
        if ($remember) {
            $token = $this->createRememberToken($usuario['id']);
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
        }
        
        // Registrar sessão no banco
        $this->registerSession($usuario['id']);
    }
    
    // Registrar sessão no banco
    private function registerSession($userId) {
        $token = bin2hex(random_bytes(32));
        $expiraEm = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        
        $sql = "INSERT INTO sessoes (usuario_id, token, ip_address, user_agent, expira_em) 
                VALUES (:usuario_id, :token, :ip_address, :user_agent, :expira_em)";
        
        $this->db->query($sql, [
            'usuario_id' => $userId,
            'token' => $token,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expira_em' => $expiraEm
        ]);
    }
    
    // Buscar usuário por email
    private function getUserByEmail($email) {
        $sql = "SELECT u.*, c.id as cfc_id FROM usuarios u 
                LEFT JOIN cfcs c ON u.id = c.responsavel_id 
                WHERE u.email = :email LIMIT 1";
        
        return $this->db->fetch($sql, ['email' => strtolower(trim($email))]);
    }
    
    // Obter dados do usuário
    private function getUserData($userId) {
        $sql = "SELECT u.id, u.nome, u.email, u.tipo, u.cpf, u.telefone, u.ultimo_login, 
                       c.id as cfc_id, c.nome as cfc_nome, c.cnpj as cfc_cnpj
                FROM usuarios u 
                LEFT JOIN cfcs c ON u.id = c.responsavel_id 
                WHERE u.id = :id LIMIT 1";
        
        return $this->db->fetch($sql, ['id' => $userId]);
    }
    
    // Atualizar último login
    private function updateLastLogin($userId) {
        $sql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id";
        $this->db->query($sql, ['id' => $userId]);
    }
    
    // Verificar se IP está bloqueado
    private function isLocked($ip) {
        // Verificação simplificada - sempre retorna false por enquanto
        // TODO: Implementar verificação de bloqueio quando a tabela logs estiver funcionando
        return false;
    }
    
    // Incrementar tentativas de login
    private function incrementAttempts($ip) {
        // Função simplificada - não faz nada por enquanto
        // TODO: Implementar contagem de tentativas quando a tabela logs estiver funcionando
    }
    
    // Resetar tentativas de login
    private function resetAttempts($ip) {
        // Função simplificada - não faz nada por enquanto
        // TODO: Implementar reset de tentativas quando a tabela logs estiver funcionando
    }
    
    // Obter tempo restante do bloqueio
    private function getLockoutTimeRemaining() {
        // Função simplificada - sempre retorna 0 por enquanto
        // TODO: Implementar cálculo de tempo quando a tabela logs estiver funcionando
        return 0;
    }
    
    // Criar token de "lembrar-me"
    private function createRememberToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiraEm = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 dias
        
        $sql = "INSERT INTO sessoes (usuario_id, token, ip_address, user_agent, expira_em) 
                VALUES (:usuario_id, :token, :ip_address, :user_agent, :expira_em)";
        
        $this->db->query($sql, [
            'usuario_id' => $userId,
            'token' => $token,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expira_em' => $expiraEm
        ]);
        
        return $token;
    }
    
    // Validar token de "lembrar-me"
    private function validateRememberToken($token) {
        $sql = "SELECT s.*, u.* FROM sessoes s 
                JOIN usuarios u ON s.usuario_id = u.id 
                WHERE s.token = :token AND s.expira_em > NOW() AND u.ativo = 1 
                LIMIT 1";
        
        $result = $this->db->fetch($sql, ['token' => $token]);
        
        if ($result) {
            $this->createSession($result, false);
            return true;
        }
        
        return false;
    }
    
    // Remover token de "lembrar-me"
    private function removeRememberToken($token) {
        $sql = "DELETE FROM sessoes WHERE token = :token";
        $this->db->query($sql, ['token' => $token]);
    }
    
    // Obter permissões do usuário
    private function getUserPermissions($userType) {
        $permissions = [
            'admin' => [
                'dashboard', 'usuarios', 'cfcs', 'alunos', 'instrutores', 'aulas', 
                'veiculos', 'relatorios', 'configuracoes', 'backup', 'logs'
            ],
            'instrutor' => [
                'dashboard', 'alunos', 'aulas', 'veiculos', 'relatorios'
            ],
            'secretaria' => [
                'dashboard', 'alunos', 'aulas', 'relatorios'
            ]
        ];
        
        return $permissions[$userType] ?? [];
    }
    
    // Obter IP do cliente
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Limpar sessões expiradas
    public function cleanupExpiredSessions() {
        $sql = "DELETE FROM sessoes WHERE expira_em <= NOW()";
        return $this->db->query($sql);
    }
    
    // Forçar logout de todas as sessões do usuário
    public function forceLogoutAllSessions($userId) {
        $sql = "DELETE FROM sessoes WHERE usuario_id = :usuario_id";
        return $this->db->query($sql, ['usuario_id' => $userId]);
    }
    
    // Verificar se sessão é válida
    public function validateSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Verificar se IP mudou
        if ($_SESSION['ip_address'] !== $this->getClientIP()) {
            $this->logout();
            return false;
        }
        
        // Verificar se User-Agent mudou
        if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    // Renovar sessão
    public function renewSession() {
        if ($this->isLoggedIn()) {
            $_SESSION['last_activity'] = time();
            
            // Atualizar expiração no banco
            $sql = "UPDATE sessoes SET expira_em = DATE_ADD(NOW(), INTERVAL :timeout SECOND) 
                    WHERE usuario_id = :usuario_id AND token = :token";
            
            $this->db->query($sql, [
                'timeout' => SESSION_TIMEOUT,
                'usuario_id' => $_SESSION['user_id'],
                'token' => $_COOKIE['remember_token'] ?? ''
            ]);
        }
    }
    
    // Obter estatísticas de sessões
    public function getSessionStats() {
        $stats = [];
        
        // Total de sessões ativas
        $sql = "SELECT COUNT(*) as total FROM sessoes WHERE expira_em > NOW()";
        $result = $this->db->fetch($sql);
        $stats['active_sessions'] = $result['total'];
        
        // Sessões por usuário
        $sql = "SELECT u.nome, COUNT(s.id) as sessions FROM sessoes s 
                JOIN usuarios u ON s.usuario_id = u.id 
                WHERE s.expira_em > NOW() 
                GROUP BY u.id, u.nome 
                ORDER BY sessions DESC";
        $stats['sessions_by_user'] = $this->db->fetchAll($sql);
        
        // Sessões por IP
        $sql = "SELECT ip_address, COUNT(*) as sessions FROM sessoes 
                WHERE expira_em > NOW() 
                GROUP BY ip_address 
                ORDER BY sessions DESC";
        $stats['sessions_by_ip'] = $this->db->fetchAll($sql);
        
        return $stats;
    }
}

// Funções globais de autenticação
function isLoggedIn() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->isLoggedIn();
}

function getCurrentUser() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->getCurrentUser();
}

function hasPermission($permission) {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->hasPermission($permission);
}

function isAdmin() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->isAdmin();
}

function isInstructor() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->isInstructor();
}

function isSecretary() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->isSecretary();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

function requirePermission($permission) {
    requireLogin();
    if (!hasPermission($permission)) {
        header('HTTP/1.1 403 Forbidden');
        die('Acesso negado');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        die('Acesso negado - Administrador requerido');
    }
}

// Middleware de autenticação para APIs
function apiRequireAuth() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Não autorizado']);
        exit;
    }
}

function apiRequirePermission($permission) {
    apiRequireAuth();
    if (!hasPermission($permission)) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
}

function apiRequireAdmin() {
    apiRequireAuth();
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado - Administrador requerido']);
        exit;
    }
}

// Instância global do sistema de autenticação - MOVIDA PARA O FINAL
$auth = new Auth();

?>
