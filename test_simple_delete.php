<?php
// Teste simples de exclusão de CFC
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Teste Simples de Exclusão</h2>";

try {
    // Iniciar sessão
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['last_activity'] = time();
    
    echo "✅ Sessão iniciada<br>";
    
    // Carregar dependências
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    require_once 'includes/auth.php';
    
    echo "✅ Dependências carregadas<br>";
    
    // Testar autenticação
    $isLoggedIn = isLoggedIn();
    $hasPermission = hasPermission('admin');
    
    echo "isLoggedIn(): " . ($isLoggedIn ? 'true' : 'false') . "<br>";
    echo "hasPermission('admin'): " . ($hasPermission ? 'true' : 'false') . "<br>";
    
    if (!$isLoggedIn || !$hasPermission) {
        echo "❌ Problema de autenticação<br>";
        exit;
    }
    
    // Testar banco
    $db = Database::getInstance();
    echo "✅ Conexão com banco OK<br>";
    
    // Verificar CFC
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [1]);
    if (!$cfc) {
        echo "❌ CFC não encontrado<br>";
        exit;
    }
    
    echo "✅ CFC encontrado: " . htmlspecialchars($cfc['nome']) . "<br>";
    
    // Verificar dependências
    $instrutores = $db->count('instrutores', 'cfc_id = ?', [1]);
    $alunos = $db->count('alunos', 'cfc_id = ?', [1]);
    $veiculos = $db->count('veiculos', 'cfc_id = ?', [1]);
    $aulas = $db->count('aulas', 'cfc_id = ?', [1]);
    
    echo "Dependências:<br>";
    echo "• Instrutores: $instrutores<br>";
    echo "• Alunos: $alunos<br>";
    echo "• Veículos: $veiculos<br>";
    echo "• Aulas: $aulas<br>";
    
    if ($instrutores > 0 || $alunos > 0 || $veiculos > 0 || $aulas > 0) {
        echo "⚠️ Ainda há dependências - exclusão seria bloqueada<br>";
    } else {
        echo "✅ Nenhuma dependência - testando exclusão...<br>";
        
        // Testar DELETE
        try {
            $result = $db->delete('cfcs', 'id = ?', [1]);
            if ($result) {
                echo "✅ DELETE executado com sucesso!<br>";
            } else {
                echo "❌ DELETE falhou<br>";
            }
        } catch (Exception $e) {
            echo "❌ Erro no DELETE: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<h3>✅ Teste Concluído</h3>";
?>
