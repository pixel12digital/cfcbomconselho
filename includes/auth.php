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
    public function login($login, $senha, $remember = false) {
        try {
            // Validar entrada
            if (empty($login) || empty($senha)) {
                return ['success' => false, 'message' => 'Login e senha são obrigatórios'];
            }
            
            // Verificar se está bloqueado
            if ($this->isLocked($this->getClientIP())) {
                return ['success' => false, 'message' => 'Muitas tentativas de login. Tente novamente em ' . $this->getLockoutTimeRemaining() . ' minutos'];
            }
            
            // Buscar usuário (por email ou CPF)
            $usuario = $this->getUserByLogin($login);
            if (!$usuario) {
                $this->incrementAttempts($this->getClientIP());
                return ['success' => false, 'message' => 'Login ou senha inválidos'];
            }
            
            // Verificar senha
            if (!password_verify($senha, $usuario['senha'])) {
                $this->incrementAttempts($this->getClientIP());
                return ['success' => false, 'message' => 'Login ou senha inválidos'];
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
        // Obter user_id ANTES de limpar a sessão
        $user_id = $_SESSION['user_id'] ?? null;
        
        if ($user_id && defined('AUDIT_ENABLED') && AUDIT_ENABLED) {
            try {
                dbLog($user_id, 'logout', 'usuarios', $user_id);
            } catch (Exception $e) {
                // Ignorar erros de log por enquanto
                if (defined('LOG_ENABLED') && LOG_ENABLED) {
                    error_log('Erro ao registrar log de logout: ' . $e->getMessage());
                }
            }
        }
        
        // Remover cookies de "lembrar-me" ANTES de destruir a sessão
        if (isset($_COOKIE['remember_token'])) {
            try {
                $this->removeRememberToken($_COOKIE['remember_token']);
            } catch (Exception $e) {
                // Ignorar erros
            }
            $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            @setcookie('remember_token', '', time() - 3600, '/', '', $is_https, true);
            unset($_COOKIE['remember_token']);
        }
        
        // Remover todos os cookies relacionados à sessão ANTES de destruir
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            @setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $is_https, $params["httponly"]
            );
        }
        
        // Remover cookie CFC_SESSION se existir
        if (isset($_COOKIE['CFC_SESSION'])) {
            $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            // Tentar remover com diferentes combinações de parâmetros
            @setcookie('CFC_SESSION', '', time() - 42000, '/', '', $is_https, true);
            if (strpos($host, 'hostingersite.com') !== false) {
                @setcookie('CFC_SESSION', '', time() - 42000, '/', '.hostingersite.com', $is_https, true);
                @setcookie('CFC_SESSION', '', time() - 42000, '/', $host, $is_https, true);
            }
            unset($_COOKIE['CFC_SESSION']);
        }
        
        // Limpar todas as variáveis de sessão ANTES de destruir
        $_SESSION = array();
        
        // Fechar a sessão antes de destruir (importante para garantir limpeza completa)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Destruir a sessão completamente
        if (function_exists('session_destroy') && session_status() !== PHP_SESSION_NONE) {
            @session_destroy();
        }
        
        // Garantir que não há sessão ativa após destruição
        // Se ainda houver sessão ativa, tentar limpar novamente
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_start();
            $_SESSION = array();
            @session_destroy();
        }
        
        return ['success' => true, 'message' => 'Logout realizado com sucesso'];
    }
    
    // Verificar se usuário está logado
    public function isLoggedIn() {
        // Verificar se a sessão está realmente ativa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        // Verificar sessão primeiro
        if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
            // Verificar timeout
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
    
    // Verificar se é aluno
    public function isStudent() {
        $user = $this->getCurrentUser();
        return $user && $user['tipo'] === 'aluno';
    }
    
    // Verificar se pode adicionar aulas (apenas admin e secretaria)
    public function canAddLessons() {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        return in_array($user['tipo'], ['admin', 'secretaria']);
    }
    
    // Verificar se pode editar aulas (admin, secretaria e instrutor)
    public function canEditLessons() {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        return in_array($user['tipo'], ['admin', 'secretaria', 'instrutor']);
    }
    
    // Verificar se pode cancelar aulas (admin, secretaria e instrutor)
    public function canCancelLessons() {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        return in_array($user['tipo'], ['admin', 'secretaria', 'instrutor']);
    }
    
    /**
     * Redireciona o usuário para o painel apropriado após login
     * Centraliza a lógica de redirecionamento por tipo de usuário
     * Verifica se precisa trocar senha e redireciona adequadamente
     * 
     * @param array|null $user Dados do usuário (opcional, usa getCurrentUser() se não fornecido)
     * @return void (faz redirect e exit)
     */
    public function redirectAfterLogin($user = null) {
        if (!$user) {
            $user = $this->getCurrentUser();
        }
        
        if (!$user) {
            // Se não houver usuário, redirecionar para login
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            header('Location: ' . $basePath . '/login.php');
            exit;
        }
        
        // Verificar se precisa trocar senha
        // Se a coluna precisa_trocar_senha existir e estiver = 1, forçar troca de senha
        $precisaTrocarSenha = false;
        try {
            // Verificar se coluna existe e se está ativa
            $db = $this->db;
            $checkColumn = $db->fetch("SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha'");
            if ($checkColumn) {
                // Buscar valor atual do flag
                $usuarioCompleto = $db->fetch("SELECT precisa_trocar_senha FROM usuarios WHERE id = ?", [$user['id']]);
                if ($usuarioCompleto && isset($usuarioCompleto['precisa_trocar_senha']) && $usuarioCompleto['precisa_trocar_senha'] == 1) {
                    $precisaTrocarSenha = true;
                }
            }
        } catch (Exception $e) {
            // Se houver erro ao verificar, continuar normalmente
            if (LOG_ENABLED) {
                error_log('Erro ao verificar precisa_trocar_senha: ' . $e->getMessage());
            }
        }
        
        $tipo = strtolower($user['tipo'] ?? '');
        
        // Se precisa trocar senha, redirecionar para página de troca
        if ($precisaTrocarSenha) {
                    switch ($tipo) {
                        case 'instrutor':
                            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
                            header('Location: ' . $basePath . '/instrutor/trocar-senha.php?forcado=1');
                            exit;
                case 'admin':
                case 'secretaria':
                    // TODO: Criar página de troca de senha para admin/secretaria se necessário
                    // Por enquanto, permite acesso normal
                    break;
                case 'aluno':
                    // TODO: Criar página de troca de senha para aluno se necessário
                    // Por enquanto, permite acesso normal
                    break;
            }
        }
        
        // Determinar URL de destino baseado no tipo de usuário
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        switch ($tipo) {
            case 'admin':
            case 'secretaria':
                // Admin e Secretaria vão para o painel administrativo
                header('Location: ' . $basePath . '/admin/index.php');
                break;
                
            case 'instrutor':
                // Instrutor vai para o painel do instrutor
                header('Location: ' . $basePath . '/instrutor/dashboard.php');
                break;
                
            case 'aluno':
                // Aluno vai para o painel do aluno
                header('Location: ' . $basePath . '/aluno/dashboard.php');
                break;
                
            default:
                // Tipo desconhecido, redirecionar para login
                header('Location: ' . $basePath . '/login.php');
        }
        
        exit;
    }
    
    // Verificar se pode acessar configurações (apenas admin)
    public function canAccessConfigurations() {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        return $user['tipo'] === 'admin';
    }
    
    // Verificar se pode gerenciar usuários (admin e secretaria)
    public function canManageUsers() {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        return in_array($user['tipo'], ['admin', 'secretaria']);
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
    
    // Buscar usuário por email ou CPF
    private function getUserByLogin($login) {
        $login = trim($login);
        
        // Se contém apenas números, tratar como CPF
        if (preg_match('/^[0-9]+$/', $login)) {
            $sql = "SELECT u.*, c.id as cfc_id FROM usuarios u 
                    LEFT JOIN cfcs c ON u.id = c.responsavel_id 
                    WHERE u.cpf = :cpf LIMIT 1";
            
            return $this->db->fetch($sql, ['cpf' => $login]);
        }
        
        // Caso contrário, tratar como email
        $sql = "SELECT u.*, c.id as cfc_id FROM usuarios u 
                LEFT JOIN cfcs c ON u.id = c.responsavel_id 
                WHERE u.email = :email LIMIT 1";
        
        return $this->db->fetch($sql, ['email' => strtolower($login)]);
    }
    
    // Buscar usuário por email (método mantido para compatibilidade)
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
                'dashboard', 'alunos', 'aulas_visualizar', 'aulas_editar', 'aulas_cancelar',
                'veiculos', 'relatorios'
                // Removido: 'aulas_adicionar', 'usuarios', 'cfcs', 'instrutores', 'configuracoes'
            ],
            'secretaria' => [
                'dashboard', 'usuarios', 'cfcs', 'alunos', 'instrutores', 'aulas', 
                'veiculos', 'relatorios'
                // Removido: 'configuracoes', 'backup', 'logs'
            ],
            'aluno' => [
                'dashboard', 'aulas_visualizar', 'relatorios_visualizar'
                // Apenas visualização
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

function isStudent() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->isStudent();
}

function canAddLessons() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->canAddLessons();
}

