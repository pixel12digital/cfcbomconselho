<?php
// Teste específico para debug do login
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

echo "<h1>Teste Debug do Login</h1>";

// Teste 1: Verificar configurações principais
echo "<h2>1. Configurações do Sistema:</h2>";
echo "<ul>";
echo "<li><strong>Environment:</strong> " . ENVIRONMENT . "</li>";
echo "<li><strong>APP_URL:</strong> " . APP_URL . "</li>";
echo "<li><strong>Session Name:</strong> " . SESSION_NAME . "</li>";
echo "<li><strong>Session Timeout:</strong> " . SESSION_TIMEOUT . "</li>";
echo "<li><strong>Max Login Attempts:</strong> " . MAX_LOGIN_ATTEMPTS . "</li>";
echo "<li><strong>DB_HOST:</strong> " . DB_HOST . "</li>";
echo "<li><strong>DB_NAME:</strong> " . DB_NAME . "</li>";
echo "</ul>";

// Teste 2: Verificar estado da sessão
echo "<h2>2. Estado da Sessão:</h2>";
echo "<ul>";
echo "<li><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'ATIVO' : 'INATIVO') . "</li>";
echo "<li><strong>Session ID:</strong> " . session_id() . "</li>";
echo "<li><strong>Headers Sent:</strong> " . (headers_sent() ? 'SIM' : 'NÃO') . "</li>";
echo "<li><strong>Output Buffer Level:</strong> " . ob_get_level() . "</li>";
echo "</ul>";

// Teste 3: Tentar criar instância Auth
echo "<h2>3. Teste da Classe Auth:</h2>";
try {
    $auth = new Auth();
    echo "<p>✅ Instância Auth criada com sucesso</p>";
    
    // Teste login simulation
    $testLogin = $auth->login('test@test.com', 'senha123');
    echo "<p><strong>Teste de login simulado:</strong> " . ($testLogin['success'] ? 'Sucesso' : $testLogin['message']) . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro ao criar Auth: " . $e->getMessage() . "</p>";
}

// Teste 4: Verificar usuários existentes
echo "<h2>4. Usuários Existentes:</h2>";
try {
    $db = db();
    $usuarios = $db->fetchAll("SELECT id, email, tipo, ativo FROM usuarios LIMIT 5");
    
    if ($usuarios) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Tipo</th><th>Ativo</th></tr>";
        foreach ($usuarios as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['tipo'] . "</td>";
            echo "<td>" . ($user['ativo'] ? 'SIM' : 'NÃO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Nenhum usuário encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Erro ao buscar usuários: " . $e->getMessage() . "</p>";
}

// Teste 5: Verificar se já está logado
echo "<h2>5. Estado Atual de Login:</h2>";
echo "<ul>";
echo "<li><strong>isLoggedIn():</strong> " . (isLoggedIn() ? 'SIM' : 'NÃO') . "</li>";

$currentUser = getCurrentUser();
if ($currentUser) {
    echo "<li><strong>Usuário Atual:</strong> " . $currentUser['email'] . " (ID: " . $currentUser['id'] . ")</li>";
} else {
    echo "<li><strong>Usuário Atual:</strong> NENHUM</li>";
}

echo "<li><strong>Sessão:</strong>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "</li>";
echo "</ul>";

// Teste 6: Simular processo de login manual
echo "<h2>6. Simulação Manual de Login:</h2>";

if (isset($_POST['simulate_login'])) {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (!empty($email) && !empty($senha)) {
        try {
            $result = $auth->login($email, $senha);
            
            if ($result['success']) {
                echo "<p>✅ Login simulado <strong>SUCESSO</strong></p>";
                echo "<p>Usuário: " . $result['user']['email'] . "</p>";
                
                // Verificar se redirecionamento funcionaria
                if (ob_get_level()) {
                    echo "<p>🟡 Output buffer ativo - precisa limpar antes do redirecionamento</p>";
                } else {
                    echo "<p>✅ Output buffer limpo - redirecionamento funcionaria</p>";
                }
                
            } else {
                echo "<p>❌ Login simulado <strong>FALHOU</strong></p>";
                echo "<p>Motivo: " . $result['message'] . "</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Erro no login: " . $e->getMessage() . "</p>";
        }
    }
}

// Formulário para teste
echo "<form method='POST' style='margin-top: 20px; padding: 20px; border: 1px solid #ccc;'>";
echo "<h3>Teste de Login Manual:</h3>";
echo "<p><label>Email: <input type='email' name='email' value='admin@cfc.com' required></label></p>";
echo "<p><label>Senha: <input type='password' name='senha' required></label></p>";
echo "<p><button type='submit' name='simulate_login'>Simular Login</button></p>";
echo "</form>";

echo "<p><a href='login.php'>← Voltar para Login</a></p>";
?>
