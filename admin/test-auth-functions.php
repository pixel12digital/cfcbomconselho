<?php
/**
 * Teste das Funções de Autenticação
 * Verifica se as funções getCurrentUser() e hasPermission() estão funcionando
 */

// Definir caminho base
$base_path = dirname(dirname(__DIR__));

// Forçar charset UTF-8
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

echo "<h1>🧪 Teste das Funções de Autenticação</h1>";

try {
    // Incluir arquivos necessários
    echo "<h2>📁 Incluindo arquivos...</h2>";
    require_once $base_path . '/includes/config.php';
    echo "✅ config.php incluído<br>";
    
    require_once $base_path . '/includes/database.php';
    echo "✅ database.php incluído<br>";
    
    require_once $base_path . '/includes/auth.php';
    echo "✅ auth.php incluído<br>";
    
    echo "<h2>🔐 Testando funções de autenticação...</h2>";
    
    // Testar isLoggedIn()
    echo "🧪 Testando isLoggedIn(): ";
    if (function_exists('isLoggedIn')) {
        $loggedIn = isLoggedIn();
        echo $loggedIn ? "✅ TRUE" : "❌ FALSE";
        echo "<br>";
    } else {
        echo "❌ Função não encontrada<br>";
    }
    
    // Testar getCurrentUser()
    echo "🧪 Testando getCurrentUser(): ";
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
        if ($user) {
            echo "✅ Usuário encontrado<br>";
            echo "   - ID: " . ($user['id'] ?? 'N/A') . "<br>";
            echo "   - Tipo: " . ($user['tipo'] ?? 'N/A') . "<br>";
            echo "   - Nome: " . ($user['nome'] ?? 'N/A') . "<br>";
        } else {
            echo "❌ Usuário não encontrado<br>";
        }
    } else {
        echo "❌ Função não encontrada<br>";
    }
    
    // Testar hasPermission()
    echo "🧪 Testando hasPermission('admin'): ";
    if (function_exists('hasPermission')) {
        $isAdmin = hasPermission('admin');
        echo $isAdmin ? "✅ TRUE" : "❌ FALSE";
        echo "<br>";
        
        echo "🧪 Testando hasPermission('instrutor'): ";
        $isInstrutor = hasPermission('instrutor');
        echo $isInstrutor ? "✅ TRUE" : "❌ FALSE";
        echo "<br>";
    } else {
        echo "❌ Função não encontrada<br>";
    }
    
    echo "<h2>🎯 Teste das Variáveis</h2>";
    echo "✅ \$user definido: " . (isset($user) ? 'SIM' : 'NÃO') . "<br>";
    echo "✅ \$isAdmin definido: " . (isset($isAdmin) ? 'SIM' : 'NÃO') . " = " . ($isAdmin ? 'TRUE' : 'FALSE') . "<br>";
    echo "✅ \$isInstrutor definido: " . (isset($isInstrutor) ? 'SIM' : 'NÃO') . " = " . ($isInstrutor ? 'TRUE' : 'FALSE') . "<br>";
    
    echo "<h2>✅ Resultado do Teste</h2>";
    if (isset($user) && isset($isAdmin) && isset($isInstrutor)) {
        echo "<div style='background: #d4edda; padding: 1rem; border-radius: 4px; color: #155724;'>";
        echo "✅ <strong>TODAS AS FUNÇÕES ESTÃO FUNCIONANDO!</strong><br>";
        echo "✅ A página de turmas teóricas deve carregar corretamente agora.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
        echo "❌ <strong>PROBLEMAS ENCONTRADOS:</strong><br>";
        echo "❌ Algumas funções não estão funcionando corretamente.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<br><br>";
echo "<a href='pages/turmas-teoricas.php' class='btn btn-primary'>🎯 Ir para Turmas Teóricas</a>";
?>
