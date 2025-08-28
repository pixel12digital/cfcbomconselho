<?php
// Debug específico da API de CFCs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Específico da API de CFCs</h2>";

try {
    // Simular uma requisição DELETE para a API
    echo "<h3>1. Simulando Requisição DELETE</h3>";
    
    // Iniciar sessão
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['last_activity'] = time();
    
    echo "✅ Sessão iniciada<br>";
    
    // Simular $_GET
    $_GET['id'] = '1';
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    
    echo "✅ Parâmetros simulados<br>";
    
    // Capturar output da API
    ob_start();
    
    // Incluir a API
    include 'admin/api/cfcs.php';
    
    $output = ob_get_clean();
    
    echo "<h4>Resposta da API:</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // Tentar decodificar JSON
    $data = json_decode($output, true);
    if ($data) {
        echo "<h4>Dados JSON decodificados:</h4>";
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<h3>2. Teste Direto da Operação DELETE</h3>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    require_once 'includes/auth.php';
    
    $db = Database::getInstance();
    
    // Verificar se CFC existe
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [1]);
    if (!$cfc) {
        echo "❌ CFC não encontrado<br>";
    } else {
        echo "✅ CFC encontrado: " . htmlspecialchars($cfc['nome']) . "<br>";
        
        // Verificar dependências novamente
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
            echo "⚠️ Ainda há dependências<br>";
        } else {
            echo "✅ Nenhuma dependência encontrada<br>";
            
            // Testar DELETE direto
            echo "<h4>Testando DELETE direto:</h4>";
            try {
                $result = $db->delete('cfcs', 'id = ?', [1]);
                if ($result) {
                    echo "✅ DELETE executado com sucesso<br>";
                } else {
                    echo "❌ DELETE falhou<br>";
                }
            } catch (Exception $e) {
                echo "❌ Erro no DELETE: " . $e->getMessage() . "<br>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Verificar Logs de Erro do PHP</h3>";

// Verificar se há logs de erro
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "Log de erro PHP: $errorLog<br>";
    $lines = file($errorLog);
    $lastLines = array_slice($lines, -5);
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "Log de erro PHP não encontrado ou não configurado<br>";
}

echo "<h3>✅ Debug Concluído</h3>";
?>
