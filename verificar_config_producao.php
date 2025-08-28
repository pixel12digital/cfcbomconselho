<?php
// Script para verificar a configuração de produção
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Verificação de Configuração - Produção</h2>";

// Simular ambiente de produção
$_SERVER['HTTP_HOST'] = 'linen-mantis-198436.hostingersite.com';

echo "<h3>🌐 Simulando Ambiente de Produção</h3>";
echo "<p>HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "</p>";

// Incluir arquivos de configuração
require_once __DIR__ . '/includes/paths.php';
require_once INCLUDES_PATH . '/config.php';

echo "<h3>📋 Configurações Detectadas</h3>";
echo "<p>Ambiente: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'indefinido') . "</p>";
echo "<p>APP_URL: " . (defined('APP_URL') ? APP_URL : 'indefinido') . "</p>";
echo "<p>BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'indefinido') . "</p>";
echo "<p>Debug Mode: " . (DEBUG_MODE ? 'true' : 'false') . "</p>";
echo "<p>Log Enabled: " . (LOG_ENABLED ? 'true' : 'false') . "</p>";

// Verificar configurações de banco
echo "<h3>🗄️ Configurações de Banco</h3>";
echo "<p>DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'indefinido') . "</p>";
echo "<p>DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'indefinido') . "</p>";
echo "<p>DB_USER: " . (defined('DB_USER') ? DB_USER : 'indefinido') . "</p>";

// Verificar configurações de sessão
echo "<h3>🍪 Configurações de Sessão</h3>";
echo "<p>SESSION_TIMEOUT: " . (defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 'indefinido') . "</p>";
echo "<p>MAX_LOGIN_ATTEMPTS: " . (defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 'indefinido') . "</p>";

// Verificar se há diferenças na configuração de produção
echo "<h3>🔍 Análise de Diferenças</h3>";

if (ENVIRONMENT === 'production') {
    echo "<p style='color: green;'>✅ Ambiente detectado como produção</p>";
    
    // Verificar se as configurações estão corretas para produção
    if (DB_HOST === 'auth-db1607.hstgr.io') {
        echo "<p style='color: green;'>✅ Banco de produção configurado</p>";
    } else {
        echo "<p style='color: red;'>❌ Banco de produção não configurado corretamente</p>";
    }
    
    if (APP_URL === 'https://linen-mantis-198436.hostingersite.com') {
        echo "<p style='color: green;'>✅ URL de produção configurada</p>";
    } else {
        echo "<p style='color: red;'>❌ URL de produção não configurada corretamente</p>";
    }
    
    if (!DEBUG_MODE) {
        echo "<p style='color: green;'>✅ Debug mode desabilitado para produção</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Debug mode habilitado em produção</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Ambiente não detectado como produção</p>";
}

// Verificar se há problemas específicos de produção
echo "<h3>⚠️ Possíveis Problemas de Produção</h3>";

// Verificar se o banco de produção está acessível
echo "<h4>🗄️ Testando Conexão com Banco de Produção</h4>";
try {
    require_once INCLUDES_PATH . '/database.php';
    $db = Database::getInstance();
    echo "<p style='color: green;'>✅ Conexão com banco de produção estabelecida</p>";
    
    // Verificar se o usuário admin existe na produção
    $admin = $db->fetch("SELECT * FROM usuarios WHERE email = ?", ['admin@cfc.com']);
    if ($admin) {
        echo "<p style='color: green;'>✅ Usuário admin encontrado na produção</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$admin['id']}</li>";
        echo "<li><strong>Nome:</strong> {$admin['nome']}</li>";
        echo "<li><strong>Email:</strong> {$admin['email']}</li>";
        echo "<li><strong>Tipo:</strong> {$admin['tipo']}</li>";
        echo "<li><strong>Ativo:</strong> " . ($admin['ativo'] ? 'Sim' : 'Não') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Usuário admin não encontrado na produção!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao conectar com banco de produção: " . $e->getMessage() . "</p>";
}

// Verificar configurações de sessão específicas
echo "<h4>🍪 Verificando Configurações de Sessão</h4>";
echo "<p>session.save_handler: " . ini_get('session.save_handler') . "</p>";
echo "<p>session.save_path: " . ini_get('session.save_path') . "</p>";
echo "<p>session.cookie_domain: " . ini_get('session.cookie_domain') . "</p>";
echo "<p>session.cookie_path: " . ini_get('session.cookie_path') . "</p>";
echo "<p>session.cookie_secure: " . (ini_get('session.cookie_secure') ? 'true' : 'false') . "</p>";
echo "<p>session.cookie_httponly: " . (ini_get('session.cookie_httponly') ? 'true' : 'false') . "</p>";

echo "<h3>✅ Verificação Concluída</h3>";
?>
