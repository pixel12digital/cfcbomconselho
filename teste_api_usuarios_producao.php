<?php
// Script para testar especificamente a API de usuários na produção
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Teste Específico - API de Usuários na Produção</h2>";

// Simular ambiente de produção
$_SERVER['HTTP_HOST'] = 'linen-mantis-198436.hostingersite.com';

echo "<h3>🌐 Simulando Ambiente de Produção</h3>";
echo "<p>HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "</p>";

// Incluir arquivos necessários
require_once __DIR__ . '/includes/paths.php';
require_once INCLUDES_PATH . '/config.php';
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';

try {
    echo "<h3>📋 Configurações de Sessão</h3>";
    echo "<p>SESSION_COOKIE_SECURE: " . (SESSION_COOKIE_SECURE ? 'true' : 'false') . "</p>";
    echo "<p>SESSION_COOKIE_DOMAIN: " . SESSION_COOKIE_DOMAIN . "</p>";
    echo "<p>SESSION_COOKIE_HTTPONLY: " . (SESSION_COOKIE_HTTPONLY ? 'true' : 'false') . "</p>";
    
    // Testar conexão com banco
    echo "<h3>🗄️ Testando Conexão com Banco</h3>";
    $db = Database::getInstance();
    echo "<p style='color: green;'>✅ Conexão com banco estabelecida</p>";
    
    // Verificar se o usuário admin existe
    echo "<h3>👤 Verificando Usuário Admin</h3>";
    $admin = $db->fetch("SELECT * FROM usuarios WHERE email = ?", ['admin@cfc.com']);
    
    if ($admin) {
        echo "<p style='color: green;'>✅ Usuário admin encontrado:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$admin['id']}</li>";
        echo "<li><strong>Nome:</strong> {$admin['nome']}</li>";
        echo "<li><strong>Email:</strong> {$admin['email']}</li>";
        echo "<li><strong>Tipo:</strong> {$admin['tipo']}</li>";
        echo "<li><strong>Ativo:</strong> " . ($admin['ativo'] ? 'Sim' : 'Não') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Usuário admin não encontrado!</p>";
        exit;
    }
    
    // Testar sistema de autenticação
    echo "<h3>🔑 Testando Sistema de Autenticação</h3>";
    
    // Iniciar sessão
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "<p>Status da sessão: " . session_status() . "</p>";
    echo "<p>ID da sessão: " . (session_id() ?: 'Nenhum') . "</p>";
    
    // Criar instância de autenticação
    $auth = new Auth();
    echo "<p style='color: green;'>✅ Instância de Auth criada</p>";
    
    // Testar login
    echo "<h3>🚀 Testando Login</h3>";
    $loginResult = $auth->login('admin@cfc.com', 'admin123');
    
    if ($loginResult['success']) {
        echo "<p style='color: green;'>✅ Login realizado com sucesso!</p>";
        echo "<p><strong>Mensagem:</strong> {$loginResult['message']}</p>";
        
        // Verificar sessão
        echo "<h3>🍪 Verificando Sessão</h3>";
        echo "<p>User ID na sessão: " . ($_SESSION['user_id'] ?? 'Não definido') . "</p>";
        echo "<p>User Type na sessão: " . ($_SESSION['user_type'] ?? 'Não definido') . "</p>";
        echo "<p>User Name na sessão: " . ($_SESSION['user_name'] ?? 'Não definido') . "</p>";
        
        // Verificar se está logado
        $isLoggedIn = $auth->isLoggedIn();
        echo "<p>isLoggedIn(): " . ($isLoggedIn ? 'true' : 'false') . "</p>";
        
        // Verificar usuário atual
        $currentUser = $auth->getCurrentUser();
        if ($currentUser) {
            echo "<p style='color: green;'>✅ getCurrentUser() retornou dados:</p>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> {$currentUser['id']}</li>";
            echo "<li><strong>Nome:</strong> {$currentUser['nome']}</li>";
            echo "<li><strong>Email:</strong> {$currentUser['email']}</li>";
            echo "<li><strong>Tipo:</strong> {$currentUser['tipo']}</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ getCurrentUser() retornou null</p>";
        }
        
        // Verificar permissões
        $hasPermission = $auth->hasPermission('admin');
        echo "<p>hasPermission('admin'): " . ($hasPermission ? 'true' : 'false') . "</p>";
        
        $isAdmin = $auth->isAdmin();
        echo "<p>isAdmin(): " . ($isAdmin ? 'true' : 'false') . "</p>";
        
        // Simular teste da API de usuários
        echo "<h3>📡 Simulando Teste da API de Usuários</h3>";
        
        // Verificar se as funções estão disponíveis
        if (function_exists('isLoggedIn') && function_exists('hasPermission')) {
            echo "<p style='color: green;'>✅ Funções de autenticação disponíveis</p>";
            
            // Simular verificação da API
            $isLoggedIn = isLoggedIn();
            $hasPermission = hasPermission('admin');
            
            echo "<p>isLoggedIn() na API: " . ($isLoggedIn ? 'true' : 'false') . "</p>";
            echo "<p>hasPermission('admin') na API: " . ($hasPermission ? 'true' : 'false') . "</p>";
            
            if ($isLoggedIn && $hasPermission) {
                echo "<p style='color: green;'>✅ API de usuários funcionaria corretamente</p>";
            } else {
                echo "<p style='color: red;'>❌ API de usuários falharia na autenticação</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Funções de autenticação não disponíveis</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Login falhou!</p>";
        echo "<p><strong>Erro:</strong> {$loginResult['error']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
}

echo "<h3>✅ Teste Concluído</h3>";
?>
