<?php
// Script de teste para exclusão de instrutores
// Execute este arquivo para testar se a API de exclusão está funcionando

echo "<h1>Teste de Exclusão de Instrutores</h1>";

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = Database::getInstance();
    
    // 1. Verificar se há instrutores na tabela
    echo "<h2>1. Verificando instrutores existentes</h2>";
    $instrutores = $db->fetchAll("SELECT * FROM instrutores LIMIT 5");
    
    if (empty($instrutores)) {
        echo "<p style='color: orange;'>⚠️ Nenhum instrutor encontrado na tabela.</p>";
        echo "<p>Para testar a exclusão, você precisa ter pelo menos um instrutor cadastrado.</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Encontrados " . count($instrutores) . " instrutores.</p>";
    
    // Mostrar o primeiro instrutor
    $primeiroInstrutor = $instrutores[0];
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
    echo "<strong>ID:</strong> " . $primeiroInstrutor['id'] . "<br>";
    echo "<strong>Usuario ID:</strong> " . $primeiroInstrutor['usuario_id'] . "<br>";
    echo "<strong>CFC ID:</strong> " . $primeiroInstrutor['cfc_id'] . "<br>";
    echo "<strong>Credencial:</strong> " . ($primeiroInstrutor['credencial'] ?? 'N/A') . "<br>";
    echo "</div>";
    
    // 2. Verificar usuário relacionado
    echo "<h2>2. Verificando usuário relacionado</h2>";
    $usuario = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$primeiroInstrutor['usuario_id']]);
    
    if ($usuario) {
        echo "<p style='color: green;'>✅ Usuário encontrado:</p>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
        echo "<strong>ID:</strong> " . $usuario['id'] . "<br>";
        echo "<strong>Nome:</strong> " . $usuario['nome'] . "<br>";
        echo "<strong>Email:</strong> " . $usuario['email'] . "<br>";
        echo "<strong>Tipo:</strong> " . $usuario['tipo'] . "<br>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Usuário não encontrado!</p>";
    }
    
    // 3. Testar exclusão via API
    echo "<h2>3. Testando exclusão via API</h2>";
    
    $id = $primeiroInstrutor['id'];
    $url = "http://localhost:8080/cfc-bom-conselho/admin/api/instrutores.php?id=$id";
    
    echo "<p>URL da API: <code>$url</code></p>";
    
    // Criar contexto para requisição DELETE
    $context = stream_context_create([
        'http' => [
            'method' => 'DELETE',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    try {
        echo "<p>Enviando requisição DELETE...</p>";
        
        $response = file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            echo "<pre>Resposta da API: ";
            print_r($data);
            echo "</pre>";
            
            if ($data['success']) {
                echo "<p style='color: green;'>✅ Instrutor excluído com sucesso!</p>";
                
                // Verificar se foi realmente excluído
                echo "<h2>4. Verificando se foi excluído</h2>";
                $instrutorExcluido = $db->fetch("SELECT * FROM instrutores WHERE id = ?", [$id]);
                if (!$instrutorExcluido) {
                    echo "<p style='color: green;'>✅ Instrutor não encontrado na tabela (excluído com sucesso)</p>";
                } else {
                    echo "<p style='color: red;'>❌ Instrutor ainda existe na tabela!</p>";
                }
                
                // Verificar se o usuário foi excluído
                $usuarioExcluido = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$primeiroInstrutor['usuario_id']]);
                if (!$usuarioExcluido) {
                    echo "<p style='color: green;'>✅ Usuário não encontrado na tabela (excluído com sucesso)</p>";
                } else {
                    echo "<p style='color: red;'>❌ Usuário ainda existe na tabela!</p>";
                }
                
            } else {
                echo "<p style='color: red;'>❌ Erro na API: " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Erro ao acessar a API</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Exceção: " . $e->getMessage() . "</p>";
    }
    
    // 5. Verificar logs de erro
    echo "<h2>5. Verificando logs de erro</h2>";
    
    $logFile = ini_get('error_log');
    if ($logFile && file_exists($logFile)) {
        echo "<p>Arquivo de log: <code>$logFile</code></p>";
        
        // Ler as últimas linhas do log
        $logLines = file($logFile);
        $ultimasLinhas = array_slice($logLines, -20); // Últimas 20 linhas
        
        echo "<h3>Últimas linhas do log:</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto;'>";
        foreach ($ultimasLinhas as $linha) {
            echo htmlspecialchars($linha);
        }
        echo "</pre>";
    } else {
        echo "<p>Arquivo de log não encontrado ou não configurado.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro geral: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Instruções para resolver problemas:</strong></p>";
echo "<ol>";
echo "<li>Verifique se o usuário está logado no sistema</li>";
echo "<li>Verifique se tem permissão de admin</li>";
echo "<li>Verifique se a API está acessível</li>";
echo "<li>Verifique os logs de erro do servidor</li>";
echo "<li>Teste a exclusão diretamente no banco de dados</li>";
echo "</ol>";

echo "<h3>Teste direto no banco:</h3>";
echo "<p>Para testar diretamente no banco, execute estas queries no phpMyAdmin:</p>";
echo "<pre>";
echo "-- Verificar instrutores
SELECT * FROM instrutores;

-- Verificar usuários
SELECT * FROM usuarios;

-- Testar exclusão manual (substitua X pelo ID real)
-- DELETE FROM instrutores WHERE id = X;
-- DELETE FROM usuarios WHERE id = X;
</pre>";
?>
