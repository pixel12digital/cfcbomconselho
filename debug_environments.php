<?php
// Debug para verificar configurações de ambiente
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug de Ambiente - CFC Sistema</h1>";

// Informações básicas do servidor
echo "<h2>Informações do Servidor:</h2>";
echo "<ul>";
echo "<li><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'NÃO DEFINIDO') . "</li>";
echo "<li><strong>SERVER_NAME:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'NÃO DEFINIDO') . "</li>";
echo "<li><strong>HTTPS:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'SIM' : 'NÃO') . "</li>";
echo "<li><strong>SERVER_PORT:</strong> " . ($_SERVER['SERVER_PORT'] ?? 'NÃO DEFINIDO') . "</li>";
echo "<li><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'NÃO DEFINIDO') . "</li>";
echo "<li><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'NÃO DEFINIDO') . "</li>";
echo "</ul>";

// Verificar detecção de ambiente
function detectEnvironment() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $port = $_SERVER['SERVER_PORT'] ?? '80';
    
    if (in_array($host, ['localhost', '127.0.0.1']) || strpos($host, 'localhost') !== false) {
        return 'local';
    } elseif (strpos($host, 'hostinger') !== false || strpos($host, 'hstgr.io') !== false) {
        return 'production';
    } else {
        return 'production';
    }
}

$environment = detectEnvironment();

echo "<h2>Detecção de Ambiente:</h2>";
echo "<ul>";
echo "<li><strong>Ambiente Detectado:</strong> " . $environment . "</li>";
echo "<li><strong>Condição local:</strong> " . ((strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])) ? 'VERDADEIRO' : 'FALSO') . "</li>";
echo "<li><strong>Condição hostinger:</strong> " . ((strpos($_SERVER['HTTP_HOST'] ?? '', 'hostinger') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'hstgr.io') !== false) ? 'VERDADEIRO' : 'FALSO') . "</li>";
echo "</ul>";

// Testar configurações de sessão
echo "<h2>Configurações de Sessão:</h2>";
session_start();
echo "<ul>";
echo "<li><strong>Session Name:</strong> " . session_name() . "</li>";
echo "<li><strong>Session ID:</strong> " . session_id() . "</li>";
echo "<li><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'ATIVO' : 'INATIVO') . "</li>";
echo "<li><strong>Cookie Lifetime:</strong> " . ini_get('session.cookie_lifetime') . "</li>";
echo "<li><strong>Cookie Path:</strong> " . ini_get('session.cookie_path') . "</li>";
echo "<li><strong>Cookie Domain:</strong> " . ini_get('session.cookie_domain') . "</li>";
echo "<li><strong>Cookie Secure:</strong> " . (ini_get('session.cookie_secure') ? 'SIM' : 'NÃO') . "</li>";
echo "<li><strong>Cookie HttpOnly:</strong> " . (ini_get('session.cookie_httponly') ? 'SIM' : 'NÃO') . "</li>";
echo "</ul>";

// Testar output buffering
echo "<h2>Output Buffering:</h2>";
echo "<ul>";
echo "<li><strong>Output Buffer Level:</strong> " . ob_get_level() . "</h2>";
echo "<li><strong>Headers Sent:</strong> " . (headers_sent($file, $line) ? "SIM (arquivo: $file, linha: $line)" : 'NÃO') . "</li>";
echo "</ul>";

// Testar configurações do banco
echo "<h2>Teste de Conexão com Banco:</h2>";
if (file_exists('includes/database.php')) {
    try {
        require_once 'includes/database.php';
        require_once 'includes/config.php';
        
        $db = db();
        $result = $db->fetch("SELECT COUNT(*) as total FROM usuarios", []);
        echo "<p>✅ Conexão com banco OK. Total de usuários: " . ($result['total'] ?? 'N/A') . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ Erro na conexão com banco: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Arquivo database.php não encontrado</p>";
}

echo "<h2>Cookies Atuais:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Sessão Atual:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
