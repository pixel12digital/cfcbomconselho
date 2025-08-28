<?php
// Debug da função de exclusão de CFC
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug da Função de Exclusão de CFC</h1>";

// Verificar se o arquivo cfcs.php existe
$cfcFile = 'admin/pages/cfcs.php';
if (file_exists($cfcFile)) {
    echo "<p>✅ Arquivo cfcs.php encontrado</p>";
    
    // Verificar tamanho do arquivo
    $fileSize = filesize($cfcFile);
    echo "<p>📏 Tamanho do arquivo: " . number_format($fileSize) . " bytes</p>";
    
    // Verificar se a função excluirCFC está no arquivo
    $fileContent = file_get_contents($cfcFile);
    if (strpos($fileContent, 'function excluirCFC') !== false) {
        echo "<p>✅ Função excluirCFC encontrada no arquivo</p>";
        
        // Extrair a função para verificar
        preg_match('/function excluirCFC\([^)]*\)\s*\{[^}]*\}/s', $fileContent, $matches);
        if (!empty($matches)) {
            echo "<p>✅ Função extraída com sucesso</p>";
            echo "<details>";
            echo "<summary>📋 Ver função excluirCFC</summary>";
            echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
            echo "</details>";
        } else {
            echo "<p>❌ Não foi possível extrair a função</p>";
        }
        
        // Verificar se há erros de sintaxe JavaScript
        preg_match('/<script>(.*?)<\/script>/s', $fileContent, $scriptMatches);
        if (!empty($scriptMatches)) {
            echo "<p>✅ Tag script encontrada</p>";
            
            // Verificar se há problemas com aspas ou caracteres especiais
            $scriptContent = $scriptMatches[1];
            if (strpos($scriptContent, 'console.log') !== false) {
                echo "<p>✅ Console.log encontrado no script</p>";
            }
            
            // Verificar se há problemas com aspas
            $singleQuotes = substr_count($scriptContent, "'");
            $doubleQuotes = substr_count($scriptContent, '"');
            echo "<p>📊 Contagem de aspas: Simples: {$singleQuotes}, Duplas: {$doubleQuotes}</p>";
            
        } else {
            echo "<p>❌ Tag script não encontrada</p>";
        }
        
    } else {
        echo "<p>❌ Função excluirCFC não encontrada no arquivo</p>";
        
        // Procurar por variações
        if (strpos($fileContent, 'excluirCFC') !== false) {
            echo "<p>⚠️ String 'excluirCFC' encontrada, mas não como função</p>";
        }
    }
    
    // Verificar as últimas linhas do arquivo
    $lines = file($cfcFile);
    $lastLines = array_slice($lines, -10);
    echo "<details>";
    echo "<summary>📋 Últimas 10 linhas do arquivo</summary>";
    echo "<pre>";
    foreach ($lastLines as $i => $line) {
        $lineNum = count($lines) - 10 + $i + 1;
        echo sprintf("%4d: %s", $lineNum, htmlspecialchars($line));
    }
    echo "</pre>";
    echo "</details>";
    
} else {
    echo "<p>❌ Arquivo cfcs.php não encontrado</p>";
}

// Verificar se há problemas de permissão
if (file_exists($cfcFile)) {
    $perms = fileperms($cfcFile);
    echo "<p>🔐 Permissões do arquivo: " . substr(sprintf('%o', $perms), -4) . "</p>";
}

echo "<hr>";
echo "<h2>🧪 Próximos Passos</h2>";
echo "<p>1. Abra a página de CFCs no navegador</p>";
echo "<p>2. Abra o console do navegador (F12)</p>";
echo "<p>3. Verifique se há erros JavaScript</p>";
echo "<p>4. Teste o botão de exclusão</p>";
echo "<p>5. Verifique se a função excluirCFC está disponível no console</p>";
?>