function canEditLessons() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->canEditLessons();
}

function canCancelLessons() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->canCancelLessons();
}

function canAccessConfigurations() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->canAccessConfigurations();
}

function canManageUsers() {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    return $auth->canManageUsers();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
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

/**
 * FASE 2 - Correção: Função centralizada para obter instrutor_id
 * Arquivo: includes/auth.php (linha ~800)
 * 
 * Usa o mesmo padrão da Fase 1 (admin/api/instrutor-aulas.php linha 64)
 * Query: SELECT id FROM instrutores WHERE usuario_id = ?
 * 
 * @param int|null $userId ID do usuário (opcional, usa getCurrentUser() se não fornecido)
 * @return int|null ID do instrutor ou null se não encontrado
 */
function getCurrentInstrutorId($userId = null) {
    if ($userId === null) {
        $user = getCurrentUser();
        if (!$user) {
            return null;
        }
        $userId = $user['id'];
    }
    
    $db = db();
    $instrutor = $db->fetch("SELECT id FROM instrutores WHERE usuario_id = ?", [$userId]);
    
    if (!$instrutor) {
        return null;
    }
    
    return $instrutor['id'];
}

/**
 * Criar registro de instrutor a partir de um usuário
 * 
 * SYNC_INSTRUTORES - Função helper para garantir consistência entre usuarios e instrutores
 * 
 * @param int $usuarioId ID do usuário na tabela usuarios
 * @param int|null $cfcId ID do CFC (se null, busca o primeiro CFC disponível)
 * @return array ['success' => bool, 'instrutor_id' => int|null, 'message' => string, 'created' => bool]
 */
function createInstrutorFromUser($usuarioId, $cfcId = null) {
    $db = db();
    
    // Verificar se usuário existe e é do tipo instrutor
    $usuario = $db->fetch("SELECT id, nome, email, tipo FROM usuarios WHERE id = ?", [$usuarioId]);
    if (!$usuario) {
        if (LOG_ENABLED) {
            error_log('[SYNC_INSTRUTORES] Usuário não encontrado: usuario_id=' . $usuarioId);
        }
        return [
            'success' => false,
            'instrutor_id' => null,
            'message' => 'Usuário não encontrado',
            'created' => false
        ];
    }
    
    if ($usuario['tipo'] !== 'instrutor') {
        if (LOG_ENABLED) {
            error_log('[SYNC_INSTRUTORES] Usuário não é do tipo instrutor: usuario_id=' . $usuarioId . ', tipo=' . $usuario['tipo']);
        }
        return [
            'success' => false,
            'instrutor_id' => null,
            'message' => 'Usuário não é do tipo instrutor',
            'created' => false
        ];
    }
    
    // Verificar se já existe registro em instrutores
    $instrutorExistente = $db->fetch("SELECT id FROM instrutores WHERE usuario_id = ?", [$usuarioId]);
    if ($instrutorExistente) {
        if (LOG_ENABLED) {
            error_log('[SYNC_INSTRUTORES] Instrutor já existe: usuario_id=' . $usuarioId . ', instrutor_id=' . $instrutorExistente['id']);
        }
        return [
            'success' => true,
            'instrutor_id' => $instrutorExistente['id'],
            'message' => 'Instrutor já existe',
            'created' => false
        ];
    }
    
    // Buscar CFC se não foi fornecido
    if ($cfcId === null) {
        $cfc = $db->fetch("SELECT id FROM cfcs ORDER BY id LIMIT 1");
        if (!$cfc) {
            if (LOG_ENABLED) {
                error_log('[SYNC_INSTRUTORES] Nenhum CFC encontrado no banco de dados');
            }
            return [
                'success' => false,
                'instrutor_id' => null,
                'message' => 'Nenhum CFC encontrado no banco de dados',
                'created' => false
            ];
        }
        $cfcId = $cfc['id'];
    }
    
    // Gerar credencial única
    $credencial = 'CRED-' . str_pad($usuarioId, 6, '0', STR_PAD_LEFT);
    
    // Verificar se credencial já existe
    $credencialExistente = $db->fetch("SELECT id FROM instrutores WHERE credencial = ?", [$credencial]);
    if ($credencialExistente) {
        // Se existir, adicionar sufixo com timestamp
        $credencial = 'CRED-' . str_pad($usuarioId, 6, '0', STR_PAD_LEFT) . '-' . time();
    }
    
    // Criar registro de instrutor
    $instrutorData = [
        'nome' => $usuario['nome'] ?? '',
        'usuario_id' => $usuarioId,
        'cfc_id' => $cfcId,
        'credencial' => $credencial,
        'ativo' => 1,
        'criado_em' => date('Y-m-d H:i:s')
    ];
    
    try {
        $instrutorId = $db->insert('instrutores', $instrutorData);
        
        if ($instrutorId) {
            if (LOG_ENABLED) {
                error_log('[SYNC_INSTRUTORES] Instrutor criado com sucesso: usuario_id=' . $usuarioId . ', instrutor_id=' . $instrutorId . ', cfc_id=' . $cfcId . ', credencial=' . $credencial);
            }
            return [
                'success' => true,
                'instrutor_id' => $instrutorId,
                'message' => 'Instrutor criado com sucesso',
                'created' => true
            ];
        } else {
            if (LOG_ENABLED) {
                error_log('[SYNC_INSTRUTORES] Erro ao criar instrutor: usuario_id=' . $usuarioId . ', erro=' . $db->getLastError());
            }
            return [
                'success' => false,
                'instrutor_id' => null,
                'message' => 'Erro ao criar instrutor: ' . $db->getLastError(),
                'created' => false
            ];
        }
    } catch (Exception $e) {
        if (LOG_ENABLED) {
            error_log('[SYNC_INSTRUTORES] Exceção ao criar instrutor: usuario_id=' . $usuarioId . ', erro=' . $e->getMessage());
        }
        return [
            'success' => false,
            'instrutor_id' => null,
            'message' => 'Erro ao criar instrutor: ' . $e->getMessage(),
            'created' => false
        ];
    }
}

// Instância global do sistema de autenticação - MOVIDA PARA O FINAL
$auth = new Auth();

/**
 * Função global para redirecionar após login baseado no tipo de usuário
 * Centraliza a lógica de redirecionamento
 * 
 * @param array|null $user Dados do usuário (opcional, usa getCurrentUser() se não fornecido)
 * @return void (faz redirect e exit)
 */
function redirectAfterLogin($user = null) {
    global $auth;
    $auth->redirectAfterLogin($user);
}

?>
